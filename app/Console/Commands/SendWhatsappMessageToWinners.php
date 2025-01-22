<?php

namespace App\Console\Commands;

use App\Models\Client;
use App\Models\Lottery;
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

        $lottery = Lottery::select(['name'])->find($lottery_id)->toArray();

        $this->info("Obteniendo ganadores...");
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

        $this->info("Clientes encontrados");
        $this->info("Obteniendo resto de clientes...");

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

        $this->info("Clientes encontrados");
        $this->info("Clientes ganadores:",  count($winners));
        $this->info("Clientes totales:", count($winners) + count($loosers));

        $time = now()->hour;
        $app_name = config('app.name');
        $salute = $time >= 12 && $time <= 17
            ? 'Buenas tardes'
            : ($time >= 18 ? 'Buenas noches' : 'Buenos días');

        $this->info("Iniciando envío de mensaje a ganadores...");
        foreach ($loosers as $key => $client) {
            $chatId = substr($client['client_phone'], 1);

            $message = "{$salute} *{$client['client_name']}*, reciba un cordial saludo de parte de _{$app_name}_, nos comunicamos con usted para hacerle notificación de que no fue seleccionado como uno de los ganadores de la rifa *{$lottery['name']}*, gracias por su participación en la rifa. Que tenga un felíz día";

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
        $this->info("Envío finalizado");

        $this->info(string: "Iniciando envío de mensaje al resto de clientes...");
        foreach ($winners as $key => $client) {
            $chatId = substr($client['client_phone'], 1);
            $tickets = $client['tickets'];
            $ticket_numbers = implode(', ', $tickets);

            $message = "{$salute} *{$client['client_name']}*, reciba un cordial saludo de parte de _{$app_name}_, nos comunicamos con usted para hacerle notificación de que ha sido seleccionado como uno de los ganadores de la rifa *{$client['lottery_name']}* de la cual cuenta con los boletos: (*{$ticket_numbers}*), por favor ponerse en contácto para retirar su premio. Que tenga un felíz día";

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

        $this->info("Envío finalizado");
        $this->info('Mensajes enviados exitosamente');

        $end = microtime(true);
        $this->info('Tiempo de ejecusón: ' . ($end - $start) . ' segundos');
    }
}
