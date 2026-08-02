<?php

namespace App\Providers;

use App\Models\Backup;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\ServiceProvider;

class BackupServiceProvider extends ServiceProvider
{
    /**
     * Register services.
     */
    public function register(): void
    {
        //
    }

    /**
     * Bootstrap services.
     */
    public function boot(): void
    {
        $database = database_path('database.sqlite');

        if (! File::exists($database)) {
            return;
        }

        try {
            if (! Schema::hasTable('backups')) {
                return;
            }

            $today = Carbon::now()->toDateString();
            $backupLog = Backup::firstOrNew([]);

            if ($backupLog->executed_at !== $today) {
                Artisan::call('backup:run --only-db');

                $backupLog->executed_at = $today;
                $backupLog->save();
            }
        } catch (\Throwable $e) {
            report($e);
        }
    }
}

