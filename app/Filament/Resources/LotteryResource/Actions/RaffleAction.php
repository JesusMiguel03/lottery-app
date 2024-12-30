<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use Filament\Tables\Actions\Action;
use App\Models\Lottery;
use Filament\Forms\Components\Placeholder;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class RaffleAction extends Action
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->name('Raffle');

    $this->label(fn(Lottery $record) => count($record->get_winners()) === 0 ? "Realizar sorteo" : 'Ver ganadores')
      ->icon(fn(Lottery $record) => count($record->get_winners()) === 0 ? 'heroicon-o-play' : 'heroicon-o-star')
      ->slideOver()
      ->hidden(fn(Lottery $record) => !($record->final_date === now()->format('d/m/Y') && count($record->get_payed_tickets()) > 0))
      ->modalHeading(function (Lottery $record) {
        $lottery_id = $record->id;
        $lottery_name = $record->name;
        $payed_tickets = count($record->get_payed_tickets()->pluck('number', 'id')->toArray());

        return "Sorteo de boletos para rifa #{$lottery_id} ({$lottery_name}) - (Boletos pagados: {$payed_tickets})";
      })
      ->form([
        // Winners
        Placeholder::make('')
          ->content(function (Lottery $record) {
            $tickets = $record->get_winners();
            $prizes = $record->prizes;

            $ticketList = $tickets->map(function ($ticket, $index) use ($prizes) {
              $prize = $prizes[$index];
              ++$index;
              return "<li class='py-2 px-6 border border-neutral-400 rounded-md'>
                        <div class='flex flex-col justify-center items-center'>
                          <h3 class='font-bold'>Ganador &num;{$index}</h3>
                          <h6 class='text-lg font-semibold'>
                            {$ticket->client->fullname}
                          </h6>
                          <p>Premio: {$prize->name} ({$prize->value}$)</p>
                          <p>Boleto &num;{$ticket->number}</p>
                        </div>
                    </li>";
            })->implode('');

            return new HtmlString("
              <ul class='flex flex-wrap justify-center gap-3'>
                  {$ticketList}
              </ul>
            ");
          })->columnSpanFull(),

        // Payed tickets
        Placeholder::make('')
          ->content(function (Lottery $record) {
            $tickets = $record->get_payed_tickets()->pluck('number', 'id')->toArray();

            $ticketList = collect($tickets)->map(function ($ticket) {
              return "<li class='py-2 px-6 border border-neutral-400 rounded-md'>
                        <p>Boleto &num;{$ticket}</p>
                    </li>";
            })->implode('');

            return new HtmlString("
              <ul class='flex flex-wrap justify-center gap-3'>
                  {$ticketList}
              </ul>
            ");
          })->columnSpanFull()
          ->hidden(fn(Lottery $record) => count($record->get_winners()) > 0),
      ])
      ->modalSubmitActionLabel('Sortear')
      ->modalSubmitAction(fn(Lottery $record) => count($record->get_winners()) === 0 ? null : false)
      ->action(function (Lottery $record) {
        $tickets = $record->get_payed_tickets();
        $tickets_missing = $record->total_winners - count($tickets);

        if (count($tickets) < $record->total_winners) {
          Notification::make()
            ->title('Boletos faltantes')
            ->body(Str::markdown("No se pueden seleccionar los ganadores de la rifa #{$record->id} ({$record->name}), no hay suficientes boletos pagados, faltan ({$tickets_missing}) boletos por pagar"))
            ->danger()
            ->send();

          return;
        }

        $tickets->random($record->total_winners)->each(function ($ticket) {
          $ticket->update(['winner' => true]);
        });

        Notification::make()
          ->title('Ganadores seleccionados')
          ->body(Str::markdown("Se han seleccionado los ganadores de la rifa #{$record->id} ({$record->name}), un total de ({$record->total_winners}) clientes ganaron"))
          ->success()
          ->send();
      });
  }
}
