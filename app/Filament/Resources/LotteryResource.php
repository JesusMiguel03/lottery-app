<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LotteryResource\Pages;
use App\Filament\Resources\LotteryResource\RelationManagers;
use App\Models\Client;
use App\Models\Lottery;
use App\Models\Ticket;
use Filament\Actions;
use Filament\Actions\Concerns\InteractsWithActions;
use Filament\Actions\Concerns\InteractsWithRecord;
use Filament\Forms;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Livewire;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Forms\Get;
use Filament\Forms\Set;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;
use Livewire\Component;
use Illuminate\Support\Str;

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
                    ->helperText('Coloque un número desde 1 hasta 10.000, si el número es mayor a 10.000 automáticamente se colocará el máximo de boletos')
                    ->afterStateUpdated(function (Get $get, Set $set) {
                        $value = $get('total_tickets');
                        $max = 10000;
                        $set('total_tickets', $value > $max ? $max : $value);
                    })
                    ->hiddenOn('edit'),
                DatePicker::make('initial_date')
                    ->label('Fecha de inicio')
                    ->placeholder('Ej: 21/12/2024')
                    ->displayFormat('d/m/Y')
                    ->format('d/m/Y')
                    ->native(false)
                    ->closeOnDateSelection()
                    ->minDate(now()->startOfDay())
                    ->rules(['required'])
                    ->markAsRequired()
                    ->validationAttribute(label: 'fecha de inicio')
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
                    ->native(false)
                    ->minDate(now()->tomorrow()->startOfDay())
                    ->after('initial_date')
                    ->rules('required')
                    ->timezone('America/Caracas')
                    ->markAsRequired()
                    ->validationAttribute('fecha de fin')
                    ->validationMessages([
                        'required' => "Debe indicar la fecha",
                        'after' => "La fecha debe ser mayor a la inicial",
                    ]),
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
                    ->label('ID'),
                TextColumn::make('name')
                    ->label('Nombre'),
                TextColumn::make('description')
                    ->label('Descripción'),
                TextColumn::make('total_winners')
                    ->label('Ganadores'),
                TextColumn::make('total_tickets')
                    ->label('Boletos'),
                TextColumn::make('date_range')
                    ->label('Duración'),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    EditAction::make(),
                    Action::make('sellTickets')
                        ->label("Venta de boletos")
                        ->icon('heroicon-o-tag')
                        ->slideOver()
                        ->modalHeading(fn(Lottery $record) => "Venta de boletos para rifa {$record->name}")
                        ->size('sm')
                        ->form([
                            Select::make('client_id')
                                ->label('Cliente')
                                ->options(Client::query()->pluck('name', 'id'))
                                ->required()
                                ->searchable(),
                            CheckboxList::make('tickets')
                                ->label('Boletos')
                                ->options(function (Lottery $record) {
                                    $tickets = $record->tickets()->with('client')->select('number', 'id', 'client_id')->get();

                                    $formatted_tickets = $tickets->mapWithKeys(function ($ticket) {
                                        $client_name = empty($ticket->client) ? 'Disponible' : $ticket->client->fullName;
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
                                ->disableOptionWhen(fn(Lottery $record, string $value) => in_array($value, $record->tickets->where('client_id', '!=', null)->pluck('id')->toArray()))
                                ->columns(3)
                                ->searchable()
                                ->markAsRequired()
                        ])
                        ->action(function (Lottery $lottery, array $data) {
                            $client = Client::find($data['client_id']);
                            $available_tickets = $lottery->tickets->where('client_id', null)->pluck('id')->toArray();

                            $selected_tickets = array_intersect($available_tickets, $data['tickets']);
                            $tickets = Ticket::findMany($selected_tickets);
                            $client->tickets()->saveMany($tickets);

                            $total_tickets = count($selected_tickets);
                            $client_name = $client->fullName;

                            Notification::make()
                                ->title('Boletos vendidos')
                                ->body(Str::markdown("Se ha vendido **{$total_tickets}** boletos al cliente **{$client_name}** en la rifa de **{$lottery->name}**"))
                                ->success()
                                ->send();
                        }),
                ])
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ])
            ->defaultSort('id', 'desc');
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
