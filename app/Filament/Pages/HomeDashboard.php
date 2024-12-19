<?php

namespace App\Filament\Pages;

use Filament\Pages\Page;

class HomeDashboard extends Page
{
    protected static ?string $navigationIcon = 'heroicon-o-home';

    protected static string $view = 'filament.pages.dashboard';

    protected static ?int $navigationSort = 1;

    protected static ?string $title = 'Inicio';
}
