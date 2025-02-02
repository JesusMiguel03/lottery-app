<?php

namespace App\Listeners;

use App\Events\BotMessageProcessedEvent;
use App\Models\Lottery;
use App\Models\Ticket;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class NotifyWhatsappMessageSentListener
{
    public function handle(BotMessageProcessedEvent $event)
    {
        $user = User::find(1);
        $lottery = Lottery::find($event->lotteryId);

        if ($event->objetive === 'winners') {
            Ticket::where('lottery_id', $event->lotteryId)->update([
                'notified_at' => now()
            ]);

            Notification::make()
                ->title('Clientes ganadores notificados')
                ->body(Str::markdown("Se notificaron (**{$event->clientCount}**) clientes ganadores de la rifa {**#$event->lotteryId**} (**{$lottery->name}**)."))
                ->success()
                ->sendToDatabase($user);
        } else {
            Ticket::where('lottery_id', $event->lotteryId)
                ->whereNotNull('client_id')
                ->increment('alerts');

            $ticketsCount = Ticket::where('lottery_id', $event->lotteryId)
                ->whereNotNull('client_id')
                ->count();

            Notification::make()
                ->title('Clientes deudores notificados')
                ->body(Str::markdown("Se notificaron (**{$event->clientCount}**) clientes deudores de la rifa {**#$event->lotteryId**} (**{$lottery->name}**). Un total de (**{$ticketsCount}**) boletos fueron notificados"))
                ->success()
                ->sendToDatabase($user);
        }
    }
}
