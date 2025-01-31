<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SendWhatsappMessagesToDebtors extends Command
{
    protected $signature = 'ws:debtors';
    protected $description = 'Sends whatsapp messages to clients with pending tickets';

    public function handle()
    {
        $start = microtime(true);

        $this->info("[🔍] Buscando clientes morosos...");
        $clients = $this->fetchDebtors();
        $this->logDebtorCount($clients);

        $this->sendDebtorMessages($clients);
        $this->updateTicketAlerts($clients);

        $this->info('[✅] Mensajes enviados exitosamente');
        $this->logExecutionTime($start);

        return 0;
    }

    private function fetchDebtors()
    {
        return Client::whereHas('tickets', fn($query) => $query->pendingPayment())
            ->with(['tickets' => fn($query) => $query->pendingPayment()->with('lottery')])
            ->get()
            ->map(function ($client) {
                return [
                    'client_name' => $client->fullName,
                    'phone' => substr($client->phoneNumber, 1),
                    'tickets' => $client->tickets->map(fn($ticket) => [
                        'id' => $ticket->id,
                        'number' => $ticket->number,
                        'price' => $ticket->price,
                        'lottery_name' => $ticket->lottery->name
                    ])
                ];
            });
    }

    private function logDebtorCount($clients)
    {
        $totalClients = count($clients);
        $totalTickets = $clients->sum(fn($client) => count($client['tickets']));

        $this->info("[🧑] Clientes encontrados: {$totalClients}");
        $this->info("[🎫] Boletos pendientes: {$totalTickets}");
    }

    private function sendDebtorMessages($clients)
    {
        $salute = $this->getGreeting();
        $appName = config('app.name');

        $clients->each(function ($client, $key) use ($salute, $appName) {
            $this->sendMessageToClient($client, $salute, $appName, $key);

            if ($key > 0) {
                sleep(rand(1, 3));
            }
        });
    }

    private function sendMessageToClient($client, $salute, $appName, $index)
    {
        $messageData = $this->prepareMessageData($client, $salute, $appName);

        $process = new Process([
            "node",
            base_path('resources/js/ws_bot.js'),
            $messageData['chatId'],
            $messageData['message']
        ]);

        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->info("[📤] Mensaje enviado a: {$client['client_name']}");
    }

    private function prepareMessageData($client, $salute, $appName)
    {
        $ticketDetails = $client['tickets']->map(
            fn($ticket) =>
            "Rifa: {$ticket['lottery_name']}, boleto: {$ticket['number']}"
        )->implode(', ');

        $totalDebt = $client['tickets']->sum('price');
        $totalTickets = count($client['tickets']);

        $message = "{$salute} *{$client['client_name']}*, reciba un cordial saludo de parte de _{$appName}_, "
            . "nos comunicamos con usted para notificarle que debe un total de: *{$totalTickets} boletos*, "
            . "por un valor total de: *{$totalDebt} $*. Por favor póngase en contacto para realizar el pago "
            . "de los boletos pendientes ({$ticketDetails}) o cancelar los mismos. Que tenga un feliz día.";

        return [
            'chatId' => $client['phone'],
            'message' => $message
        ];
    }

    private function updateTicketAlerts($clients)
    {
        $ticketIds = $clients->flatMap(
            fn($client) =>
            collect($client['tickets'])->pluck('id')
        )->all();

        Ticket::whereIn('id', $ticketIds)->increment('alerts');
    }

    private function getGreeting()
    {
        $hour = now()->hour;

        return match (true) {
            $hour >= 12 && $hour <= 17 => 'Buenas tardes',
            $hour >= 18 => 'Buenas noches',
            default => 'Buenos días'
        };
    }

    private function logExecutionTime($start)
    {
        $this->info('[🕒] Tiempo de ejecución: ' . round(microtime(true) - $start, 2) . ' segundos');
    }
}
