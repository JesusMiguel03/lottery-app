<?php

namespace App\Filament\Resources\LotteryResource\Pages;

use App\Filament\Resources\LotteryResource;
use App\Filament\Traits\HasActivityLogger;
use Filament\Resources\Pages\ViewRecord;

class ViewLottery extends ViewRecord
{
    use HasActivityLogger;

    protected static string $resource = LotteryResource::class;
}
