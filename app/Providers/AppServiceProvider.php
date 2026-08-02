<?php

namespace App\Providers;

use Filament\Notifications\Notification;
use Filament\Pages\Page;
use Filament\Support\Facades\FilamentView;
use Filament\View\PanelsRenderHook;
use Illuminate\Support\Facades\File;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\ValidationException;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Page::$reportValidationErrorUsing = function (ValidationException $exception) {
            $logFilePath = public_path('logs/exceptions_log.txt');

            if (!File::exists(public_path('logs'))) {
                File::makeDirectory(public_path('logs'), 0755, true);
            }
            File::append($logFilePath, now() . ' - ' . '[Exception]' . ' ' . $exception->getMessage() . PHP_EOL);

            Notification::make()
                ->title($exception->getMessage())
                ->danger()
                ->send();
        };

        FilamentView::registerRenderHook(
            PanelsRenderHook::AUTH_LOGIN_FORM_BEFORE,
            fn() => view('filament.auth.demo-credentials'),
        );
    }
}
