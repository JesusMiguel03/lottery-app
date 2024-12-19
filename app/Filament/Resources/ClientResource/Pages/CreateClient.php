<?php

namespace App\Filament\Resources\ClientResource\Pages;

use App\Filament\Resources\ClientResource;
use App\RedirectTrait;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateClient extends CreateRecord
{
    use RedirectTrait;
    protected static string $resource = ClientResource::class;

    protected function getCreatedNotificationMessage(): string|null
    {
        return 'Cliente registrado';
    }
}
