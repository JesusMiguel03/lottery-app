<?php

namespace App\Livewire;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class MontlyPaymentsChart extends ChartWidget
{
    protected static ?string $heading = 'Pagos mensuales';

    protected function getData(): array
    {
        $data = Trend::model(Payment::class)
            ->between(
                start: now()->startOfYear(),
                end: now()->endOfYear(),
            )
            ->perMonth()
            ->sum('amount');

        return [
            'labels' => $data->map(fn(TrendValue $value) => Carbon::createFromFormat('Y-m', $value->date)->translatedFormat('F'))->toArray(),
            'datasets' => [
                [
                    'label' => 'Pagos por mes',
                    'data' => $data->map(fn(TrendValue $value) => $value->aggregate)->toArray(),
                ]
            ],
        ];
    }

    protected function getType(): string
    {
        return 'bar';
    }

    public function getMaxHeight(): string|null
    {
        return '300px';
    }

    public function getColumnSpan(): array|int|string
    {
        return 'full';
    }
}
