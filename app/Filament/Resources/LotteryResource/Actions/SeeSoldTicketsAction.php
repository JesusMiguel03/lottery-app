<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Actions\Action;
use App\Models\Lottery;
use Filament\Forms\Get;

class SeeSoldTicketsAction extends Action
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->name('SeeSoldTickets');

    $this->label("Boletos vendidos")
      ->icon('heroicon-o-document-currency-dollar')
      ->slideOver()
      ->hidden(fn(Lottery $record) => $record->tickets()->whereHas('client')->count() === 0)
      ->modalHeading(fn(Lottery $record) => "Boletos vendidos ({$record->tickets()->whereHas('client')->count()}) para rifa #{$record->id} ({$record->name})")
      ->form([
        CheckboxList::make('tickets')
          ->label('Boletos')
          ->options(function (Lottery $record) {
            $tickets = $record->tickets()
              ->whereHas('client')
              ->with('client')
              ->select('number', 'id', 'client_id')
              ->get();

            $formatted_tickets = $tickets->mapWithKeys(function ($ticket) use ($record) {
              $client_name = $ticket->client->full_name;
              $payment = $ticket->total_payed === 0
                ? 'Pendiente'
                : (
                  $ticket->total_payed === $record->ticket_price
                  ? "{$ticket->total_payed} $"
                  : "{$ticket->total_payed} $ / {$record->ticket_price} $"
                );

              return [
                $ticket->id => "{$ticket->number} - {$client_name} ({$payment})"
              ];
            })->toArray();

            return $formatted_tickets;
          })
          ->default(function (Lottery $record, Get $get) {
            $has_client = empty($get('client_id'));
            if (!$has_client) {
              return [];
            }

            $tickets = $record->tickets->where('client_id', $has_client)->pluck('id')->toArray();

            return $tickets;
          })
          ->disableOptionWhen(fn(Lottery $record, string $value) => in_array($value, $record->tickets->where('client_id', '!=', null)->pluck('id')->toArray()))
          ->columns(3),
      ])
      ->modalSubmitAction(false);
  }
}
