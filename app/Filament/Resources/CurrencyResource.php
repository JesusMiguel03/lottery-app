<?php

namespace App\Filament\Resources;

use App\Filament\Resources\CurrencyResource\Pages;
use App\Models\Currency;
use Carbon\Carbon;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class CurrencyResource extends Resource
{
    protected static ?string $model = Currency::class;

    protected static ?string $label = 'Tasas';

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-currency-dollar';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('value')
                    ->label('Precio')
                    ->placeholder('Ej: 46,20')
                    ->suffix('Bs')
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
                    ->columnSpanFull()
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('value')
                    ->label('Valor')
                    ->suffix(' Bs'),
                TextColumn::make('created_at')
                    ->label('Registrado')
                    ->formatStateUsing(fn($state) => Carbon::parse($state)
                        ->translatedFormat('l d, M Y')),
            ])
            ->filters([
                //
            ])
            ->actions([
                ActionGroup::make([
                    Tables\Actions\EditAction::make(),
                ])
            ]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ManageCurrencies::route('/'),
        ];
    }

    public static function canCreate(): bool
    {
        return !Currency::whereDate('created_at', Carbon::today())->exists();
    }
}
