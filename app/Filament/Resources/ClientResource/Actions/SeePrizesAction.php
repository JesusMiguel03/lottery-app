<?php

namespace App\Filament\Resources\ClientResource\Actions;

use Filament\Tables\Actions\Action;
use App\Models\Client;
use Filament\Forms\Components\ViewField;

class SeePrizesAction extends Action
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->name('SeePrizes');

    $this->label("Ver premios")
      ->icon('heroicon-o-gift')
      ->modalHeading(fn(Client $record) => "Ver premios ({$record->tickets()
        ->where('winner', 1)
        ->whereHas('payment')
        ->count()}) de  ({$record->name})")
      ->form([
        ViewField::make('')
          ->view('filament.pages.prizes')
          ->viewData([
            'tickets' => '',
          ])
      ])
      ->modalSubmitAction(false)
      ->hidden(fn(Client $record) => $record->get_lotteries_won() === 0);
  }
}
