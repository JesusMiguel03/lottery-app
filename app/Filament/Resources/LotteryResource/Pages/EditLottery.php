<?php

namespace App\Filament\Resources\LotteryResource\Pages;

use App\Filament\Resources\LotteryResource;
use App\Filament\Traits\HasActivityLogger;
use App\RedirectTrait;
use Filament\Resources\Pages\EditRecord;

class EditLottery extends EditRecord
{
    use RedirectTrait, HasActivityLogger;
    protected static string $resource = LotteryResource::class;

    protected function afterSave(): void
    {
        $this->afterSaveModel($this->record);
    }
}
