<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use App\Filament\Traits\HasActivityLogger;
use Filament\Tables\Actions\Action;
use App\Models\Lottery;
use App\Models\Ticket;
use Filament\Forms\Components\Checkbox;
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

    $this->label(fn(Lottery $record) => count($record->getWinners()) === 0
      ? "Realizar sorteo"
      : 'Ver ganadores')
      ->icon(fn(Lottery $record) => count($record->getWinners()) === 0
        ? 'heroicon-o-play'
        : 'heroicon-o-star')
      ->slideOver()
      ->hidden(
        fn(Lottery $record) =>
        count($record->get_payed_tickets()) === 0 ||
          now()->format('d/m/Y') !== $record->final_date
      )
      ->modalHeading(function (Lottery $record) {
        $lottery_id = $record->id;
        $lottery_name = $record->name;
        $payed_tickets = count(
          $record->get_payed_tickets()
            ->pluck('number', 'id')
            ->toArray()
        );
        $total_tickets = $record->tickets()->count();

        return "Sorteo de boletos para rifa #{$lottery_id} ({$lottery_name}) - (Boletos pagados: {$payed_tickets}/{$total_tickets})";
      })
      ->form([
        // Winners
        Placeholder::make('')
          ->content(function (Lottery $record) {
            $tickets = $record->getWinners();
            $prizes = $record->prizes;

            $ticketList = $tickets->map(function ($ticket, $index) use ($prizes) {
              if (count($prizes) > $index) {
                $prize = $prizes[$ticket->order - 1 > 0 ? $ticket->order - 1 : 0];
                ++$index;
                $route = route('filament.admin.resources.clients.view', $ticket->client->id);
                return "
                    <a href='{$route}'>
                      <li class='py-2 px-6 border border-neutral-400 rounded-md'>
                          <div class='flex flex-col justify-center items-center'>
                            <h3 class='font-bold'>Ganador &num;{$index}</h3>
                            <h6 class='text-lg font-semibold'>
                              {$ticket->client->full_name}
                            </h6>
                            <p>Premio: {$prize->name} ({$prize->value}$)</p>
                            <p>Boleto &num;{$ticket->number}</p>
                          </div>
                      </li>
                    </a>";
              }
            })->implode('');

            return new HtmlString("
              <ul class='flex flex-wrap justify-center gap-3'>
                  {$ticketList}
              </ul>
            ");
          })->columnSpanFull(),

        // Random draw info
        Placeholder::make('')
          ->content(function (Lottery $record) {
            $total_winners = $record->total_winners;
            $payed_tickets = count($record->get_payed_tickets());

            return new HtmlString(
              "Se seleccionarán <strong>{$total_winners} ganador(es)</strong> al azar entre los boletos pagados ({$payed_tickets} boletos disponibles)."
            );
          })
          ->columnSpanFull()
          ->hidden(fn(Lottery $record) => count($record->getWinners()) > 0),

        // Draw confirmation
        Checkbox::make('confirm_draw')
          ->label(function (Lottery $record) {
            $total_winners = $record->total_winners;
            $payed_tickets = count($record->get_payed_tickets());

            return "Se sortearán {$total_winners} ganador(es) aleatoriamente entre {$payed_tickets} boletos pagados. ¿Confirmar?";
          })
          ->required()
          ->columnSpanFull()
          ->hidden(fn(Lottery $record) => count($record->getWinners()) > 0)
      ])
      ->modalSubmitActionLabel('Registrar ganadores')
      ->modalSubmitAction(
        fn(Lottery $record) =>
        count($record->getWinners()) === 0 ? null : false
      )
      ->action(function (Lottery $record, array $data) {
        $pool = $record->get_payed_tickets();
        $tickets_missing = $record->total_winners - count($pool);

        if (count($pool) < $record->total_winners) {
          Notification::make()
            ->title('Boletos faltantes')
            ->body(Str::markdown("No se pueden seleccionar los ganadores de la rifa #{$record->id} ({$record->name}), no hay suficientes boletos pagados, faltan ({$tickets_missing}) boletos por pagar"))
            ->danger()
            ->send();

          return;
        }

        $flat_data = $pool->pluck('id')->shuffle()->take($record->total_winners)->all();
        $prizes = $record->prizes;
        Ticket::with('client')
          ->findMany($flat_data)
          ->sortBy(fn($ticket) => array_search($ticket->id, $flat_data))
          ->map(function ($ticket, $index) use ($record, $prizes) {
            $current_index = ++$index;
            $ticket->update([
              'winner' => true,
              'order' => $current_index,
              'prize_id' => $prizes[$index - 1]->id
            ]);

            Notification::make()
              ->title(Str::markdown("Ganador **#{$current_index}** seleccionado"))
              ->body(Str::markdown("Se ha seleccionado al cliente **{$ticket->client->full_name}** como ganador **#{$current_index}** de la rifa **#{$record->id} ({$record->name})**"))
              ->success()
              ->send();
          });

        $record->update(['finished_at' => now()]);

        HasActivityLogger::logActivity($record, 'raffle', 'update', [
          'tickets' => $pool,
          'data' => $flat_data
        ]);

        Notification::make()
          ->title('Ganadores seleccionados')
          ->body(Str::markdown("Se han seleccionado los ganadores de la rifa #{$record->id} ({$record->name}), un total de ({$record->total_winners}) clientes ganaron."))
          ->success()
          ->send();
      });
  }
}
