<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Livewire\AnnualClientPaymentChart;
use Filament\Resources\Pages\ViewRecord;

class ViewClient extends ViewRecord
{
    protected static string $resource = ClientResource::class;

    public function getFooterWidgetsColumns(): int
    {
        return 1;
    }
    protected function getFooterWidgets(): array
    {
        return [
            AnnualClientPaymentChart::make([
                'client_id' => $this->record->id,
            ])
        ];
    }
}
