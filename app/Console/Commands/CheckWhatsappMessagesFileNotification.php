<?php

namespace App\Console\Commands;

use App\Models\Ticket;
use App\Models\User;
use Filament\Notifications\Notification;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class CheckWhatsappMessagesFileNotification extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'file:check';

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
        $interval = 10;

        $maxIterations = 60; // Set the maximum number of iterations
        $iterations = 0;

        while ($iterations < $maxIterations) {
            $iterations++;
            try {
                if (Storage::disk('public')->exists('status.json')) {
                    $content = json_decode(Storage::disk('public')->get('status.json'));
                    $sendMessages = $content->totalMessages;
                    $lotteryId = $content->data->id;
                    $lotteryName = $content->data->name;
                    $lotteryObjetive = $content->data->objective;
                    $user = User::find(1);

                    if ($lotteryObjetive === 'winners') {
                        Ticket::where('lottery_id', $lotteryId)->update([
                            'notified_at' => now()
                        ]);

                        Notification::make()
                            ->title('Clientes notificados')
                            ->body(Str::markdown("Se notificaron (**{$sendMessages}**) clientes de la rifa (**#{$lotteryId}**) (**{$lotteryName}**)."))
                            ->success()
                            ->sendToDatabase($user);
                    } else {
                        $ticketsCount = Ticket::where('lottery_id', $lotteryId)
                            ->whereNotNull('client_id')
                            ->count();

                        Notification::make()
                            ->title('Clientes deudores notificados')
                            ->body(Str::markdown("Se notificaron (**{$sendMessages}**) clientes deudores de la rifa (**#{$lotteryId}**) (**{$lotteryName}**). Un total de (**{$ticketsCount}**) boletos fueron notificados."))
                            ->success()
                            ->sendToDatabase($user);
                    }
                    $this->info("Notificación enviada: " . now());

                    Storage::disk('public')->delete('status.json');
                } else {
                    $this->comment("Archivo no encontrado: " . now());
                }

                sleep($interval);
            } catch (\Exception $e) {
                $this->error("Error: " . $e->getMessage());
                sleep(60);
            }
        }
    }
}
