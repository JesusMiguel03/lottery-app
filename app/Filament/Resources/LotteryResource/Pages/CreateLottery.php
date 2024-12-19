<?php

namespace App\Filament\Resources\LotteryResource\Pages;

use App\Filament\Resources\LotteryResource;
use App\RedirectTrait;
use Filament\Actions;
use Filament\Resources\Pages\CreateRecord;

class CreateLottery extends CreateRecord
{
    use RedirectTrait;
    protected static string $resource = LotteryResource::class;
}
