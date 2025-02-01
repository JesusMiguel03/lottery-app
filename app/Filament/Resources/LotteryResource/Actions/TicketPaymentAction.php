<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use App\Filament\Traits\HasActivityLogger;
use App\Models\Currency;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\Action;
use App\Models\Ticket;
use App\Models\Lottery;
use Closure;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
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
      ->hidden(fn(Lottery $record) => (count($record->getWinners()) > 0) ||
        (now()->format('d/m/Y') > $record->final_date) ||
        count($record->not_payed_tickets()) === 0
        ? true
        : null)
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
          ->content(function (Lottery $record) {
            $currency = Currency::latest()->first()->value;
            $ticket_price = $record->ticket_price();
            $ticket_price_bs = $ticket_price * $currency;
            return "{$ticket_price}$ o {$ticket_price_bs} Bs";
          }),
        Fieldset::make('Pagos')
          ->schema([
            TextInput::make('total_payed')
              ->label('Monto pagado')
              ->placeholder('Ej: 100')
              ->type('number')
              ->numeric()
              ->live()
              ->step(0.01)
              ->minValue(0.01)
              ->rules([
                'required',
                'min:0.01',
                'max:10000',
                fn(Lottery $record, Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($record, $get) {
                  $type = $get('type');

                  if (in_array($type, ['bs', 'payment'])) {
                    $total_payed = floatval($get('total_payed'));
                    $currency = $get('currency') ?? Currency::latest()->first()->value;
                    $currency = $get('currency');
                    $ticket_price = $record->ticket_price();

                    $total_cost = $ticket_price;
                    $total_cost_bs = $total_cost * $currency;
                    $total_payed = round($total_payed / $currency, 2);

                    if ($total_payed === $total_cost) {
                      return false;
                    }

                    $diff = $total_payed > $total_cost
                      ? $total_payed - $total_cost
                      : $total_cost - $total_payed;
                    $diff_bs = $diff * $currency;


                    $total_payed > $total_cost
                      ? $fail("El monto pagado es mayor al coste total por ({$diff}$) o ({$diff_bs} Bs). Monto válido: ({$total_cost}$) o ({$total_cost_bs} Bs)")
                      : $fail("El monto pagado es menor al coste total por ({$diff}$) o ({$diff_bs} Bs). Monto válido: ({$total_cost}$) o ({$total_cost_bs} Bs)");
                  }
                }
              ])
              ->validationMessages([
                'required' => "Debe indicar un número",
                'min' => "Debe ser al menos :min",
                'max' => "Debe ser máximo :max",
              ])
              ->live()
              ->afterStateUpdated(function (Lottery $record, Get $get, Set $set) {
                $type = $get('type');

                if (in_array($type, ['bs', 'payment'])) {
                  $total_payed = floatval($get('total_payed'));
                  $currency = $get('currency');
                  $ticket_price = $record->ticket_price();
                  $set('equivalent', round($total_payed / $currency, 2));
                  $set('equivalent_bs', round($ticket_price * $currency, 2));
                }
              })
              ->helperText('Indique el monto que está pagando el cliente por los boletos'),
            Select::make('type')
              ->label('Tipo de pago')
              ->options([
                'usd' => 'Dólares efectivo',
                'bs' => 'Bolìvares efectivo',
                'payment' => 'Pago mòvil',
                'other' => 'Otros, divisas'
              ])
              ->live()
              ->rules(['required', 'in:usd,bs,payment,other'])
              ->validationMessages([
                'required' => 'Debe seleccionar alguna de las opciones',
                'in' => 'Debe seleccionar una opción válida'
              ])
              ->afterStateUpdated(function (Lottery $record, Get $get, Set $set) {
                $type = $get('type');
                $total_payed = floatval($get('total_payed'));

                if (in_array($type, ['bs', 'payment'])) {
                  $currency = Currency::latest()->first()->value;
                  $ticket_price = $record->ticket_price();
                  $set('equivalent', round($total_payed / $currency, 2));
                  $set('equivalent_bs', round($ticket_price * $currency, 2));
                }
              }),
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
            Section::make()
              ->schema([
                TextInput::make('currency')
                  ->label('Tasa del día')
                  ->type('number')
                  ->numeric()
                  ->integer()
                  ->step(0000)
                  ->placeholder('Ej: 0001')
                  ->readOnly()
                  ->default(fn() => Currency::latest()->first()->value),
                TextInput::make('equivalent')
                  ->label('Equivalente en divisas')
                  ->type('number')
                  ->placeholder('Ej: 0001')
                  ->default(function (Get $get) {
                    $currency = $get('currency');
                    $total_payed = $get('total_payed');
                    $calc = round($currency * $total_payed, 2);

                    return $calc;
                  })
                  ->disabled(),
                TextInput::make('equivalent_bs')
                  ->label('Monto a pagar en bs')
                  ->type('number')
                  ->placeholder('Ej: 0001')
                  ->default(function (Lottery $record, Get $get) {
                    $currency = $get('currency');
                    $ticket_price = $record->ticket_price();
                    $ticket_price_bs = $ticket_price * $currency;
                    $calc = round($currency * $ticket_price_bs, 2);

                    return $calc;
                  })
                  ->disabled()
              ])->columns(3)
              ->hidden(fn(Get $get) => !in_array($get('type'), ['payment', 'bs']))
          ])
      ])
      ->action(function (Lottery $record, array $data) {
        $total_payed = $data['total_payed'] ?? 0;
        $ticket = Ticket::find($data['ticket_id']);
        $ref = $data['ref'] ?? 0;
        $type = $data['type'] ?? 0;
        $client_name = $ticket->client->full_name;

        $currency_id = Currency::latest()->first()->id;
        $ticket->payment()->create([
          'amount' => $total_payed,
          'ref' => $ref,
          'type' => $type,
          'currency_id' => $currency_id
        ]);

        $payment_types = [
          'usd' => 'dólares efectivo',
          'bs' => 'bolìvares efectivo',
          'payment' => 'pago mòvil',
          'other' => 'otros'
        ];

        HasActivityLogger::logActivity($record, 'ticket_payment', 'update', [
          'total_payed' => $total_payed,
          'ticket' => $ticket,
          'ref' => $ref,
          'type' => $type,
          'client' => $ticket->client,
          'currency' => $currency_id
        ]);

        Notification::make()
          ->title('Pago registrado')
          ->body(Str::markdown("Se ha registrado el pago del boleto **{$ticket->number}** de la rifa **{$record->name}** para el cliente **{$client_name}** bajo el monto de **{$total_payed} {$payment_types[$type]}**"))
          ->success()
          ->send();
      });
  }
}
