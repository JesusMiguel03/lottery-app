<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use App\Filament\Traits\HasActivityLogger;
use App\Jobs\ProcessWhatsappMessagesJob;
use App\Models\Client;
use Filament\Tables\Actions\Action;
use App\Models\Lottery;

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

        ProcessWhatsappMessagesJob::dispatch($clients, $record->id, 'debtors');

        HasActivityLogger::logActivity(null, 'notify_debtors', 'notification');
      });
  }
}
