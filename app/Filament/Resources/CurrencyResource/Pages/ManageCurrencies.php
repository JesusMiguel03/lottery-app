<?php

namespace App\Filament\Resources\CurrencyResource\Pages;

use App\Filament\Resources\CurrencyResource;
use App\Filament\Traits\HasActivityLogger;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Model;

class ManageCurrencies extends ManageRecords
{
    use HasActivityLogger;

    protected static string $resource = CurrencyResource::class;

    public function getSubheading(): string|\Illuminate\Contracts\Support\Htmlable|null
    {
        return request('required')
            ? '(Debe registrar la tasa del día para agregar una rifa)'
            : '';
    }

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make()
                ->modalHeading('Crear Tasa')
                ->using(function (array $data) {
                    $record = static::getModel()::create($data);
                    $this->afterCreateModel($record);
                    return $record;
                }),
        ];
    }

    protected function getTableActions(): array
    {
        return [
            EditAction::make()
                ->modalHeading('Editar Tasa')
                ->using(function (Model $record, array $data) {
                    dd('running');
                    $record->update($data);
                    $this->afterSave($record);

                    return $record;
                })
        ];
    }

    public function table(Table $table): Table
    {
        return parent::table($table)
            ->actions([
                ActionGroup::make([
                    EditAction::make()
                        ->modalHeading('Editar Tasa')
                        ->using(function (Model $record, array $data) {
                            $record->update($data);
                            $this->afterSaveModel($record);
                            return $record;
                        })
                ])
            ]);
    }
}
