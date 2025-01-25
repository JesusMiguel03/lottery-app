<?php

namespace App\Livewire;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Get;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;
use Illuminate\Support\Facades\DB;
use Leandrocfe\FilamentApexCharts\Widgets\ApexChartWidget;

class MontlyPaymentsChart extends ApexChartWidget
{
    /**
     * Chart Id
     *
     * @var string
     */
    protected static ?string $chartId = 'montlyPaymentsChart';

    /**
     * Widget Title
     *
     * @var string|null
     */
    protected static ?string $heading = 'Pagos mensuales';

    /**
     * Chart options (series, labels, types, size, animations...)
     * https://apexcharts.com/docs/options
     *
     * @return array
     */
    protected function getOptions(): array
    {
        $data = Payment::whereBetween(
            'created_at',
            [
                Carbon::parse($this->filterFormData['date_start']),
                Carbon::parse($this->filterFormData['date_end']),
            ]
        )->selectRaw('
            strftime("%Y-%m", created_at) as month,
            SUM(
                CASE WHEN type = "bs" OR type = "payment" THEN
                    amount / (SELECT value FROM currencies WHERE id = currency_id)
                ELSE amount 
                END
            ) as aggregate')
            ->groupBy('month')
            ->get()
            ->keyBy('month')
            ->toArray();


        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $date = Carbon::createFromFormat('m', $i)->format('Y-m');
            $months[$date] = [
                'month' => $date,
                'aggregate' => 0
            ];
        }

        $groupedData = array_merge($months, $data);
        ksort($groupedData);

        $data = [];
        foreach ($groupedData as $month => $row) {
            $data[] = [
                'month' => $month,
                'aggregate' => $row['aggregate'],
            ];
        }

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
                    'name' => 'Pagos por mes $',
                    'data' => array_map(fn($row) => $row['aggregate'], $data),
                ],
            ],
            'xaxis' => [
                'categories' => array_map(
                    fn($row) =>
                    Carbon::createFromFormat('Y-m', $row['month'])->translatedFormat('Y-M'),
                    $data
                ),
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

    public function getColumnSpan(): array|int|string
    {
        return 'full';
    }
}
