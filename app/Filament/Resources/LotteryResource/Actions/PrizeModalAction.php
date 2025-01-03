<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use Filament\Tables\Actions\Action;
use App\Models\Lottery;
use Filament\Forms\Components\Placeholder;
use Illuminate\Support\HtmlString;

class PrizeModalAction extends Action
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->name('ViewPrices');

    $this->label('Ver premios')
      ->icon('heroicon-o-gift')
      ->modalHeading(fn(Lottery $record) => "Premios para rifa #{$record->id} ({$record->name})")
      ->modalSubmitAction(false)
      ->modalCancelActionLabel('Cerrar')
      ->form([
        Placeholder::make('')
          ->content(function (Lottery $record) {
            return new HtmlString("
                                        <ul class='grid grid-cols-1 sm:grid-cols-3 gap-3'>
                                            {$record->prizes->map(function ($prize) {
              return "<li class='p-4 border border-neutral-400 rounded-md'>
                                        <div class='flex flex-col'>
                                            <h6 class='font-semibold'>&num;{$prize->order} {$prize->name}</h6>
                                            <p>Cantidad: {$prize->quantity}, Valor: {$prize->value}$</p>
                                        </div>
                                                </li>";
            })->implode('')}
                                        </ul>
                                    ");
          })
      ]);
  }
}
