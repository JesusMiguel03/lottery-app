<?php

namespace App\Filament\Resources;

use App\Filament\Resources\ClientResource\Pages;
use App\Models\Client;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class ClientResource extends Resource
{
    protected static ?string $model = Client::class;

    protected static ?string $label = "Clientes";

    protected static ?int $navigationSort = 2;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
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
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('id')
                    ->label('ID')
                    ->sortable(),
                TextColumn::make('fullName')
                    ->label('Nombre')
                    ->sortable()
                    ->searchable(['name', 'last_name']),
                TextColumn::make('document')
                    ->label('Documento')
                    ->searchable(true, function (Builder $query, string $search) {
                        $upper_string = strtoupper($search);
                        $has_doc_type = in_array($upper_string[0], ['V', 'E', 'J', 'G']);

                        if (strlen($upper_string) > 1 && $has_doc_type) {
                            $doc_type = strtoupper(substr($upper_string, 0, 1));
                            $doc = substr($upper_string, 2);

                            $query->orWhere(function ($q) use ($doc_type, $doc) {
                                $q->where('doc_type', 'like', "%{$doc_type}%")
                                    ->where('doc', 'like', "%{$doc}%");
                            });
                        } else {
                            return $query->where('doc_type', 'like', "%{$upper_string}%")
                                ->orWhere('doc', 'like', "%{$upper_string}%");
                        }
                    }),
                TextColumn::make('phoneNumber')
                    ->label('Teléfono')
                    ->searchable(true, function (Builder $query, string $search) {
                        if (strlen($search) > 4) {
                            $code = substr($search, 0, 4);
                            $phone = substr($search, 4);

                            $query->orWhere(function ($q) use ($code, $phone) {
                                $q->where('code', 'like', "%{$code}%")
                                    ->where('phone', 'like', "%{$phone}%");
                            });
                        } else {
                            return $query->where('code', 'like', "%{$search}%")
                                ->orWhere('phone', 'like', "%{$search}%");
                        }
                    })
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
            ])
            ->defaultSort('id', 'desc')
            ->searchPlaceholder('Buscar cliente')
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
            'index' => Pages\ListClients::route('/'),
            'create' => Pages\CreateClient::route('/create'),
            'edit' => Pages\EditClient::route('/{record}/edit'),
        ];
    }

    public static function getGloballySearchableAttributes(): array
    {
        return ['name', 'last_name', 'code', 'phone', 'doc_type', 'doc'];
    }
}
