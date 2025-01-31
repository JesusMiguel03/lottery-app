<?php

namespace App\Livewire;

use App\Models\Prize;
use Filament\Forms\Components\DatePicker;
use Filament\Support\Colors\Color;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\Filter;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;


class MonthlyPrizesTable extends BaseWidget
{
  public function table(Table $table): Table
  {
    return $table
      ->query(Prize::query())
      ->columns([
        TextColumn::make('name')
          ->label('Nombre'),
        TextColumn::make('value')
          ->label('Precio')
          ->suffix(' $'),
        TextColumn::make('quantity')
          ->label('Cantidad'),
        TextColumn::make('id')
          ->label('Ganador')
          ->badge()
          ->color(
            fn(Prize $record) =>
            $record->winner->count() > 0
              ? Color::Green
              : Color::Red
          )
          ->formatStateUsing(
            fn(Prize $record) =>
            $record->winner->count() > 0
              ? $record->winner[0]->client->fullName
              : 'Pendiente'
          ),
        TextColumn::make('lottery.dateRange')
          ->label('Fecha'),
        TextColumn::make('lottery_id')
          ->label('Lotería nro'),
      ])
      ->defaultSort('id', 'desc')
      ->filters([
        Filter::make('created_at')
          ->form([
            DatePicker::make('created_from')
              ->label('Desde')
              ->default(now()->startOfMonth()),
            DatePicker::make('created_until')
              ->label('Hasta')
              ->default(now()->endOfMonth()),
          ])
          ->query(function ($query, array $data) {
            return $query
              ->when(
                $data['created_from'],
                fn($query, $date) => $query->whereDate('created_at', '>=', $date)
              )
              ->when(
                $data['created_until'],
                fn($query, $date) => $query->whereDate('created_at', '<=', $date)
              );
          }),
      ])
      ->heading('Premios del mes');
  }

  protected int|string|array $columnSpan = 'full';
}
