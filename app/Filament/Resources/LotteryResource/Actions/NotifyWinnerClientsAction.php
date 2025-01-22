<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use App\Models\Client;
use Filament\Tables\Actions\Action;
use App\Models\Lottery;
use Exception;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Str;

class NotifyWinnerClientsAction extends Action
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->name('NotifyWinnerClients');

    $this->label("Notificar a ganadores")
      ->icon('heroicon-o-paper-airplane')
      ->hidden(
        fn(Lottery $record) =>
        !($record->finished_at !== null && $record->final_date !== now()->format('d/m/Y'))
      )
      ->action(function (Lottery $record) {
        $clients = $record->get_winners()->count();

        try {
          Artisan::call("ws:winners {$record->id}");

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
