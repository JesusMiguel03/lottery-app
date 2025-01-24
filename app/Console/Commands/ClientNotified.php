<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;

class ClientNotified extends Command
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

        try {
            $ticketId = $this->argument('ticketId');
            $ticket = Ticket::find($ticketId);

            if (!$ticket) {
                File::append($logFilePath, now() . ' - ' . '[ClientNotified]' . ' ' . "Boleto {$ticketId} no encontrado" . PHP_EOL);
                $this->error("Ticket with ID {$ticketId} not found.");
                return 1;
            }

            $ticket->update(['notified_at' => now()]);
            $this->info("Boleto {$ticket->number} notificado");
            return 0;
        } catch (\Exception $e) {
            File::append($logFilePath, now() . ' - ' . '[ClientNotified]' . ' ' . $e->getMessage() . PHP_EOL);

            $this->error("Ocurrió un error: " . $e->getMessage());
            return 1;
        }
    }
}
