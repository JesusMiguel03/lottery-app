<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Actions\Action;
use App\Models\Client;
use App\Models\Ticket;
use App\Models\Lottery;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\HtmlString;
use Illuminate\Support\Str;

class SellTicketsAction extends Action
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->name('SellTickets');

    $this->label("Venta de boletos")
      ->icon('heroicon-o-tag')
      ->slideOver()
      ->hidden(
        fn(Lottery $record) => (count($record->get_winners()) > 0 ? true : null) || (empty($record->totalLeft) ? true : null)
      )
      ->modalHeading(fn(Lottery $record) => "Venta de boletos para rifa #{$record->id} ({$record->name})")
      ->form([
        Select::make('client_id')
          ->label('Cliente')
          ->options(Client::query()->pluck('name', 'id'))
          ->markAsRequired()
          ->rules(['required'])
          ->validationMessages([
            'required' => 'Debe seleccionar un cliente'
          ])
          ->searchable(),
        CheckboxList::make('tickets')
          ->label('Boletos')
          ->options(function (Lottery $record) {
            $tickets = $record->tickets()->with('client')->select('number', 'id', 'client_id')->get();

            $ticket_price = empty($record->total_price) ? 0 : $record->total_price / $record->tickets->count();
            $formatted_tickets = $tickets->mapWithKeys(function ($ticket) use ($ticket_price) {
              $client_name = empty($ticket->client) ? "Disponible {$ticket_price}$" : $ticket->client->fullName;
              return [
                $ticket->id => "{$ticket->number} - {$client_name}"
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
          ->afterStateUpdated(function (Lottery $record, Get $get, Set $set) {
            $selected_tickets = $get('tickets');
            $calc = ($record->total_price / $record->tickets->count()) * count($selected_tickets);
            $set('total_cost', $calc);
          })
          ->live()
          ->disableOptionWhen(fn(Lottery $record, string $value) => in_array($value, $record->tickets->where('client_id', '!=', null)->pluck('id')->toArray()))
          ->columns(3)
          ->rules(['required'])
          ->validationMessages([
            'required' => 'Debe seleccionar al menos un boleto'
          ])
          ->searchable()
          ->markAsRequired()
          ->bulkToggleable(),
        Toggle::make('add_payment')
          ->label('Realizar pago')
          ->live(),
        Fieldset::make('')
          ->schema([
            Placeholder::make('total_cost')
              ->label('Monto a pagar')
              ->content(function (Lottery $record, Get $get) {
                $available_tickets = $record->tickets->where('client_id', null)->pluck('id')->toArray();
                $selected_tickets = count(array_intersect($available_tickets, $get('tickets')));

                if (empty($record->total_price)) {
                  return '0$';
                }

                return number_format(($record->total_price / $record->tickets->count())  * $selected_tickets) . '$';
              }),
            Placeholder::make('total_payed_amount')
              ->label('Monto pagado')
              ->content(function (Lottery $record) {
                return "{$record->totalPayed}$";
              }),
            Placeholder::make('total_left_amount')
              ->label('Monto restante')
              ->content(function (Lottery $record) {
                return "{$record->totalLeft}$";
              }),
          ])->columns(3),
        Fieldset::make('Pagos')
          ->schema([
            TextInput::make('total_payed')
              ->label('Monto pagado')
              ->placeholder('Ej: 100')
              ->type('number')
              ->numeric()
              ->integer()
              ->step(0.01)
              ->minValue(0.01)
              ->rules(['required', 'min:0.01', 'max:10000'])
              ->validationMessages([
                'required' => "Debe indicar un número",
                'min' => "Debe ser al menos :min",
                'max' => "Debe ser máximo :max",
              ])
              ->live()
              ->helperText('Indique el monto que está pagando el cliente por los boletos'),
            Select::make('type')
              ->label('Tipo de pago')
              ->options([
                'usd' => 'Dólares efectivo',
                'bs' => 'Bolìvares efectivo',
                'payment' => 'Pago mòvil',
                'other' => 'Otros'
              ])
              ->rules(['required', 'in:usd,bs,payment,other'])
              ->validationMessages([
                'required' => 'Debe seleccionar alguna de las opciones',
                'in' => 'Debe seleccionar una opción válida'
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
          ->hidden(function (Lottery $record, Get $get) {
            $tickets = $get('tickets') ?? [];
            $tickets_available = count(array_intersect($record->tickets_left(), $tickets));

            return $tickets_available === 0 || empty($get('add_payment'));
          }),
        Placeholder::make('note')
          ->label('Nota')
          ->content(new HtmlString(
            "<ul>
                                        <li>El monto pagado será desglozado en cada boleto por órden numérico.</li>
                                        <li>Si no añade un pago los boletos quedarán pendientes, se pueden pagar a través de la opción (pagar boletos)</li>
                                    </ul>"
          )),
      ])
      ->modalSubmitAction(fn(Lottery $record) => count($record->tickets_left()) >= 1 ? null : false)
      ->action(function (Lottery $record, array $data) {
        $client = Client::find($data['client_id']);
        $available_tickets = $record->tickets->where('client_id', null)->pluck('id')->toArray();

        $selected_tickets = array_intersect($available_tickets, $data['tickets']);
        $tickets = Ticket::findMany($selected_tickets);
        $client->tickets()->saveMany($tickets);

        $total_payed = $data['total_payed'] ?? 0;
        $ref = $data['ref'] ?? 0;
        $type = $data['type'] ?? 0;
        $ticket_price = $record->ticket_price();
        $has_payments = $total_payed > 0;

        $total_tickets = count($selected_tickets);
        $client_name = $client->fullName;

        Notification::make()
          ->title('Boletos vendidos')
          ->body(Str::markdown("Se ha vendido **{$total_tickets}** boletos al cliente **{$client_name}** en la rifa de **{$record->name}**"))
          ->success()
          ->send();

        $current_total_payed_amount = $total_payed;

        $tickets->map(function (Ticket $ticket) use (&$current_total_payed_amount, $ticket_price, $ref, $type) {
          if ($current_total_payed_amount >= $ticket_price) {
            $current_total_payed_amount -= $ticket_price;

            $ticket->payment()->create([
              'amount' => $ticket_price,
              'ref' => $ref,
              'type' => $type
            ]);
          }
        });


        if ($has_payments) {
          $payment_types = [
            'usd' => 'dólares efectivo',
            'bs' => 'bolìvares efectivo',
            'payment' => 'pago mòvil',
            'other' => 'otros'
          ];

          Notification::make()
            ->title('Pagos registrados')
            ->body(Str::markdown("Se ha registrado el pago de **{$total_tickets}** boletos bajo el monto de **{$total_payed} {$payment_types[$type]}**"))
            ->success()
            ->send();
        }
      });
  }
}
