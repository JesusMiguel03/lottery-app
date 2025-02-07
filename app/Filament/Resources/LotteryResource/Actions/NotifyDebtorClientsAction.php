<?php

namespace App\Filament\Resources\LotteryResource\Actions;

use App\Filament\Traits\HasActivityLogger;
use App\Models\Client;
use Filament\Tables\Actions\Action;
use App\Models\Lottery;
use App\Models\Ticket;
use Filament\Notifications\Notification;
use Illuminate\Support\Facades\DB;
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
        fn(Lottery $record) => $record->tickets()->whereHas('client')->whereDoesntHave('payments')->count() === 0 || (now()->format('d/m/Y') > $record->final_date)
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
    $ticketPrice = Lottery::find($lottery_id)->ticket_price;

    return Client::whereHas('tickets', function ($query) use ($lottery_id) {
      $query->where('lottery_id', $lottery_id);
    })
      ->with(['tickets' => function ($query) use ($lottery_id) {
        $query->where('lottery_id', $lottery_id)
          ->with('lottery', 'payments')
          ->selectRaw("tickets.*, COALESCE(SUM(CASE 
                    WHEN payments.type IN ('bs', 'payment') THEN payments.amount / (SELECT value FROM currencies WHERE id = payments.currency_id) 
                    ELSE payments.amount 
                    END), 0) as total_paid")
          ->leftJoin('payments', 'tickets.id', '=', 'payments.ticket_id')
          ->leftJoin('currencies', 'payments.currency_id', '=', 'currencies.id')
          ->groupBy('tickets.id', 'tickets.number', 'tickets.lottery_id', 'tickets.client_id')
          ->havingRaw("(COALESCE(total_paid, 0) = 0 OR COALESCE(total_paid, 0) < (SELECT ticket_price FROM lotteries WHERE id = tickets.lottery_id))"); // Corrected having clause
      }])
      ->select([
        DB::raw("name || ' ' || last_name as client_name"),
        DB::raw("code || '' || phone as client_phone"),
        '*'
      ])
      ->get()
      ->map(function ($client) use ($ticketPrice) {
        $unpaidTickets = $client->tickets; // All tickets match the criteria

        $totalDebt = $unpaidTickets->sum(function ($ticket) use ($ticketPrice) {
          return  $ticketPrice - $ticket->total_paid; // Calculate debt for each ticket
        });

        return [
          'client_name' => $client['client_name'],
          'client_phone' => $client['client_phone'],
          'tickets' => $unpaidTickets->map(function ($ticket) use ($ticketPrice) {
            $debt = $ticketPrice - $ticket->total_paid;
            return [
              'id' => $ticket->id,
              'number' => $ticket->number,
              'price' => $ticketPrice,
              'lottery_name' => $ticket->lottery->name,
              'debt' => $debt > 0 ? $debt : 0
            ];
          }),
          'total_debt' => $totalDebt > 0 ? $totalDebt : 0,
        ];
      });
  }

  private function prepareMessages($clients)
  {
    $salute = $this->getGreeting();
    $appName = config('app.name');
    $debtor_message = config('messages.debtor');

    $data = [];

    foreach ($clients as $client) {
      $chatId = $client['client_phone'];
      $tickets = $client['tickets'];
      $formatted_tickets = [];
      $totalDebt = 0;

      foreach ($tickets as $ticket) {
        $debt = $ticket['debt'];
        $formatted_tickets[] = "- *{$ticket['number']}* (Rifa: {$ticket['lottery_name']}, Deuda: {$debt} $)";
        $totalDebt += $debt;
      }

      $ticket_numbers = implode("\n", $formatted_tickets);
      $message = str_replace(
        ['SALUTE', 'CLIENT_NAME', 'APP_NAME', 'TOTAL_TICKETS', 'TICKETS', 'TOTAL_DEBT'],
        [$salute, $client['client_name'], $appName, count($tickets), $ticket_numbers, $totalDebt],
        $debtor_message
      );

      $data[] = [
        'message' => $message,
        'chatId' => substr($chatId, 1)
      ];
    }

    return $data;
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
