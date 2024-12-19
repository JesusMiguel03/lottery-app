<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Filament\Resources\ClientResource\RelationManagers;
use App\Models\Client;
use Filament\Forms;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Components\Tab;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\SoftDeletingScope;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $label = "Clientes";

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                TextInput::make('name')
                    ->label('Nombre')
                    ->placeholder('José'),
                TextInput::make('last_name')
                    ->label('Apellido')
                    ->placeholder('Marcos'),
                Select::make('doc_type')
                    ->label('Nacionalidad')
                    ->options(['V' => 'V', 'E' => 'E', 'J' => 'J', 'G' => 'G'])
                    ->placeholder('Selecciona una opción'),
                TextInput::make('doc')
                    ->label('Documento')
                    ->type('number')
                    ->placeholder('12451248'),
                Select::make('code')
                    ->label('Código')
                    ->options([
                        '0412' => '0412',
                        '0414' => '0414',
                        '0416' => '0416',
                        '0424' => '0424',
                        '0426' => '0426'
                    ])
                    ->placeholder('Selecciona una opción'),
                TextInput::make('phone')
                    ->label('Código')
                    ->type('number')
                    ->placeholder('4561278'),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('name')
                    ->label('Nombre'),
                TextColumn::make('last_name')
                    ->label('Apellido'),
                TextColumn::make('document')
                    ->label('Documento'),
                TextColumn::make('phoneNumber')
                    ->label('Teléfono')
            ])
            ->filters([
                //
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }
}
