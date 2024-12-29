<?php

namespace App\Filament\Resources\ClientResource\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Actions\Action;
use App\Models\Client;
use App\Models\Ticket;
use Filament\Forms\Get;
use Filament\Forms\Set;

class SeeTicketsAction extends Action
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->name('SeeTickets');

    $this->label("Ver boletos")
      ->icon('heroicon-o-tag')
      ->modalHeading(fn(Client $record) => "Ver boletos de  ({$record->name})")
      ->form([
        Select::make('lottery_id')
          ->label('Rifas')
          ->live()
          ->options(fn(Client $record) => $record->get_lotteries())
          ->searchable()
          ->afterStateUpdated(function (Client $record, Get $get, Set $set) {
            $tickets = Ticket::where('lottery_id', $get('lottery_id'))
              ->where('client_id', $record->id)
              ->pluck('id')
              ->toArray();

            $set('tickets', $tickets);
          }),
        CheckboxList::make('tickets')
          ->label('Boletos')
          ->options(
            function (Client $record, Get $get) {
              return match ($get('lottery_id')) {
                null => [],
                default => Ticket::where('lottery_id', $get('lottery_id'))
                  ->where('client_id', $record->id)
                  ->get()
                  ->mapWithKeys(fn($ticket) => [$ticket->id => $ticket->number])
                  ->toArray()
              };
            }
          )
          ->hidden(fn(Get $get) => empty($get('lottery_id')))
          ->columns(6)
          ->disabled(fn(Get $get) => true)
          ->default(fn(Get $get) => $get('tickets') ?? [])
      ])
      ->modalSubmitAction(false)
      ->hidden(fn(Client $record) => count($record->get_lotteries()) === 0);
  }
}
