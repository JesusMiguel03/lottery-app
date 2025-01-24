<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class TicketNotified extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ticket_notified {ticketId}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Updates ticket notificacion';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $logFilePath = public_path('logs/error_log.txt');
        if (!File::exists(public_path('logs'))) {
            File::makeDirectory(public_path('logs'), 0755, true);
        }

        $ticketIdsAsString = $this->argument('ticketId');
        $ticketIds = explode(', ', $this->argument('ticketId'));
        try {
            $tickets = Ticket::findMany($ticketIds);

            if ($tickets->isEmpty()) {
                File::append($logFilePath, now() . ' - ' . '[ClientNotified]' . ' ' . "Boletos ({$ticketIdsAsString}) no encontrados" . PHP_EOL);
                $this->error("Boleto ({$ticketIdsAsString}) no encontrados.");
                return 1;
            }

            Ticket::whereIn('id', $ticketIds)
                ->update(['notified_at' => now()]);
            $this->info("Boletos ({$ticketIdsAsString}) notificados");
            return 0;
        } catch (\Exception $e) {
            File::append($logFilePath, now() . ' - ' . '[ClientNotified]' . ' ' . $e->getMessage() . PHP_EOL);

            $this->error("Ocurrió un error: " . $e->getMessage());
            return 1;
        }
    }
}
