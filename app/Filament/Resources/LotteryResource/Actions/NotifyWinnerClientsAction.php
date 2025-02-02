<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use App\Filament\Traits\HasActivityLogger;
use App\Jobs\ProcessWhatsappMessagesJob;
use Filament\Tables\Actions\Action;
use App\Models\Lottery;

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

        ProcessWhatsappMessagesJob::dispatch($clients, $record->id, 'winners');

        HasActivityLogger::logActivity($record, 'notify_winners', 'notification');
      });
  }
}
