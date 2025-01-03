<?php

namespace App\Filament\Resources\CurrencyResource\Pages;

use App\Filament\Resources\CurrencyResource;
use Filament\Actions;
use Filament\Resources\Pages\ManageRecords;

class ManageCurrencies extends ManageRecords
{
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
        ];
    }
}
