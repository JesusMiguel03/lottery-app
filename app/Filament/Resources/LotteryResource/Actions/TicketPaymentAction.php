<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use Filament\Forms\Components\Select;
use Filament\Tables\Actions\Action;
use App\Models\Ticket;
use App\Models\Lottery;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Notifications\Notification;
use Illuminate\Support\Str;

class TicketPaymentAction extends Action
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->name('TicketPayment');

    $this->label("Pago de boleto")
      ->icon('heroicon-o-credit-card')
      ->hidden(fn(Lottery $record) => count($record->get_winners()) > 0 ? true : null)
      ->modalHeading(fn(Lottery $record) => "Pago de boletos para rifa #{$record->id} ({$record->name})")
      ->form([
        Select::make('ticket_id')
          ->label('Boleto')
          ->options(fn(Lottery $record) => $record->not_payed_tickets())
          ->markAsRequired()
          ->rules(['required'])
          ->validationMessages([
            'required' => 'Debe seleccionar un boleto'
          ]),
        Placeholder::make('Monto a pagar')
          ->content(fn(Lottery $record) => "{$record->ticket_price()}$"),
        Fieldset::make('Pagos')
          ->schema([
            TextInput::make('total_payed')
              ->label('Monto pagado')
              ->placeholder('Ej: 100')
              ->type('number')
              ->numeric()
              ->step(0.01)
              ->minValue(0.01)
              ->markAsRequired()
              ->rules(['required'])
              ->validationMessages([
                'required' => "Debe indicar un número",
                'min' => "Debe ser al menos :min",
                'max' => "Debe ser máximo :max",
              ]),
            Select::make('type')
              ->label('Tipo de pago')
              ->options([
                'usd' => 'Dólares efectivo',
                'bs' => 'Bolìvares efectivo',
                'payment' => 'Pago mòvil',
                'other' => 'Otros'
              ])
              ->markAsRequired()
              ->rules(['required'])
              ->validationMessages([
                'required' => 'Debe seleccionar alguna de las opciones'
              ]),
            TextInput::make('ref')
              ->label('Referencia')
              ->type('number')
              ->numeric()
              ->integer()
              ->step(0000)
              ->placeholder('Ej: 0001')
              ->rules(['sometimes', 'min:3', 'max:25', 'regex:/^[a-zA-Z\s]+$/'])
              ->validationAttribute('nombre')
              ->validationMessages([
                'regex' => 'Solo se aceptan letras',
                'min' => 'Debe contener al menos :min caracteres',
                'max' => 'Debe contener máximo :max caracteres',
              ]),
          ])
      ])
      ->action(function (Lottery $record, array $data) {
        $total_payed = $data['total_payed'] ?? 0;
        $ticket = Ticket::find($data['ticket_id']);
        $ref = $data['ref'] ?? 0;
        $type = $data['type'] ?? 0;
        $client_name = $ticket->client->fullName;

        $ticket->payment()->create([
          'amount' => $total_payed,
          'ref' => $ref,
          'type' => $type
        ]);

        $payment_types = [
          'usd' => 'dólares efectivo',
          'bs' => 'bolìvares efectivo',
          'payment' => 'pago mòvil',
          'other' => 'otros'
        ];

        Notification::make()
          ->title('Pago registrado')
          ->body(Str::markdown("Se ha registrado el pago del boleto **{$ticket->number}** de la rifa **{$record->name}** para el cliente **{$client_name}**"))
          ->success()
          ->send();
      });
  }
}
