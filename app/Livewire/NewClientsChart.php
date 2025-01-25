<?php

namespace App\Livewire;

use App\Models\Client;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Get;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class NewClientsChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'newClientsChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Clientes nuevos';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $data = Trend::model(Client::class)
            ->between(
                start: Carbon::parse($this->filterFormData['date_start']),
                end: Carbon::parse($this->filterFormData['date_end']),
            )
            ->perMonth()
            ->count();

        return [
            'chart' => [
                'type' => 'bar',
                'height' => 300,
                'toolbar' => [
                    'show' => false,
                ],
            ],
            'series' => [
                [
                    'name' => 'Clientes por mes',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate)->toArray(),
                ],
            ],
            'xaxis' => [
                'categories' => $data->map(fn(TrendValue $value) => Carbon::createFromFormat('Y-m', $value->date)->translatedFormat('F'))->toArray(),
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
            'yaxis' => [
                'labels' => [
                    'style' => [
                        'fontFamily' => 'inherit',
                    ],
                ],
            ],
        ];
    }

    protected function getFormSchema(): array
    {
        return [
            DatePicker::make('date_start')
                ->label('Fecha de inicio')
                ->default(now()->startOfYear())
                ->maxDate(fn(Get $get) => Carbon::createFromFormat('Y-m-d', $get('date_end'))->subDay()->startOfDay()),
            DatePicker::make('date_end')
                ->label('Fecha fin')
                ->default(now()->endOfYear())
                ->minDate(fn(Get $get) => Carbon::createFromFormat('Y-m-d', $get('date_start'))->addDay()->startOfDay())
        ];
    }
}
