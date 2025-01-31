<?php

namespace App\Console\Commands;

use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class InitializeWhatsappConnection extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ws:start {phone}';

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
        $phone = $this->argument('phone');
        $now = now()->translatedFormat('d-m-Y h:i:s a');

        $this->info(string: "Iniciando la conexión con whatsapp web...");
        $process = new Process([
            "node",
            base_path('resources/js/ws_bot.js'),
            $phone,
            "Conexión establecida exitosamente a las: {$now}"
        ]);

        $process->setTimeout(timeout: 300);
        $process->run(function ($type, $buffer) {
            if (Process::ERR === $type) {
                $this->error($buffer);
            } else {
                $this->info($buffer);
            }
        });

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->info("[👍] Conexión establecida con éxito");
        return 0;
    }
}
