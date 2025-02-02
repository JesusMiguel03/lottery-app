<?php

namespace App\Jobs;

use App\Events\BotMessageProcessedEvent;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class ProcessWhatsappMessagesJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(public int $clientCount, public int $lotteryId, public string $objective) {}

    public function handle()
    {
        $process = new Process([
            'php',
            'artisan',
            $this->objective === 'debtors' ? 'ws:debtors' : 'ws:winners',
            $this->lotteryId
        ]);
        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $output = $process->getOutput();
        $logFilePath = public_path('logs/jobs_log.txt');

        if (!File::exists(public_path('logs'))) {
            File::makeDirectory(public_path('logs'), 0755, true);
        }
        File::append($logFilePath, now() . ' - ' . "[ProcessWhatsappMessagesJob - $this->objective]" . ' ' . $output . PHP_EOL);

        event(new BotMessageProcessedEvent(
            $this->clientCount,
            $this->lotteryId,
            $this->objective
        ));
    }
}
