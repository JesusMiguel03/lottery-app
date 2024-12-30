<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LotteryResource\Actions\PrizeModalAction;
use App\Filament\Resources\LotteryResource\Actions\SellTicketsAction;
use App\Filament\Resources\LotteryResource\Actions\TicketPaymentAction;
use App\Filament\Resources\LotteryResource\Pages;
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
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class LotteryResource extends Resource
{
    use InteractsWithActions;

    protected static ?string $model = Lottery::class;

    protected static ?string $label = "Rifas";

    protected static ?int $navigationSort = 3;

    protected static ?string $navigationIcon = 'heroicon-o-book-open';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nombre')
                    ->placeholder('Ej: Moto Bera')
                    ->autofocus()
                    ->rules(['required', 'min:3', 'max:25', 'regex:/^[a-zA-Z\s]+$/'])
                    ->markAsRequired()
                    ->columnSpanFull()
                    ->validationAttribute('nombre')
                    ->validationMessages([
                        'required' => 'Debe indicar un nombre',
                        'regex' => 'Solo se aceptan letras',
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
                        'regex' => 'Solo se aceptan letras',
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
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $value = $get('total_winners');
                        $max = 100;
                        $set('total_winners', $value > $max ? $max : $value);
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
                        $set('ticket_value', $value > 0 ? $calc : 0);
                    })
                    ->hiddenOn('edit'),
                Repeater::make('prizes')
                    ->label('Premios')
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
                    ->maxItems(fn(Get $get) => $get('total_winners') ?? 0),
                Placeholder::make('prize_notes')
                    ->label('Notas')
                    ->content('Los premios se asignarán en el órden en que fueron definidos')
                    ->hidden(fn(Get $get) => empty($get('total_winners')))
                    ->columnSpanFull(),
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
                        $value = empty($get('total_price')) ? 0 : $get('total_price');
                        $calc = $value / $total_tickets;
                        $set('ticket_value', $value > 0 ? $calc : 0);
                    })
                    ->hiddenOn('edit'),
                TextInput::make('ticket_value')
                    ->label('Precio por boleto')
                    ->disabled()
                    ->suffix('$')
                    ->placeholder('Ej: 100')
                    ->helperText('Este campo mostrará que valor tendrá cada boleto')
                    ->hiddenOn('edit'),
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
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable()
                    ->toggleable(),
                TextColumn::make('is_active')
                    ->label('Estado')
                    ->badge()
                    ->color(fn(string $state) => $state === 'Activo' ? 'success' : 'danger')
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
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('total_price')
                    ->label('Precio total')
                    ->suffix('$')
                    ->searchable()
                    ->toggleable(),
                TextColumn::make('total_payed')
                    ->label('Total pagado')
                    ->suffix('$')
                    ->toggleable(),
                TextColumn::make('total_prizes_value')
                    ->label('Valor en premios')
                    ->suffix('$')
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
                    EditAction::make(),
                    SellTicketsAction::make(),
                    TicketPaymentAction::make()
                        ->hidden(fn(Lottery $record) => count($record->not_payed_tickets()) === 0),
                    PrizeModalAction::make()
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
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
        ];
    }
}
