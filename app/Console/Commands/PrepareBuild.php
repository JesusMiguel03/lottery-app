<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class PrepareBuild extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'prepare-build';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Command description';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $start = microtime(true);

        $keys = ['APP_ENV', 'APP_DEBUG'];
        $values = ['production', 'false'];
        $envFilePath = base_path('.env');
        $envContent = File::get($envFilePath);

        $this->info("[💬] Preparando variables de entorno...");
        foreach ($keys as $index => $key) {
            $value = $values[$index];
            if (preg_match("/^{$key}=.*/m", $envContent)) {
                $envContent = preg_replace("/^{$key}=.*/m", "{$key}={$value}", $envContent);
            } else {
                $envContent .= "\n{$key}={$value}\n";
            }
        }
        File::put($envFilePath, $envContent);
        $this->info("[✅] Entorno de producción configurado");

        $this->info("[💬] Ejecutando filament:assets...");
        exec('php artisan filament:assets');
        $this->info("[✅] Ejecución exitosa");
        $end = microtime(true);
        $this->info('[🕒] Duración: ' . ($end - $start) . ' segundos');

        $this->info("[💬] Ejecutando composer optimize...");
        exec('composer dump-autoload --optimize');
        $this->info("[✅] Ejecución exitosa");
        $end = microtime(true);
        $this->info('[🕒] Duración: ' . ($end - $start) . ' segundos');

        $this->info("[💬] Ejecutando artisan optimize...");
        exec('php artisan optimize');
        $this->info("[✅] Ejecución exitosa");
        $end = microtime(true);
        $this->info('[🕒] Duración: ' . ($end - $start) . ' segundos');

        $this->info("[💬] Ejecutando npm build...");
        exec('npm run build');
        $this->info("[✅] Ejecución exitosa");
        $end = microtime(true);
        $this->info('[🕒] Duración: ' . ($end - $start) . ' segundos');

        $this->info("[✅] Todos los comandos (4) fueron ejecutados con éxito");

        $end = microtime(true);
        $this->info('[🕒] Tiempo de ejecusón: ' . ($end - $start) . ' segundos');
        return 0;
    }
}
