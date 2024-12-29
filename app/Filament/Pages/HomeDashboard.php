<?php

namespace App\Filament\Pages;

use App\Livewire\ClientsChart;
use App\Livewire\StatsOverview;
use App\Livewire\TicketsChart;
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
            TicketsChart::class,
            ClientsChart::class
        ];
    }
}
