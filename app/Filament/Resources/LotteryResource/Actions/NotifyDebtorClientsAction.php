<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use App\Filament\Traits\HasActivityLogger;
use App\Models\Client;
use Filament\Tables\Actions\Action;
use App\Models\Lottery;
use App\Models\Ticket;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;

class NotifyDebtorClientsAction extends Action
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->name('NotifyDebtorClients');

    $this->label("Notificar a deudores")
      ->icon('heroicon-o-chat-bubble-left')
      ->hidden(
        fn(Lottery $record) =>
        $record->tickets()->whereHas('client')->whereDoesntHave('payment')->count() === 0 || (now()->format('d/m/Y') > $record->final_date)
      )
      ->action(function (Lottery $record) {
        $this->actionHandler($record->id, $record->name);

        HasActivityLogger::logActivity(null, 'notify_debtors', 'notification');

        while (!Storage::disk('public')->exists('status.json')) {
          sleep(5);
        }

        $content = json_decode(Storage::disk('public')->get('status.json'));
        $sendMessages = $content->totalMessages;
        $lotteryId = $content->data->id;
        $lotteryName = $content->data->name;
        $lotteryObjetive = $content->data->objective;

        if ($lotteryObjetive === 'debtors') {

          $ticketsCount = Ticket::where('lottery_id', $lotteryId)
            ->whereNotNull('client_id')
            ->count();

          Notification::make()
            ->title('Clientes deudores notificados')
            ->body(Str::markdown("Se notificaron (**{$sendMessages}**) clientes deudores de la rifa (**#{$lotteryId}**) (**{$lotteryName}**). Un total de (**{$ticketsCount}**) boletos fueron notificados."))
            ->success()
            ->send();

          Storage::disk('public')->delete('status.json');
        }
      });
  }
  private function actionHandler($lotteryId, $lotteryName)
  {
    $clients = $this->fetchDebtors($lotteryId);

    if (count($clients) > 0) {
      $data = $this->prepareMessages($clients);
      $this->saveClientsDataToFile($data);
      $this->saveLotteryDataToFile(array_merge(
        ['id' => $lotteryId, 'name' => $lotteryName],
        ['objective' => 'debtors']
      ));

      $this->updateTicketAlerts($clients);
    }
  }

  private function fetchDebtors($lottery_id)
  {
    $ticketPrice = Lottery::find($lottery_id)->ticket_price();

    return Client::whereHas(
      'tickets',
      fn($query) =>
      $query->pendingPayment()
        ->where('lottery_id', $lottery_id)
    )
      ->with(['tickets' => fn($query) => $query->pendingPayment()->with('lottery')])
      ->get()
      ->map(function ($client) use ($ticketPrice) {
        return [
          'client_name' => $client->full_name,
          'phone' => str_replace('-', '', substr($client->phone_number, 1)),
          'tickets' => $client->tickets->map(fn($ticket) => [
            'id' => $ticket->id,
            'number' => $ticket->number,
            'price' => $ticketPrice,
            'lottery_name' => $ticket->lottery->name
          ])
        ];
      });
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

  private function saveClientsDataToFile($data)
  {
    File::put(
      storage_path('app/public/clients.json'),
      json_encode($data, JSON_PRETTY_PRINT)
    );
  }

  private function saveLotteryDataToFile($data)
  {
    File::put(
      storage_path('app/public/lottery.json'),
      json_encode($data, JSON_PRETTY_PRINT)
    );
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
}
