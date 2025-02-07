<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LotteryResource\Actions\CancelTicketsAction;
use App\Filament\Resources\LotteryResource\Actions\NotifyDebtorClientsAction;
use App\Filament\Resources\LotteryResource\Actions\NotifyWinnerClientsAction;
use App\Filament\Resources\LotteryResource\Actions\PrizeModalAction;
use App\Filament\Resources\LotteryResource\Actions\RaffleAction;
use App\Filament\Resources\LotteryResource\Actions\SeeSoldTicketsAction;
use App\Filament\Resources\LotteryResource\Actions\SellTicketsAction;
use App\Filament\Resources\LotteryResource\Actions\TicketPaymentAction;
use App\Filament\Resources\LotteryResource\Pages;
use App\Filament\Traits\HasActivityLogger;
use App\Livewire\LotteryTicketsComponent;
use App\Models\Currency;
use App\Models\Lottery;
use Carbon\Carbon;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Fieldset;
use Filament\Forms\Components\Placeholder;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Infolists\Components\Livewire;
use Filament\Infolists\Components\Split;
use Filament\Infolists\Components\TextEntry;
use Filament\Infolists\Infolist;
use Filament\Resources\Resource;
use Filament\Support\Colors\Color;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Arr;

class LotteryResource extends Resource
{
    use InteractsWithActions, HasActivityLogger;

    protected static ?string $model = Lottery::class;

    protected static ?string $label = "Rifas";

    protected static ?int $navigationSort = 4;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nombre')
                    ->placeholder('Ej: Moto Bera')
                    ->autofocus()
                    ->rules(['required', 'min:3', 'max:25'])
                    ->markAsRequired()
                    ->regex('/^[a-zA-Z0-9\s]+$/')
                    ->columnSpanFull()
                    ->validationAttribute('nombre')
                    ->validationMessages([
                        'required' => 'Debe indicar un nombre',
                        'regex' => 'Solo se aceptan letras y números',
                        'min' => 'Debe contener al menos :min caracteres',
                        'max' => 'Debe contener máximo :max caracteres',
                    ]),
                Textarea::make('description')
                    ->label('Descripción')
                    ->placeholder('Ej: Sorteo de moto Bera SBR')
                    ->rules('required')
                    ->markAsRequired()
                    ->minLength(10)
                    ->maxLength(255)
                    ->regex('/^[a-zA-Z0-9\s]+$/')
                    ->rows(5)
                    ->columnSpanFull()
                    ->validationAttribute('descripción')
                    ->validationMessages([
                        'required' => 'Debe indicar una descripción',
                        'regex' => 'Solo se aceptan letras y números',
                        'min' => 'Debe contener al menos :min caracteres',
                        'max' => 'Debe contener máximo :max caracteres',
                    ]),
                TextInput::make('total_winners')
                    ->label('Máximo de ganadores')
                    ->placeholder('Ej: 5')
                    ->type('number')
                    ->numeric()
                    ->integer()
                    ->step(1)
                    ->rules('required')
                    ->markAsRequired()
                    ->live()
                    ->lt('total_tickets')
                    ->validationAttribute(label: 'máximo de ganadores')
                    ->validationMessages([
                        'required' => "Debe indicar un número",
                        'min' => "Debe ser al menos :min",
                        'max' => "Debe ser máximo :max",
                        'numeric' => "Debe ser un número",
                        'lt' => 'Debe ser menor al total de boletos'
                    ])
                    ->helperText('Coloque un número desde 1 hasta 100, si el número es mayor a 100 automáticamente se colocará el máximo de ganadores')
                    ->afterStateUpdated(function (Get $get, Set $set, $state) {
                        $value = $get('total_winners');
                        $max = 100;
                        $set('total_winners', $value > $max ? $max : $value);
                        $set('prizes', array_fill(0, $state, []));
                    })
                    ->hiddenOn('edit'),
                TextInput::make('total_tickets')
                    ->label('Boletos totales')
                    ->placeholder('Ej: 100')
                    ->type('number')
                    ->numeric()
                    ->integer()
                    ->step(1)
                    ->rules('required')
                    ->markAsRequired()
                    ->gt('total_winners')
                    ->validationAttribute(label: 'boletos totales')
                    ->validationMessages([
                        'required' => "Debe indicar un número",
                        'min' => "Debe ser al menos :min",
                        'max' => "Debe ser máximo :max",
                        'gt' => 'Debe ser mayor al máximo de ganadores'
                    ])
                    ->live()
                    ->helperText('Coloque un número desde 1 hasta 10.000, si el número es mayor a 10.000 automáticamente se colocará el máximo de boletos')
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $value = $get('total_tickets');
                        $max = 10000;
                        $set('total_tickets', $value > $max ? $max : $value);

                        $total_tickets = $get('total_tickets');
                        $value = empty($get('total_price')) ? 0 : $get('total_price');
                        $calc = $value / $total_tickets;
                        $set('ticket_price', $value > 0 ? $calc : 0);
                    })
                    ->hiddenOn('edit'),
                Repeater::make('prizes')
                    ->label('Premios')
                    ->live()
                    ->reactive()
                    ->schema([
                        Fieldset::make('Premio')
                            ->columns(3)
                            ->schema([
                                TextInput::make('name')
                                    ->label('Nombre')
                                    ->placeholder('Ej: Moto Bera')
                                    ->rules(['required', 'min:3', 'max:25', 'regex:/^[a-zA-Z\s]+$/'])
                                    ->markAsRequired()
                                    ->validationAttribute('nombre')
                                    ->validationMessages([
                                        'required' => 'Debe indicar un nombre',
                                        'regex' => 'Solo se aceptan letras',
                                        'min' => 'Debe contener al menos :min caracteres',
                                        'max' => 'Debe contener máximo :max caracteres',
                                    ]),
                                TextInput::make('quantity')
                                    ->label('Cantidad')
                                    ->placeholder('Ej: 1')
                                    ->type('number')
                                    ->numeric()
                                    ->integer()
                                    ->step(0.01)
                                    ->rules(['required', 'min:1', 'max:1000'])
                                    ->markAsRequired()
                                    ->validationMessages([
                                        'required' => "Debe indicar un número",
                                        'min' => "Debe ser al menos :min",
                                        'max' => "Debe ser máximo :max",
                                    ]),
                                TextInput::make('value')
                                    ->label('Valor')
                                    ->placeholder('Ej: 700')
                                    ->suffix('$')
                                    ->type('number')
                                    ->numeric()
                                    ->integer()
                                    ->step(0.01)
                                    ->rules(['required', 'min:1', 'max:1000'])
                                    ->markAsRequired()
                                    ->validationMessages([
                                        'required' => "Debe indicar un número",
                                        'min' => "Debe ser al menos :min",
                                        'max' => "Debe ser máximo :max",
                                    ])
                            ])
                    ])
                    ->columnSpanFull()
                    ->hidden(fn(Get $get) => empty($get('total_winners')))
                    ->maxItems(fn(Get $get) => $get('total_winners') ?? 0)
                    ->minItems(fn(Get $get) => $get('total_winners') ?? 0),
                Placeholder::make('prize_notes')
                    ->label('Notas')
                    ->content('Los premios se asignarán en el órden en que fueron definidos')
                    ->hidden(fn(Get $get) => empty($get('total_winners')))
                    ->columnSpanFull(),
                Fieldset::make('prizes')
                    ->label('Precios')
                    ->schema([
                        TextInput::make('total_price')
                            ->label('Precio total')
                            ->placeholder('Ej: 100$')
                            ->type('number')
                            ->numeric()
                            ->integer()
                            ->step(0.01)
                            ->rules('required')
                            ->markAsRequired()
                            ->minValue(0.01)
                            ->validationMessages([
                                'required' => "Debe indicar un número",
                                'min' => "Debe ser al menos :min",
                                'max' => "Debe ser máximo :max",
                            ])
                            ->live()
                            ->helperText('Indique el precio total de la rifa (este precio se dividirá entre el total de boletos)')
                            ->afterStateUpdated(function (Get $get, Set $set) {
                                $total_tickets = $get('total_tickets');
                                if (empty($total_tickets)) {
                                    $set('total_tickets', 1);
                                    $total_tickets = 1;
                                }

                                $value = empty($get('total_price')) ? 0 : $get('total_price');
                                $calc = $value / $total_tickets;

                                $currency = Currency::latest()->first();
                                if ($currency == null) {
                                    redirect(route('filament.admin.resources.clients.create'));
                                }

                                $set('ticket_price', $value > 0 ? $calc : 0);
                                $set('none', $value > 0 ? round($calc * $currency->value, 2) : 0);
                            })
                            ->hiddenOn('edit'),
                        TextInput::make('ticket_price')
                            ->label('Precio por boleto')
                            ->readOnly()
                            ->suffix('$')
                            ->placeholder('Ej: 100')
                            ->helperText('Este campo mostrará que valor tendrá cada boleto')
                            ->hiddenOn('edit'),
                        TextInput::make('none')
                            ->label('Precio por boleto')
                            ->disabled()
                            ->suffix('Bs')
                            ->placeholder('Ej: 100')
                            ->helperText('Este campo mostrará que valor tendrá cada boleto usando la tasa de hoy')
                            ->hiddenOn('edit'),
                    ])
                    ->columns(3),
                Fieldset::make('Duración')
                    ->schema([
                        DatePicker::make('initial_date')
                            ->label('Fecha de inicio')
                            ->placeholder('Ej: 21/12/2024')
                            ->displayFormat('d/m/Y')
                            ->format('d/m/Y')
                            ->closeOnDateSelection()
                            ->minDate(now()->startOfDay())
                            ->rules(['required'])
                            ->markAsRequired()
                            ->validationAttribute(label: 'fecha de inicio')
                            ->helperText('A partir de esta fecha iniciará la rifa')
                            ->validationMessages([
                                'required' => "Debe indicar la fecha",
                                'after' => "La fecha debe ser mayor o igual a hoy",
                            ]),
                        DatePicker::make('final_date')
                            ->label('Fecha de fin')
                            ->placeholder('Ej: 22/12/2024')
                            ->displayFormat('d/m/Y')
                            ->format('d/m/Y')
                            ->closeOnDateSelection()
                            ->minDate(now()->tomorrow()->startOfDay())
                            ->after('initial_date')
                            ->rules('required')
                            ->timezone('America/Caracas')
                            ->markAsRequired()
                            ->validationAttribute('fecha de fin')
                            ->helperText(text: 'Hasta esta fecha la rifa estará disponible')
                            ->validationMessages([
                                'required' => "Debe indicar la fecha",
                                'after' => "La fecha debe ser mayor a la inicial",
                            ]),
                    ])
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->modifyQueryUsing(function (Builder $query) {
                return $query->with('tickets');
            })
            ->heading('Nota: Para corroborar que los clientes fueron notificados espere aproximadamente 1 minuto y recargue la página y corrobore en el ícono de campana, si no aparece ninguna notificación puede que deba esperar un poco más de tiempo (varía acorde a la cantidad de clientes y boletos a notificar), puede continuar interactuando con el sistema y visualizar dichas notificaciones en cualquier momento')
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('is_active')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state) => $state === 'Disponible' ? 'success' : 'danger')
                    ->toggleable(),
                TextColumn::make('name')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('description')
                    ->label('Descripción')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('total_winners')
                    ->label('Ganadores')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('total_tickets')
                    ->label('Boletos')
                    ->formatStateUsing(
                        fn(Lottery $record) => "{$record->tickets_occuped} / {$record->total_tickets}"
                    )
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('ticket_price')
                    ->label('Precio boleto')
                    ->suffix(' $')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('total_price')
                    ->label('Precio')
                    ->formatStateUsing(
                        fn(Lottery $record) => "{$record->total_payed}$ / {$record->total_price}$"
                    )
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('total_prizes_value')
                    ->label('Valor en premios')
                    ->suffix('$')
                    ->toggleable(),
                TextColumn::make('lottery_date')
                    ->label('Fecha de sorteo')
                    ->toggleable(),
                TextColumn::make('initial_date')
                    ->label('Fecha de inicio')
                    ->formatStateUsing(
                        fn($state) => Carbon::createFromFormat('d/m/Y', $state)->translatedFormat('l, d M Y')
                    )
                    ->toggleable(),
                TextColumn::make('final_date')
                    ->formatStateUsing(
                        fn($state) => Carbon::createFromFormat('d/m/Y', $state)->translatedFormat('l, d M Y')
                    )
                    ->label('Fecha fin')
                    ->toggleable(),
                TextColumn::make('date_range')
                    ->label('Duración')
                    ->toggleable(),
            ])
            ->filters([
                Filter::make('is_active')
                    ->label('Estado')
                    ->form([
                        Select::make('is_active')
                            ->label('Estado')
                            ->options([
                                '1' => 'Activo',
                                '0' => 'Inactivo',
                            ])
                            ->placeholder('Seleccione un estado')
                            ->nullable()
                    ])
                    ->query(fn(Builder $query, array $data) => $query->when(
                        in_array($data['is_active'], ['0', '1']),
                        fn(Builder $query) =>
                        $query->where(
                            'final_date',
                            $data['is_active'] === '0' ? '<' : '>',
                            now()->endOfDay()->format('d/m/Y')
                        )
                    )),
                Filter::make('date')
                    ->label('Fechas')
                    ->form([
                        DatePicker::make('start_date')
                            ->label('Fecha inicial')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->format('d/m/Y'),
                        DatePicker::make('end_date')
                            ->label('Fecha final')
                            ->required()
                            ->displayFormat('d/m/Y')
                            ->format('d/m/Y'),
                    ])
                    ->query(fn(Builder $query, array $data) => $query->when(
                        !empty($data['start_date']) && !empty($data['end_date']),
                        fn(Builder $query) => $query->whereBetween('created_at', [
                            Carbon::parse($data['start_date']),
                            Carbon::parse($data['end_date']),
                        ])
                    )),
            ])
            ->actions([
                ActionGroup::make([
                    ViewAction::make(),
                    EditAction::make()
                        ->hidden(
                            fn(Lottery $record) => (strtotime(now()->format('Y-m-d')) >
                                strtotime(Carbon::createFromFormat('d/m/Y', $record->final_date)->format('Y-m-d'))) || $record->finished_at !== null
                        ),
                    SellTicketsAction::make(),
                    SeeSoldTicketsAction::make(),
                    TicketPaymentAction::make(),
                    CancelTicketsAction::make(),
                    PrizeModalAction::make(),
                    NotifyDebtorClientsAction::make(),
                    NotifyWinnerClientsAction::make(),
                    RaffleAction::make(),
                    DeleteAction::make()
                        ->after(function (Lottery $record) {
                            HasActivityLogger::logActivity($record, 'delete', 'delete');
                        }),
                ])
            ])
            ->defaultSort('id', 'desc')
            ->searchPlaceholder('Buscar rifa')
            ->searchDebounce(500);
    }

    public static function getRelations(): array
    {
        return [
            //
        ];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLotteries::route('/'),
            'create' => Pages\CreateLottery::route('/create'),
            'edit' => Pages\EditLottery::route('/{record}/edit'),
            'view' => Pages\ViewLottery::route('/{record}'),
        ];
    }

    public static function infolist(Infolist $infolist): Infolist
    {
        return $infolist->schema([
            Split::make([
                TextEntry::make('name')
                    ->label('Nombre'),
                TextEntry::make('total_price')
                    ->label('Precio')
                    ->formatStateUsing(
                        fn(Lottery $record) => "{$record->total_payed}$ / {$record->total_price}$"
                    )
            ]),
            Split::make([
                Split::make([
                    TextEntry::make(name: 'total_prizes_value')
                        ->label('Valor en premios')
                        ->suffix(' $'),
                ]),
                TextEntry::make('is_active')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(Lottery $record) => $record->is_active === 'Disponible' ? 'success' : 'danger')
            ]),
            TextEntry::make('description')
                ->label('Descripción')
                ->columnSpanFull(),
            Split::make([
                TextEntry::make('initial_date')
                    ->label('Fecha de inicio'),
                TextEntry::make('final_date')
                    ->label('Fecha fin')
            ]),
            Split::make([
                TextEntry::make('lottery_date')
                    ->label('Fecha del sorteo')
                    ->badge()
                    ->color(fn($state) => $state === 'Pendiente' ? Color::Yellow : Color::Green),
                TextEntry::make('date_range')
                    ->label('Duración')
            ]),
            Split::make([
                TextEntry::make('total_winners')
                    ->label('Ganadores totales'),
                TextEntry::make('total_tickets')
                    ->label('Boletos')
                    ->formatStateUsing(
                        fn(Lottery $record) => "{$record->tickets_occuped} / {$record->total_tickets}"
                    )
            ]),
            Livewire::make(LotteryTicketsComponent::class)
                ->columnSpanFull()
        ]);
    }

    public static function canCreate(): bool
    {
        return Currency::whereDate('created_at', Carbon::today())->exists();
    }
}
