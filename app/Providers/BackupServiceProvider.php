<?php

namespace App\Providers;

use App\Models\Backup;
use Carbon\Carbon;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
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
        $today = Carbon::now()->toDateString();
        $file = File::exists('./database/database.sqlite');

        if ($file) {
            $backupLog = Backup::firstOrNew([]);

            if ($backupLog->executed_at !== $today) {
                Artisan::call('backup:run --only-db');

                $backupLog->executed_at = $today;
                $backupLog->save();
            }
        }
    }
}
