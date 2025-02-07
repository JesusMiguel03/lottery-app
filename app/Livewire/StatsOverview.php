<?php

namespace App\Livewire;

use App\Models\Client;
use App\Models\Payment;
use App\Models\Ticket;
use Filament\Widgets\StatsOverviewWidget as BaseWidget;
use Filament\Widgets\StatsOverviewWidget\Stat;

class StatsOverview extends BaseWidget
{
    protected function getStats(): array
    {
        $total_clients = Client::count();
        $total_tickets = Ticket::where('active', 1)->count();
        $total_payments = round(Payment::with('currency')->get()->reduce(function ($carry, $payment) {
            if ($payment->type === 'bs' || $payment->type === 'payment') {
                return $carry + ($payment->amount / $payment->currency->value);
            }
            return $carry + $payment->amount;
        }, 0), 2);

        $monthly_payments = round(Payment::with('currency')->whereMonth('created_at', now()->month)->get()->reduce(function ($carry, $payment) {
            if ($payment->type === 'bs' || $payment->type === 'payment') {
                return $carry + ($payment->amount / $payment->currency->value);
            }
            return $carry + $payment->amount;
        }, 0), 2);
        $montly_tickets = round(Ticket::whereMonth('created_at', '=', now()->month)->count(), 2);

        return [
            Stat::make('Clientes registrados', $total_clients),
            Stat::make('Boletos totales', $total_tickets),
            Stat::make('Pagos totales', "{$total_payments} $"),
            Stat::make('Boletos mensuales', $montly_tickets),
            Stat::make('Pagos mensuales', "{$monthly_payments} $"),
        ];
    }
}
