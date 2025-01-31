<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\Filament\Traits\HasActivityLogger;
use App\RedirectTrait;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    use RedirectTrait, HasActivityLogger;
    protected static string $resource = ClientResource::class;

    protected function getCreatedNotificationMessage(): string|null
    {
        return 'Cliente registrado';
    }

    protected function afterCreate(): void
    {
        $this->afterCreateModel($this->record);
    }
}
