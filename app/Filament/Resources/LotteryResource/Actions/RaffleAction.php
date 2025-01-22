<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use Filament\Tables\Actions\Action;
use App\Models\Lottery;
use App\Models\Ticket;
use Exception;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
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
      ->hidden(
        fn(Lottery $record) =>
        !($record->final_date === now()->format('d/m/Y') &&
          count($record->get_payed_tickets()) > 0)
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
            $tickets = $record->get_winners();
            $prizes = $record->prizes;

            $ticketList = $tickets->map(function ($ticket, $index) use ($prizes) {
              $prize = $prizes[$ticket->order - 1];
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
        Repeater::make('selected_tickets')
          ->label('Seleccionar boletos ganadores')
          ->schema([
            Select::make('ticket')
              ->label('Boletos')
              ->rules([
                'required'
              ])
              ->validationMessages([
                'required' => 'Debe seleccionar una opción',
                'in' => 'Debe seleccionar una opción de la lista'
              ])
              ->searchable()
              ->in(
                values: fn(Lottery $record) =>
                $record->get_payed_tickets()->pluck('id')->toArray()
              )
              ->options(
                fn(Lottery $record) =>
                $record->get_payed_tickets()->pluck('ticket_owner_name', 'id')->toArray()
              )
              ->disableOptionWhen(function (string $value, Get $get) {
                $selected_tickets = collect($get('../') ?? [])
                  ->pluck('ticket')
                  ->filter()
                  ->map(fn($item) => (int) $item)
                  ->toArray();

                return in_array((int) $value, $selected_tickets);
              })
          ])
          ->itemLabel(function () {
            static $custom_index = 1;
            return "Número ganador: #" . $custom_index++;
          })
          ->addActionLabel('Agregar ganador')
          ->defaultItems(fn(Lottery $record) => $record->get_payed_tickets()->count())
          ->maxItems(fn(Lottery $record) => $record->get_payed_tickets()->count())
          ->reorderable(false)
          ->deletable(false)
          ->columnSpanFull()
          ->hidden(fn(Lottery $record) => count($record->get_winners()) > 0)
      ])
      ->modalSubmitActionLabel('Registrar ganadores')
      ->modalSubmitAction(
        fn(Lottery $record) =>
        count($record->get_winners()) === 0 ? null : false
      )
      ->action(function (Lottery $record, array $data) {
        $flat_data = array_map(
          fn($item) => $item['ticket'],
          $data['selected_tickets']
        );
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

        Ticket::with('client')
          ->findMany($flat_data)
          ->map(function ($ticket, $index) use ($record) {
            $current_index = ++$index;
            $ticket->update([
              'winner' => true,
              'notified_at' => now(),
              'order' => $current_index
            ]);

            Notification::make()
              ->title(Str::markdown("Ganador **#{$current_index}** seleccionado"))
              ->body(Str::markdown("Se ha seleccionado al cliente **{$ticket->client->fullName}** como ganador **#{$current_index}** de la rifa **#{$record->id} ({$record->name})**"))
              ->success()
              ->send();
          });

        $record->update(['finished_at' => now()]);

        Notification::make()
          ->title('Ganadores seleccionados')
          ->body(Str::markdown("Se han seleccionado los ganadores de la rifa #{$record->id} ({$record->name}), un total de ({$record->total_winners}) clientes ganaron"))
          ->success()
          ->send();

        try {
          Artisan::call("ws:winners {$record->id}");
          Notification::make()
            ->title('Ganadores seleccionados')
            ->body(Str::markdown("Se han seleccionado los ganadores de la rifa #{$record->id} ({$record->name}), un total de ({$record->total_winners}) clientes ganaron"))
            ->success()
            ->send();
        } catch (Exception $e) {
          $logFilePath = public_path('logs/error_log.txt');

          if (!File::exists(public_path('logs'))) {
            File::makeDirectory(public_path('logs'), 0755, true);
          }

          File::append($logFilePath, now() . ' - ' . '[RaffleAction]' . ' ' . $e->getMessage() . PHP_EOL);

          Notification::make()
            ->title('Ocurrió un error')
            ->body(Str::markdown("No se pudo notificar a los clientes ganadores, por favor seleccione la rifa y haga clic en la acción (**Notificar a ganadores**) para notificarles."))
            ->danger()
            ->send();
        }
      });
  }
}
