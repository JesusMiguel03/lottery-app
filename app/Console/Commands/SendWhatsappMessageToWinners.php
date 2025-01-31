<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;

class SendWhatsappMessageToWinners extends Command
{
    protected $signature = 'ws:winners {lottery_id}';
    protected $description = 'Sends whatsapp message to client winners';

    public function handle()
    {
        $lottery_id = $this->argument('lottery_id');
        $start = microtime(true);

        $this->info("Lotería {$lottery_id}");
        $this->info("[🔍] Obteniendo clientes...");

        $winners = $this->fetchWinners($lottery_id);
        $losers = $this->fetchLosers($lottery_id);

        $this->logClientCounts($winners, $losers);

        $data = $this->prepareMessages($winners, $losers);

        $this->saveDataToFile($data);

        $this->sendMessagesViaBot();

        $end = microtime(true);
        $this->info('[🕒] Tiempo de ejecusón: ' . ($end - $start) . ' segundos');

        return 0;
    }

    private function fetchWinners($lottery_id)
    {
        return Client::whereHas('tickets', function ($query) use ($lottery_id) {
            $query->whereHas('payment')
                ->where('winner', true)
                ->where('active', true)
                ->whereHas('lottery', fn($q) => $q->where('id', $lottery_id));
        })
            ->with(['tickets' => function ($query) use ($lottery_id) {
                $query->where('winner', true)
                    ->where('lottery_id', $lottery_id)
                    ->with(['lottery' => fn($q) => $q->select(['id', DB::raw('name as lottery_name')])]);
            }])
            ->select([
                DB::raw("name || ' ' || last_name as client_name"),
                DB::raw("code || '' || phone as client_phone"),
                '*'
            ])
            ->get()
            ->map(function ($client) {
                $tickets = [];
                $lottery_name = $client['tickets'][0]['lottery']['lottery_name'];
                foreach ($client['tickets'] as $ticket) {
                    $tickets[] = [
                        'number' => $ticket['number'],
                        'id' => $ticket['id']
                    ];
                }
                return [
                    'client_name' => $client['client_name'],
                    'client_phone' => $client['client_phone'],
                    'lottery_name' => $lottery_name,
                    'tickets' => $tickets,
                ];
            });
    }

    private function fetchLosers($lottery_id)
    {
        return Client::whereHas('tickets', function ($query) use ($lottery_id) {
            $query->whereHas('payment')
                ->where('winner', false)
                ->where('active', true)
                ->whereHas('lottery', fn($q) => $q->where('id', $lottery_id));
        })
            ->select([
                DB::raw("name || ' ' || last_name as client_name"),
                DB::raw("code || '' || phone as client_phone"),
            ])
            ->get()
            ->toArray();
    }

    private function logClientCounts($winners, $losers)
    {
        $winnersCount = count($winners);
        $losersCount = count($losers);
        $totalClients = $winnersCount + $losersCount;

        $this->info("[🧑] Clientes encontrados");
        $this->info("   - Clientes ganadores: {$winnersCount}");
        $this->info("   - Clientes restantes: {$losersCount}");
        $this->info("   - Clientes totales: {$totalClients}");
    }

    private function prepareMessages($winners, $losers)
    {
        $time = now()->hour;
        $app_name = config('app.name');
        $salute = $time >= 12 && $time <= 17
            ? 'Buenas tardes'
            : ($time >= 18 ? 'Buenas noches' : 'Buenos días');

        $winner_message = config('messages.winner');
        $looser_message = config('messages.looser');

        $data = [];

        foreach ($winners as $client) {
            $chatId = substr($client['client_phone'], 1);
            $tickets = $client['tickets'];
            $formatted_tickets = array_map(fn($ticket) => "- *{$ticket['number']}*", $tickets);
            $ticket_numbers = implode("\n", $formatted_tickets);
            $message = str_replace(
                ['SALUTE', 'CLIENT_NAME', 'APP_NAME', 'LOTTERY_NAME', 'TICKETS'],
                [$salute, $client['client_name'], $app_name, $client['lottery_name'], $ticket_numbers],
                $winner_message
            );
            $data['winners'][] = [
                'message' => $message,
                'chatId' => $chatId
            ];
        }

        foreach ($losers as $client) {
            $chatId = substr($client['client_phone'], 1);
            $message = str_replace(
                ['SALUTE', 'CLIENT_NAME', 'APP_NAME', 'LOTTERY_NAME'],
                [$salute, $client['client_name'], $app_name, ''], // Assuming lottery_name is not needed for losers
                $looser_message
            );
            $data['losers'][] = [
                'message' => $message,
                'chatId' => $chatId
            ];
        }

        return $data;
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

        $process->setTimeout(timeout: 300);
        $process->run();

        if (!$process->isSuccessful()) {
            throw new ProcessFailedException($process);
        }

        $this->info("[👍] Envío finalizado");
        $this->info('[✅] Mensajes enviados exitosamente');
    }
}
