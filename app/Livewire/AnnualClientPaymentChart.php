<?php

namespace App\Livewire;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Flowframe\Trend\Trend;
use Flowframe\Trend\TrendValue;

class AnnualClientPaymentChart extends ChartWidget
{
    protected static ?string $heading = 'Pagos mensuales';

    public int $client_id;

    protected function getData(): array
    {
        $data = Trend::query(
            Payment::with('ticket')
                ->whereHas('ticket', function ($query) {
                    $query->where('client_id', $this->client_id);
                })
        )
            ->between(
                start: Carbon::now()->startOfYear(),
                end: Carbon::now()->endOfYear()
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

    public function getMaxHeight(): string|null
    {
        return '300px';
    }

    protected function getType(): string
    {
        return 'bar';
    }
}
