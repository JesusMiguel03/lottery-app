<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Ticket;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SendWhatsappMessagesToDebtors extends Command
{
    protected $signature = 'ws:debtors {lottery_id}';
    protected $description = 'Sends whatsapp messages to clients with pending tickets';

    public function handle()
    {
        $lottery_id = $this->argument('lottery_id');
        $start = microtime(true);

        $this->info("Lotería {$lottery_id}");
        $this->info("[🔍] Buscando clientes morosos...");
        $clients = $this->fetchDebtors($lottery_id);

        $this->logDebtorCount($clients);

        if (count($clients) > 0) {
            $data = $this->prepareMessages($clients);
            $this->saveDataToFile($data);

            $this->sendMessagesViaBot();
            $this->updateTicketAlerts($clients);
            $this->info('[✅] Mensajes enviados exitosamente');
        } else {
            $this->info("[❌] No se encontraron deudores a los que notificar...");
        }

        $this->logExecutionTime($start);

        return 0;
    }

    private function fetchDebtors($lottery_id)
    {
        return Client::whereHas(
            'tickets',
            fn($query) =>
            $query->pendingPayment()
                ->where('lottery_id', $lottery_id)
        )
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

    private function prepareMessages($clients)
    {
        $salute = $this->getGreeting();
        $appName = config('app.name');

        $data = [];

        foreach ($clients as $client) {
            $messageData = $this->prepareMessageData($client, $salute, $appName);
            $data[] = [
                'message' => $messageData['message'],
                'chatId' => $messageData['chatId']
            ];
        }

        return $data;
    }

    private function prepareMessageData($client, $salute, $appName)
    {
        $ticketDetails = collect($client['tickets'])->map(
            fn($ticket) => "Rifa: {$ticket['lottery_name']}, boleto: {$ticket['number']}"
        )->implode(', ');

        $totalDebt = collect($client['tickets'])->sum('price');
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

    private function saveDataToFile($data)
    {
        File::put(
            storage_path('app/public/clients.json'),
            json_encode($data, JSON_PRETTY_PRINT)
        );

        $this->info("[✅] Información de clientes guardada con éxito...");
    }

    private function sendMessagesViaBot()
    {
        $this->info("[💬] Procediendo al envio de mensajes...");

        $process = new Process([
            "node",
            base_path('resources/js/ws_bot.js')
        ]);

        $process->setTimeout(300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->info("[👍] Envío finalizado");
    }

    private function updateTicketAlerts($clients)
    {
        $ticketIds = collect($clients)->flatMap(
            fn($client) => collect($client['tickets'])->pluck('id')
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
