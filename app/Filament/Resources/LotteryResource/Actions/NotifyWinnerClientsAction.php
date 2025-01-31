<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use App\Filament\Traits\HasActivityLogger;
use Filament\Tables\Actions\Action;
use App\Models\Lottery;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class NotifyWinnerClientsAction extends Action
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->name('NotifyWinnerClients');

    $this->label("Notificar a ganadores")
      ->icon('heroicon-o-paper-airplane')
      ->hidden(
        fn(Lottery $record) => $record->getNotifiedTickets()->count() > 0 || now()->format('d/m/Y') > $record->final_date || $record->getWinners()->count() === 0
      )
      ->action(function (Lottery $record) {
        $clients = $record->getWinners()->count();

        try {
          $process = new Process(['php', 'artisan', 'ws:winners', $record->id], base_path());
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

          HasActivityLogger::logActivity($record, 'notify_winners', 'notification');

          Notification::make()
            ->title('Clientes notificados')
            ->body(Str::markdown("Se notificaron (**{$clients}**) clientes ganadores."))
            ->success()
            ->send();
        } catch (Exception $e) {
          $logFilePath = public_path('logs/error_log.txt');

          if (!File::exists(public_path('logs'))) {
            File::makeDirectory(public_path('logs'), 0755, true);
          }

          File::append($logFilePath, now() . ' - ' . '[NotifyWinnerClientsAction]' . ' ' . $e->getMessage() . PHP_EOL);

          Notification::make()
            ->title('Ocurrió un error')
            ->body(Str::markdown("No se pudo notificar a los clientes, por favor intente de nuevo más tarde. Si el error persite después de varios intento favor comunicarse."))
            ->danger()
            ->send();
        }
      });
  }
}
