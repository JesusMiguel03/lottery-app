<?php

namespace App\Filament\Resources\LotteryResource\Pages;

use App\Filament\Resources\LotteryResource;
use App\Filament\Traits\HasActivityLogger;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListLotteries extends ListRecords
{
    use HasActivityLogger;

    protected static string $resource = LotteryResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
