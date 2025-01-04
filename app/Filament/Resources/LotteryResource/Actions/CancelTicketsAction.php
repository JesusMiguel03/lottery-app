<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Actions\Action;
use App\Models\Lottery;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Get;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class CancelTicketsAction extends Action
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->name('CancelTickets');

    $this->label("Cancelar boletos")
      ->icon('heroicon-o-ticket')
      ->slideOver()
      ->hidden(
        fn(Lottery $record) =>
        $record->tickets()->whereHas('client')->whereDoesntHave('payment')->count() === 0
      )
      ->modalHeading(fn(Lottery $record) => "Cancelar boletos para rifa #{$record->id} ({$record->name})")
      ->form([
        CheckboxList::make('tickets')
          ->label(function (Lottery $record, Get $get) {
            $available_tickets = $record->tickets->where('client_id', null)->pluck('id')->toArray();
            $selected_tickets = count(array_intersect($available_tickets, $get('tickets')));

            return $selected_tickets > 0
              ? "Boletos (seleccionados: {$selected_tickets})"
              : 'Boletos';
          })
          ->options(function (Lottery $record) {
            $tickets = $record->tickets()->with('client')->where('client_id', '!=', null)->whereDoesntHave('payment')->select('number', 'id', 'client_id')->get();

            $ticket_price = empty($record->total_price) ? 0 : $record->total_price / $record->tickets->count();
            $formatted_tickets = $tickets->mapWithKeys(function ($ticket) use ($ticket_price) {
              $client_name = empty($ticket->client) ? "Disponible {$ticket_price}$" : $ticket->client->fullName;
              return [
                $ticket->id => "{$ticket->number} - {$client_name}"
              ];
            })->toArray();

            return $formatted_tickets;
          })
          ->columns(3)
          ->rules(['required'])
          ->validationMessages([
            'required' => 'Debe seleccionar al menos un boleto'
          ])
          ->searchable()
          ->markAsRequired()
          ->bulkToggleable(),
        Placeholder::make('note')
          ->label('Nota')
          ->content(function () {
            $content = [
              'Solo se pueden cancelar boletos no pagados.',
              'Todo boleto cancelado estará de nuevo disponible para su venta.',
            ];

            $listItems = '';
            foreach ($content as $row) {
              if (!empty($row)) {
                $listItems .= "<li>{$row}</li>";
              }
            }
            return new HtmlString("
							<ul>
								{$listItems}
							</ul>
						");
          })
      ])
      ->modalSubmitAction(fn(Lottery $record) => count($record->tickets_left()) >= 1 ? null : false)
      ->action(function (Lottery $record, array $data) {
        $record->tickets()->whereIn('id', $data['tickets'])->update(['client_id' => null]);

        $total_tickets = count($data['tickets']);

        Notification::make()
          ->title('Boletos cancelados')
          ->body(Str::markdown("Se han cancelado **{$total_tickets}** boletos no pagados de la rifa **#{$record->id}** (**{$record->name}**)."))
          ->success()
          ->send();
      });
  }
}
