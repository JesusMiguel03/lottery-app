<?php

namespace App\Filament\Pages;

use App\Filament\Widgets\Whatsapp_qr;
use App\Livewire\MonthlyPrizesTable;
use App\Livewire\MontlyPaymentsChart;
use App\Livewire\NewClientsChart;
use App\Livewire\StatsOverview;
use App\Livewire\TicketsSoldChart;
use Filament\Pages\Page;

class HomeDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Inicio';
    protected function getHeaderWidgets(): array
    {
        return [
            StatsOverview::class,
            Whatsapp_qr::class,
            TicketsSoldChart::class,
            NewClientsChart::class,
            MontlyPaymentsChart::class,
            MonthlyPrizesTable::class,
        ];
    }
}
