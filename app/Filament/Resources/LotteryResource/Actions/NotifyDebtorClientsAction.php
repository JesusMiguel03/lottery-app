<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use App\Filament\Traits\HasActivityLogger;
use App\Models\Client;
use Filament\Tables\Actions\Action;
use App\Models\Lottery;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class NotifyDebtorClientsAction extends Action
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->name('NotifyDebtorClients');

    $this->label("Notificar a deudores")
      ->icon('heroicon-o-chat-bubble-left')
      ->hidden(
        fn(Lottery $record) =>
        $record->tickets()->whereHas('client')->whereDoesntHave('payment')->count() === 0 || (now()->format('d/m/Y') > $record->final_date)
      )
      ->action(function (Lottery $record) {
        $clients = Client::whereHas(
          'tickets',
          fn($query) => $query->whereDoesntHave('payment')
            ->where('lottery_id', $record->id)
        )->count();

        try {
          $process = new Process(['php', 'artisan', 'ws:debtors', $record->id], base_path());
          $process->setTimeout(300);
          $process->run(function ($type, $buffer) {
            $logFilePath = public_path('logs/notification_log.txt');

            if (!File::exists(public_path('logs'))) {
              File::makeDirectory(public_path('logs'), 0755, true);
            }
            if (Process::ERR === $type) {
              File::append($logFilePath, now() . ' - ' . '[Notificate winner]' . ' ' . $buffer . PHP_EOL);
            } else {
              File::append($logFilePath, now() . ' - ' . '[Notificate winner]' . ' ' . $buffer . PHP_EOL);
            }
          });

          if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
          }

          HasActivityLogger::logActivity(null, 'notify_debtors', 'notification');

          Notification::make()
            ->title('Clientes notificados')
            ->body(Str::markdown("Se notificaron (**{$clients}**) clientes deudores."))
            ->success()
            ->send();
        } catch (Exception $e) {
          $logFilePath = public_path('logs/error_log.txt');

          if (!File::exists(public_path('logs'))) {
            File::makeDirectory(public_path('logs'), 0755, true);
          }

          File::append($logFilePath, now() . ' - ' . '[NotifyDebtorClientsAction]' . ' ' . $e->getMessage() . PHP_EOL);

          Notification::make()
            ->title('Ocurrió un error')
            ->body(Str::markdown("No se pudo notificar a los clientes, por favor intente de nuevo más tarde. Si el error persite después de varios intento favor comunicarse."))
            ->danger()
            ->send();
        }
      });
  }
}
