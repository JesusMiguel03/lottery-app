<?php

namespace App\Livewire;

use App\Models\Payment;
use Carbon\Carbon;
use Filament\Widgets\ChartWidget;
use Illuminate\Support\Facades\DB;

class MontlyPaymentsChart extends ChartWidget
{
    protected static ?string $heading = 'Pagos mensuales';

    protected function getData(): array
    {
        $data = DB::table('payments')
            ->select(DB::raw('strftime("%Y-%m", created_at) as month'), DB::raw('SUM(CASE 
                WHEN type = "bs" OR type = "payment" THEN amount / (SELECT value FROM currencies WHERE id = currency_id) 
                ELSE amount 
            END) as total'))
            ->whereBetween('created_at', [now()->startOfYear(), now()->endOfYear()])
            ->groupBy('month')
            ->orderBy('month')
            ->get();

        $months = [];
        for ($i = 1; $i <= 12; $i++) {
            $months[Carbon::now()->format('Y') . '-' . str_pad($i, 2, '0', STR_PAD_LEFT)] = 0;
        }

        foreach ($data as $value) {
            $months[$value->month] = $value->total;
        }

        return [
            'labels' => array_keys($months),
            'datasets' => [
                [
                    'label' => 'Pagos por mes $',
                    'data' => array_values($months),
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
