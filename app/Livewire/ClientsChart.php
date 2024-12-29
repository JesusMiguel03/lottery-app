<?php

namespace App\Livewire;

use App\Models\Client;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class ClientsChart extends ChartWidget
{
    protected static ?string $heading = 'Clientes nuevos';

    protected function getData(): array
    {
        $data = Trend::model(Client::class)
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
