<?php

namespace App\Livewire;

use App\Models\Ticket;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class TicketsChart extends ChartWidget
{
    protected static ?string $heading = 'Boletos vendidos';

    protected function getData(): array
    {
        $data = Trend::model(Ticket::class)
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->count();

        return [
            'labels' => $data->map(fn(TrendValue $value) => Carbon::createFromFormat('Y-m', $value->date)->translatedFormat('F'))->toArray(),
            'datasets' => [
                [
                    'label' => 'Boletos por mes',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate)->toArray(),
                ]
            ],
        ];
    }


    protected function getType(): string
    {
        return 'bar';
    }
}
