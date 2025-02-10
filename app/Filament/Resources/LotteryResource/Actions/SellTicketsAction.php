<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use App\Filament\Traits\HasActivityLogger;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\CheckboxList;
use Filament\Tables\Actions\Action;
use App\Models\Client;
use App\Models\Currency;
use App\Models\Ticket;
use App\Models\Lottery;
use Carbon\Carbon;
use Closure;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Hidden;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Split;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
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
				fn(Lottery $record) => (count($record->getWinners()) > 0 ? true : null) ||
					($record->getRemainingTickets()->count() === 0) ||
					(empty($record->totalLeft)) ||
					(strtotime(now()->format('Y-m-d')) >
						strtotime(Carbon::createFromFormat('d/m/Y', $record->final_date)->format('Y-m-d'))) ? true : null

			)
			->modalHeading(fn(Lottery $record) => "Venta de boletos para rifa #{$record->id} ({$record->name})")
			->form([
				Select::make('client_id')
					->label('Cliente')
					->options(Client::query()->select([
						'id',
						DB::raw("(name || ' ' || last_name) as client_name"),
					])->pluck('client_name', 'id'))
					->markAsRequired()
					->rules(['required'])
					->validationMessages([
						'required' => 'Debe seleccionar un cliente'
					])
					->searchable()
					->createOptionForm([
						Split::make([
							TextInput::make('name')
								->label('Nombre')
								->placeholder('José')
								->markAsRequired()
								->rules(['required', 'string', 'min:3', 'max:15', 'regex:/^[a-zA-Z\s]+$/'])
								->validationMessages([
									'required' => 'Debe indicar el nombre',
									'string' => 'El nombre debe ser una cadena de texto',
									'min' => 'El nombre debe tener al menos :min caracteres',
									'max' => 'El nombre no debe tener más de :max caracteres',
									'regex' => 'El nombre solo puede contener letras y espacios'
								]),
							TextInput::make('last_name')
								->label('Apellido')
								->placeholder('Marcos')
								->markAsRequired()
								->rules(['required', 'string', 'min:3', 'max:15', 'regex:/^[a-zA-Z\s]+$/'])
								->validationMessages([
									'required' => 'Debe indicar el apellido',
									'string' => 'El apellido debe ser una cadena de texto',
									'min' => 'El apellido debe tener al menos :min caracteres',
									'max' => 'El apellido no debe tener más de :max caracteres',
									'regex' => 'El apellido solo puede contener letras y espacios'
								]),
						]),
						Split::make([
							Select::make('doc_type')
								->label('Nacionalidad')
								->options(['V' => 'V', 'E' => 'E', 'J' => 'J', 'G' => 'G'])
								->placeholder('Selecciona una opción')
								->markAsRequired()
								->rules(['required'])
								->in(['V', 'E', 'J', 'G'])
								->validationMessages([
									'required' => "Debe seleccionar una opción",
									'in' => "Debe seleccionar una de las opciones",
								]),
							TextInput::make('doc')
								->label('Documento')
								->type('number')
								->placeholder('12451248')
								->markAsRequired()
								->rules(['required', 'min_digits:6', 'max_digits:9',  'numeric'])
								->unique(ignoreRecord: true)
								->validationMessages([
									'required' => 'Debe indicar la cédula',
									'min_digits' => 'Debe contener al menos :min dígitos',
									'max_digits' => 'Debe contener máximo :max dígitos',
									'numeric' => 'Deben ser números',
									'unique' => 'Esta cédula se encuentra registrada',
								]),
						]),
						Split::make([
							Select::make('code')
								->label('Código')
								->options([
									'0412' => '0412',
									'0414' => '0414',
									'0416' => '0416',
									'0424' => '0424',
									'0426' => '0426'
								])
								->placeholder('Selecciona una opción')
								->markAsRequired()
								->rules(['required'])
								->in(['0412', '0414', '0416', '0424', '0426'])
								->validationMessages([
									'required' => "Debe seleccionar una opción",
									'in' => "Debe seleccionar una de las opciones",
								]),
							TextInput::make('phone')
								->label('Teléfono')
								->type('number')
								->placeholder('4561278')
								->markAsRequired()
								->rules(['required', 'digits:7', 'numeric'])
								->unique(ignoreRecord: true)
								->validationMessages([
									'required' => 'Debe indicar un número de teléfono',
									'digits' => 'Debe tener :digits dígitos',
									'numeric' => 'Deben ser números',
									'unique' => 'Este teléfono se encuentra registrado',
								]),
						])
					])
					->createOptionUsing(function (array $data) {
						return Client::create($data)->id;
					}),
				CheckboxList::make('tickets')
					->label(function (Lottery $record, Get $get) {
						$available_tickets = $record->tickets->where('client_id', null)->pluck('id')->toArray();
						$selected_tickets = count(array_intersect($available_tickets, $get('tickets')));

						return $selected_tickets > 0
							? "Boletos (seleccionados: {$selected_tickets})"
							: 'Boletos';
					})
					->options(function (Lottery $record) {
						$tickets = $record->tickets()->with('client')->select('number', 'id', 'client_id', 'ticket_price')->get(); // Select ticket_price here
						$ticket_price = $record->ticket_price; // Get ticket price once outside the loop

						$formatted_tickets = $tickets->mapWithKeys(function ($ticket) use ($ticket_price) {
							$ticket_payed_rounded = round($ticket->total_payed, 2); // Calculate rounded value once
							$paymentStatus = '';

							if ($ticket_payed_rounded === 0) {
								$paymentStatus = 'Pendiente';
							} elseif ($ticket_payed_rounded === $ticket_price) {
								$paymentStatus = "{$ticket_payed_rounded} $";
							} else {
								$paymentStatus = "{$ticket_payed_rounded} $ / {$ticket_price} $";
							}

							$client_name = $ticket->client ? "{$ticket->client->full_name} ({$paymentStatus})" : "Disponible {$ticket_price}$";

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
						$available_tickets = $record->tickets->where('client_id', null)->pluck('id')->toArray();
						$selected_tickets = count(array_intersect($available_tickets, $get('tickets')));

						$calc = round(($record->total_price / $record->tickets->count()) * $selected_tickets, 2);

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
						Hidden::make('total_cost')
							->default(0),
						Placeholder::make('total_cost_usd')
							->label('Monto a pagar')
							->content(function (Lottery $record, Get $get) {
								$available_tickets = $record->tickets->where('client_id', null)->pluck('id')->toArray();
								$selected_tickets = count(value: array_intersect($available_tickets, $get('tickets')));

								if (empty($record->total_price)) {
									return '0$';
								}

								$value = round(($record->total_price / $record->tickets->count()) * $selected_tickets, 2);

								return "{$value}$";
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
				Repeater::make('payments')
					->label('Pagos')
					->rules([
						fn(Get $get): Closure => function (string $attribute, $value, Closure $fail) use ($get) {
							$currency = Currency::latest()->first();
							$payments = collect($get('payments'));
							$changes = collect($get('changes') ?? []);
							$total_cost = $get('total_cost');

							$total_payed = round($payments->reduce(function ($total, $payment) {
								if ($payment['type'] === null) return $total;

								$total += in_array($payment['type'], ['bs', 'payment'])
									? (float) $payment['total_payed'] / $payment['currency']
									: (float) $payment['total_payed'];

								return $total;
							}, 0), 2);
							$total_change = round($changes->reduce(function ($total, $change) {
								if ($change['type'] === null) return $total;

								$total += in_array($change['type'], ['bs', 'payment'])
									? (float) $change['total_payed'] / $change['currency']
									: (float) $change['total_payed'];

								return $total;
							}, 0), 2);

							$total_cost += $total_change;
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
					->afterStateUpdated(function (Get $get, Set $set) {
						$payments = collect($get('payments') ?? []);
						$changes = collect($get('changes') ?? []);
						$total_cost = $get('total_cost');

						$total_change = round(
							$changes->reduce(function ($total, $change) {
								if ($change['type'] === null) return $total;

								$total += in_array($change['type'], ['bs', 'payment'])
									? (float) $change['total_payed'] / $change['currency']
									: (float) $change['total_payed'];

								return $total;
							}, 0),
							2
						);
						$total_payed = round(
							$payments->reduce(function ($total, $payment) {
								if ($payment['type'] === null) return $total;

								$total += in_array($payment['type'], ['bs', 'payment'])
									? (float) $payment['total_payed'] / $payment['currency']
									: (float) $payment['total_payed'];

								return $total;
							}, 0),
							2
						);

						$rest = $total_cost - $total_payed + $total_change;
						$rest = abs($rest);
						$set('total_change', $rest);
					})
					->columns(3)
					->hidden(function (Lottery $record, Get $get) {
						$tickets = $get('tickets') ?? [];
						$tickets_available = count(array_intersect($record->tickets_left(), $tickets));

						return $tickets_available === 0 || empty($get('add_payment'));
					}),
				Repeater::make('changes')
					->label('Cambios')
					->live()
					->hidden(function (Get $get) {
						$payments = collect($get('payments') ?? []);
						$changes = collect($get('changes') ?? []);
						$total_cost = $get('total_cost_usd');

						$total_change = round(
							$changes->reduce(function ($total, $change) {
								if (!is_array($change)) { // Check if $change is an array
									return $total; // Skip if not an array
								}
								if (!isset($change['type'])) { // Check if 'type' key exists
									return $total; // Skip if 'type' key is missing
								}

								if ($change['type'] === null) return $total;

								$total += in_array($change['type'], ['bs', 'payment'])
									? $change['total_payed'] / $change['currency']
									: $change['total_payed'];

								return $total;
							}, 0),
							2
						);
						$total_payed = round(
							$payments->reduce(function ($total, $payment) {
								if ($payment['type'] === null) return $total;

								$total += in_array($payment['type'], ['bs', 'payment'])
									? (float) $payment['total_payed'] / $payment['currency']
									: (float) $payment['total_payed'];

								return $total;
							}, 0),
							2
						);

						return $total_payed == $total_cost && $total_change == 0;
					})
					->afterStateUpdated(function (Get $get, Set $set) {
						$payments = collect($get('payments') ?? []);
						$changes = collect($get('changes') ?? []);
						$total_cost = $get('total_cost');

						$total_change = round(
							$changes->reduce(function ($total, $change) {
								if ($change['type'] === null) return $total;

								$total += in_array($change['type'], ['bs', 'payment'])
									? (float) $change['total_payed'] / $change['currency']
									: (float) $change['total_payed'];

								return $total;
							}, 0),
							2
						);
						$total_payed = round(
							$payments->reduce(function ($total, $payment) {
								if ($payment['type'] === null) return $total;

								$total += in_array($payment['type'], ['bs', 'payment'])
									? (float) $payment['total_payed'] / $payment['currency']
									: (float) $payment['total_payed'];

								return $total;
							}, 0),
							2
						);

						$rest = $total_cost - $total_payed + $total_change;
						$rest = abs($rest);
						$set('total_change', $rest);
					})
					->columns(3)
					->maxItems(
						fn(Get $get) =>
						$get('total_change') > 0
							? count($get('payments') ?? [])
							: null
					)
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
					->nullable(),
				Fieldset::make()
					->schema([
						TextInput::make('total_change')
							->label('Vuelto total')
							->default(0)
							->suffix(' $')
							->readOnly(),
						Placeholder::make('message')
							->label('Mensaje')
							->content(function (Get $get) {
								$payments = collect($get('payments') ?? []);
								$changes = collect($get('changes') ?? []);
								$total_cost = $get('total_cost_usd');

								$total_change = round(
									$changes->reduce(function ($total, $change) {
										if ($change['type'] === null) return $total;

										$total += in_array($change['type'], ['bs', 'payment'])
											? $change['total_payed'] / $change['currency']
											: $change['total_payed'];

										return $total;
									}, 0),
									2
								);
								$total_payed = round(
									$payments->reduce(function ($total, $payment) {
										if ($payment['type'] === null) return $total;

										$total += in_array($payment['type'], ['bs', 'payment'])
											? (float) $payment['total_payed'] / $payment['currency']
											: (float) $payment['total_payed'];

										return $total;
									}, 0),
									2
								);

								if ($total_cost - $total_payed + $total_change == 0) return '';

								return $total_cost > $total_payed + $total_change
									? 'Debe'
									: 'A devolver';
							})
							->live()
					])
					->columns(2),
				Placeholder::make('note')
					->label('Nota')
					->content(function (Get $get) {
						$content = [
							'El monto pagado será desglozado en cada boleto por órden numérico.',
							'Si no añade un pago los boletos quedarán pendientes, se pueden pagar a través de la opción (pagar boletos)',
							$get('add_payment') ? '' : 'Debe agregar los pagos de los respectivos boletos posteriormente a través de la opción (pago de boleto)'
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
				$currency = Currency::latest()->first();
				$client = Client::find($data['client_id']);
				$available_tickets = $record->tickets->where('client_id', null)->pluck('id')->toArray();
				$selected_tickets = array_intersect($available_tickets, $data['tickets']);

				$client_name = $client->full_name;
				$tickets = Ticket::findMany($selected_tickets);
				$client->tickets()->saveMany($tickets);
				$selected_tickets_count = count($selected_tickets);
				$ticket_price = $record->ticket_price;
				$total_cost = $ticket_price * $selected_tickets_count;
				$payments = collect($data['payments'] ?? []);
				$changes = collect($data['changes'] ?? []); // Get changes from the form data
				$ticket_index = 0;
				$total_paid_usd = 0;
				$total_change_usd = 0;

				Notification::make()
					->title('Boletos vendidos')
					->body(Str::markdown("Se ha vendido **{$selected_tickets_count}** boletos al cliente **{$client_name}** en la rifa de **{$record->name}**"))
					->success()
					->send();

				if ($payments->isNotEmpty()) {
					$payment_index = 0;
					$current_payment = $payments[$payment_index];

					while ($ticket_index < count($tickets) && $payment_index < count($payments)) {
						$ticket = $tickets[$ticket_index];
						$amount_usd = in_array($current_payment['type'], ['bs', 'payment'])
							? $current_payment['total_payed'] / $currency->value
							: $current_payment['total_payed'];

						$payment_amount_for_ticket = min($ticket_price, $amount_usd);

						$ticket->payments()->create([
							'amount' => $payment_amount_for_ticket,
							'type' => $current_payment['type'],
							'ref' => $current_payment['ref'] ?? null,
							'currency_id' => $currency->id,
						]);

						$amount_usd -= $payment_amount_for_ticket;

						if ($payment_amount_for_ticket == $ticket_price) {
							$ticket_index++;
						}

						if ($amount_usd === 0) {
							$payment_index++;
							if ($payment_index < count($payments)) {
								$current_payment = $payments[$payment_index];
							}
						}

						//Last ticket and remaining amount, apply it all to the LAST PAYMENT RECORD for that ticket
						if ($ticket_index === count($tickets) && $amount_usd > 0) {
							$lastPayment = $ticket->payments()->orderBy('created_at', 'desc')->first(); // Get the last payment

							if ($lastPayment) { // Ensure there is payment record
								$lastPayment->update([ // Update the last payment record
									'amount' => $amount_usd,
								]);
								$amount_usd = 0; // Ensure no further processing of this amount
							} else {
								$ticket->payments()->create([ //If there is no payment record, create one
									'amount' => $amount_usd,
									'type' => $current_payment['type'], // Or 'change' if you have a change type
									'ref' => $current_payment['ref'] ?? null,  // Or a separate change ref
									'currency_id' => $currency->id,
								]);
								$amount_usd = 0; // Ensure no further processing of this amount
							}
						}
					}


					foreach ($changes as $change) {
						$lastTicket = $tickets->last();
						$amount_usd = in_array($change['type'], ['bs', 'payment'])
							? $change['total_payed'] / $currency->value
							: $change['total_payed'];

						$total_change_usd += $amount_usd;

						$lastTicket->changesM()->create([ // Corrected relationship name
							'amount' => $change['total_payed'],
							'type' => $change['type'],
							'ref' => $change['ref'],
							'currency_id' => $currency->id,
							'confirmed' => true
						]);
					}

					$total_paid_usd = round(
						$payments->reduce(function ($total, $payment) {
							if ($payment['type'] === null) return $total;

							$total += in_array($payment['type'], ['bs', 'payment'])
								? (float) $payment['total_payed'] / $payment['currency']
								: (float) $payment['total_payed'];

							return $total;
						}, 0),
						2
					);
					$total_change_usd = round($total_change_usd, 2);

					Notification::make()
						->title('Pagos registrados')
						->body(Str::markdown("Se ha registrado el pago de **{$selected_tickets_count}** boletos bajo el monto de **{$total_paid_usd} $ en total** y se ha entregado un cambio de **{$total_change_usd} $**"))
						->success()
						->send();
				} else {
					Notification::make()
						->title('Pagos pendientes')
						->body(Str::markdown("Se ha registrado **{$selected_tickets_count}** boletos **pendientes por pagar** equivalentes a ({$total_cost}$)"))
						->warning()
						->send();
				}

				HasActivityLogger::logActivity($record, 'sell_tickets', 'create', [
					'tickets' => $selected_tickets_count,
					'ticket_price' => $ticket_price,
					'payments' => $payments,
					'currency' => $currency,
					'client' => $client->toArray(),
					'changes' => $changes,
				]);
			});
	}
}
