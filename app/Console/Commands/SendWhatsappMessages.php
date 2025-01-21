<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


class SendWhatsappMessages extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ws:send';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends whatsapp messages to clients with pending tickets';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $start = microtime(true);

        $clients = Client::whereHas('tickets', function ($query) {
            $query->whereDoesntHave('payment');
        })
            ->get()
            ->map(function ($client) {
                $data = [
                    'client_name' => $client->fullName,
                    'phone' => substr($client->phoneNumber, 1),
                    'tickets' => $client->getPendingTicketsJson()
                ];

                return $data;
            });

        $total_clients = count($clients);
        $this->info("Clientes con boletos por pagar:",  $total_clients);

        $tickets = [];
        $time = now()->hour;
        $app_name = config('app.name');
        $salute = $time >= 12 && $time <= 17
            ? 'Buenas tardes'
            : ($time >= 18 ? 'Buenas noches' : 'Buenos días');

        foreach ($clients as $key => $client) {
            $chatId = $client['phone'];
            $total_tickets = count($client['tickets']);
            $total_debt = 0;
            $ticket_numbers = [];

            foreach ($client['tickets'] as $ticket) {
                array_push($tickets, $ticket['number']);
                $total_debt = round($total_debt + $ticket['price'], 2);
                array_push(
                    $ticket_numbers,
                    'Rifa: ' . $ticket['name'] . ', boleto: ' . $ticket['number']
                );
            }

            $ticket_numbers = implode(', ', $ticket_numbers);

            $message = "{$salute} *{$client['client_name']}*, reciba un cordial saludo de parte de _{$app_name}_, nos comunicamos con usted para hacerle notificación de que debe un total de: *{$total_tickets} boletos*, por un valor total de: *{$total_debt} $*, por favor ponerse en contácto para realizar el pago de los boletos pendientes ({$ticket_numbers}) o cancelar los mismos. Que tenga un felíz día";

            if ($key > 0) {
                sleep(rand(1, 3));
            }

            $process = new Process([
                "node",
                base_path('resources/js/ws_bot.js'),
                $chatId,
                $message
            ]);
            $process->setTimeout(timeout: 300);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }

            $this->info("Mensaje enviado a cliente:", $client['client_name']);
        }

        Ticket::whereIn('id', $tickets)->increment('alerts');

        $this->info('Mensajes enviados exitosamente');

        $end = microtime(true);
        $this->info('Tiempo de ejecusón: ' . ($end - $start) . ' segundos');
    }
}
