<?php

namespace App\Filament\Resources\LotteryResource\Pages;

use App\Filament\Resources\LotteryResource;
use App\RedirectTrait;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditLottery extends EditRecord
{
    use RedirectTrait;
    protected static string $resource = LotteryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make(),
        ];
    }
}
