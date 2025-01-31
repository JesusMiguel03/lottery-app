<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Traits\HasActivityLogger;
use App\RedirectTrait;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditClient extends EditRecord
{
    use RedirectTrait, HasActivityLogger;
    protected static string $resource = ClientResource::class;

    protected function afterSave(): void
    {
        $this->afterSaveModel($this->record);
    }
}
