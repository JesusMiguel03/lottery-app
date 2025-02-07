<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use App\Filament\Traits\HasActivityLogger;
use App\Models\Currency;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Tables\Actions\Action;
use App\Models\Ticket;
use App\Models\Lottery;
use Closure;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
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
          ->live()
          ->afterStateUpdated(function (Lottery $record, Get $get, Set $set) {
            $selected_ticket = $get('ticket_id');
            $ticket_total_payed = Ticket::find($selected_ticket)->total_payed ?? 0;
            $total_left = round($record->ticket_price - $ticket_total_payed, 2);
            $currency = Currency::latest()->first()->value;
            $total_left_bs = $total_left * $currency;

            $set('total_cost', "{$total_left}$ o {$total_left_bs} Bs");
            $set('total_cost_usd', $total_left);
          })
          ->options(fn(Lottery $record) => $record->not_payed_tickets())
          ->markAsRequired()
          ->rules(['required'])
          ->validationMessages([
            'required' => 'Debe seleccionar un boleto'
          ]),
        Placeholder::make('total_cost')
          ->label('Monto a pagar')
          ->content(function (Lottery $record, Get $get) {
            $selected_ticket = $get('ticket_id');
            $ticket_total_payed = Ticket::find($selected_ticket)->total_payed ?? 0;
            $total_left = round($record->ticket_price - $ticket_total_payed, 2);
            $currency = Currency::latest()->first()->value;
            $total_left_bs = $total_left * $currency;
            return "{$total_left}$ o {$total_left_bs} Bs";
          }),
        Placeholder::make('total_cost_usd')
          ->label('Monto a pagar')
          ->hidden()
          ->content(function (Lottery $record, Get $get) {
            $selected_ticket = $get('ticket_id');
            $ticket_total_payed = Ticket::find($selected_ticket)->total_payed ?? 0;
            $total_left = round($record->ticket_price - $ticket_total_payed, 2);
            return $total_left;
          }),
        Repeater::make('payments')
          ->label('Pagos')
          ->rules([
            fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
              $currency = Currency::latest()->first();
              $payments = collect($get('payments'));
              $total_cost = $get('total_cost_usd');

              $total_payed = round($payments->reduce(function ($total, $payment) {
                if ($payment['type'] === null) return $total;

                $total += in_array($payment['type'], ['bs', 'payment'])
                  ? $payment['total_payed'] / $payment['currency']
                  : $payment['total_payed'];

                return $total;
              }, 0), 2);

              $total_cost_bs = round($total_cost * $currency->value, 2);
              $diff = round(abs($total_payed - $total_cost), 2);
              $diff_bs = round($diff * $currency->value, 2);

              if ($total_payed == $total_cost) return false;
              if ($total_payed > $total_cost) {
                return $fail("El monto pagado es mayor al coste total por ({$diff}$) o ({$diff_bs} Bs). Monto válido: ({$total_cost}$) o ({$total_cost_bs} Bs)");
              }
            }
          ])
          ->schema([
            TextInput::make('total_payed')
              ->label('Monto pagado')
              ->placeholder('Ej: 100')
              ->type('number')
              ->numeric()
              ->step(0.01)
              ->minValue(0.01)
              ->rules([
                'required',
                'min:0.01',
                'max:10000',
              ])
              ->validationMessages([
                'required' => "Debe indicar un número",
                'min' => "Debe ser al menos :min",
                'max' => "Debe ser máximo :max",
              ])
              ->live()
              ->afterStateUpdated(function (Get $get, Set $set) {
                $type = $get('type');

                if (in_array($type, ['bs', 'payment'])) {
                  $total_payed = floatval($get('total_payed'));
                  $currency = $get('currency');
                  $total_cost = $get('total_cost');
                  $set('equivalent', round($total_payed / $currency, 2));
                  $set('equivalent_bs', round($total_cost * $currency, 2));
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
              ->afterStateUpdated(function (Get $get, Set $set) {
                $type = $get('type');
                $total_payed = floatval($get('total_payed'));

                if (in_array($type, ['bs', 'payment'])) {
                  $total_cost = $get('total_cost');
                  $currency = Currency::latest()->first()->value;
                  $set('equivalent', round($total_payed / $currency, 2));
                  $set('equivalent_bs', round($total_cost * $currency, 2));
                }
              }),
            TextInput::make('ref')
              ->label('Referencia')
              ->type('number')
              ->numeric()
              ->step(0000)
              ->placeholder('Ej: 0001')
              ->rules(['sometimes', 'digits:4', 'regex:/^[0-9]+$/'])
              ->validationAttribute('nombre')
              ->validationMessages([
                'regex' => 'Solo se aceptan números',
                'digits' => 'Debe contener los 4 dígitos'
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
                  ->default(function (Get $get) {
                    $currency = $get('currency');
                    $total_cost = $get('total_cost');
                    $calc = round($currency * $total_cost, 2);

                    return $calc;
                  })
                  ->disabled()
              ])->columns(3)
              ->hidden(fn(Get $get) => !in_array($get('type'), ['payment', 'bs']))
          ])
          ->live()
          ->columns(3)
      ])
      ->action(function (Lottery $record, array $data) {
        $ticket = Ticket::find($data['ticket_id']);
        $client_name = $ticket->client->full_name;
        $ticket_price = $record->ticket_price;
        $payments = collect($data['payments']);
        $currency = Currency::latest()->first();

        DB::beginTransaction();

        try {
          $ticket_payment_total = 0;
          $total_payed_calculated = 0;

          foreach ($payments as $payment) {
            if ($payment['type'] === null) continue; // Skip null payment types

            $payment_amount_original = $payment['total_payed'];
            $payment_amount_usd = $payment['type'] === 'bs' || $payment['type'] === 'payment'
              ? $payment_amount_original / $currency->value
              : $payment_amount_original;

            $amount_to_apply_usd = min($payment_amount_usd, $ticket_price - $ticket_payment_total);
            $amount_to_apply_original = ($payment['type'] === 'bs' || $payment['type'] === 'payment')
              ? $amount_to_apply_usd * $currency->value
              : $amount_to_apply_usd;

            if ($amount_to_apply_original > 0) { // Only process if the amount is positive
              $ticket->payments()->create([
                'amount' => $amount_to_apply_original,
                'ref' => $payment['ref'] ?? '',
                'type' => $payment['type'],
                'currency_id' => $currency->id,
              ]);

              $ticket_payment_total += $amount_to_apply_usd;
              $total_payed_calculated += $amount_to_apply_original; // Correctly accumulate original amounts
            }

            if ($ticket_payment_total >= $ticket_price) {
              break; // Ticket is fully paid, exit the loop
            }
          }

          DB::commit();

          HasActivityLogger::logActivity($record, 'ticket_payment', 'update', [
            'total_payed' => $total_payed_calculated,
            'ticket' => $ticket,
            'payments' => $payments,
            'client' => $ticket->client,
          ]);

          Notification::make()
            ->title('Pago registrado')
            ->body(Str::markdown("Se ha registrado el pago del boleto **{$ticket->number}** de la rifa **{$record->name}** para el cliente **{$client_name}** bajo el monto de **{$total_payed_calculated} $ en total**"))
            ->success()
            ->send();
        } catch (\Exception $e) {
          DB::rollBack();

          $logFilePath = public_path('logs/error_log.txt');

          if (!File::exists(public_path('logs'))) {
            File::makeDirectory(public_path('logs'), 0755, true);
          }
          File::append($logFilePath, now() . ' - ' . "[TicketPaymentAction]" . ' ' . $e->getMessage() . PHP_EOL);
        }
      });
  }
}
