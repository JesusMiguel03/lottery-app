<?php

namespace App\Console\Commands;

use App\Models\Client;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Symfony\Component\Process\Process;
use Symfony\Component\Process\Exception\ProcessFailedException;


class SendWhatsappMessageToWinners extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'ws:winners {lottery_id}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Sends whatsapp message to client winners';

    /**
     * Execute the console command.
     */
    public function handle()
    {
        $lottery_id = $this->argument('lottery_id');
        $start = microtime(true);

        $this->info("[🔍] Obteniendo clientes...");

        $winners = Client::whereHas('tickets', function ($query) use ($lottery_id) {
            $query->whereHas('payment')
                ->where('winner', true)
                ->where('active', true)
                ->whereHas('lottery', function ($query) use ($lottery_id) {
                    $query->where('id', $lottery_id);
                });
        })
            ->with(['tickets' => function ($query) use ($lottery_id) {
                $query->where('winner', true)
                    ->where('lottery_id', $lottery_id)
                    ->with(['lottery' => function ($query) {
                        $query->select(['id', DB::raw('name as lottery_name')]);
                    }]);
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
                    array_push($tickets, $ticket['number']);
                }

                $data = [
                    'client_name' => $client['client_name'],
                    'client_phone' => $client['client_phone'],
                    'lottery_name' => $lottery_name,
                    'tickets' => $tickets,
                ];

                return $data;
            });

        $loosers = Client::whereHas('tickets', function ($query) {
            $query->whereHas('payment')
                ->where('winner', false)
                ->where('active', true)
                ->whereHas('lottery', function ($query) {
                    $query->where('id', 7);
                });
        })
            ->select([
                DB::raw("name || ' ' || last_name as client_name"),
                DB::raw("code || '' || phone as client_phone"),
            ])
            ->get()
            ->toArray();

        $winnersCount = count($winners);
        $loosersCount = count($loosers);
        $totalClients = $winnersCount + count($loosers);

        $this->info("[🧑] Clientes encontrados");
        $this->info("     - Clientes ganadores: {$winnersCount}",);
        $this->info("     - Clientes restantes: {$loosersCount}");
        $this->info("     - Clientes totales:   {$totalClients}");

        $time = now()->hour;
        $app_name = config('app.name');
        $salute = $time >= 12 && $time <= 17
            ? 'Buenas tardes'
            : ($time >= 18 ? 'Buenas noches' : 'Buenos días');

        $this->info("Iniciando envío de mensaje a ganadores...");
        $winner_message = config('messages.winner');
        $looser_message = config('messages.looser');

        foreach ($winners as $key => $client) {
            $chatId = substr($client['client_phone'], 1);
            $tickets = $client['tickets'];
            $formatted_tickets = array_map(function ($number) {
                return "- *$number*";
            }, $tickets);
            $ticket_numbers = implode("\n", $formatted_tickets);

            $this->info(
                "[💬] Enviando mensaje a cliente: +58{$chatId} ({$client['client_name']})"
            );

            $message = str_replace(
                ['SALUTE', 'CLIENT_NAME', 'APP_NAME', 'LOTTERY_NAME', 'TICKETS'],
                [$salute, $client['client_name'], $app_name, $client['lottery_name'], $ticket_numbers],
                $winner_message
            );

            if ($key > 0) {
                sleep(rand(1, 3));
            }

            $process = new Process([
                "node",
                base_path('resources/js/ws_bot.js'),
                $chatId,
                $message,
                $lottery_id
            ]);
            $process->setTimeout(timeout: 300);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        }

        $this->info("[👍] Envío finalizado");
        $this->info(string: "Iniciando envío de mensaje al resto de clientes...");

        foreach ($loosers as $key => $client) {
            $chatId = substr($client['client_phone'], 1);

            $this->info(
                "[💬] Enviando mensaje a cliente: +58{$chatId} ({$client['client_name']})"
            );

            $message = str_replace(
                ['SALUTE', 'CLIENT_NAME', 'APP_NAME', 'LOTTERY_NAME'],
                [$salute, $client['client_name'], $app_name, $client['lottery_name']],
                $looser_message
            );

            if ($key > 0) {
                sleep(rand(1, 3));
            }

            $process = new Process([
                "node",
                base_path('resources/js/ws_bot.js'),
                $chatId,
                $message,
                $lottery_id
            ]);
            $process->setTimeout(timeout: 300);
            $process->run();
            if (!$process->isSuccessful()) {
                throw new ProcessFailedException($process);
            }
        }

        $this->info("[👍] Envío finalizado");
        $this->info('[✅] Mensajes enviados exitosamente');

        $end = microtime(true);
        $this->info('[🕒] Tiempo de ejecusón: ' . ($end - $start) . ' segundos');
    }
}
