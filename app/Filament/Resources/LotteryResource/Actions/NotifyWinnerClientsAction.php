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

class NotifyWinnerClientsAction extends Action
{
  protected function setUp(): void
  {
    parent::setUp();

    $this->name('NotifyWinnerClients');

    $this->label("Notificar a ganadores")
      ->icon('heroicon-o-paper-airplane')
      ->hidden(fn(Lottery $record) => $record->getWinners()->count() === 0)
      ->action(function (Lottery $record) {
        $this->actionHandler($record->id, $record->name);

        HasActivityLogger::logActivity($record, 'notify_winners', 'notification');

        while (!Storage::disk('public')->exists('status.json')) {
          sleep(5);
        }

        $content = json_decode(Storage::disk('public')->get('status.json'));
        $sendMessages = $content->totalMessages;
        $lotteryId = $content->data->id;
        $lotteryName = $content->data->name;
        $lotteryObjetive = $content->data->objective;

        if ($lotteryObjetive === 'winners') {
          Ticket::where('lottery_id', $lotteryId)->update([
            'notified_at' => now()
          ]);

          Notification::make()
            ->title('Clientes notificados')
            ->body(Str::markdown("Se notificaron (**{$sendMessages}**) clientes de la rifa (**#{$lotteryId}**) (**{$lotteryName}**)."))
            ->success()
            ->send();

          Storage::disk('public')->delete('status.json');
        }
      });
  }

  private function actionHandler($lotteryId, $lotteryName)
  {
    $winners = $this->fetchWinners($lotteryId);
    $winnerIds = collect($winners)->pluck('id')->toArray();
    $losers = $this->fetchLosers($lotteryId, $winnerIds);

    if (count($winners) > 0 || count($losers) > 0) {
      $data = $this->prepareMessages($winners, $losers);

      $this->saveClientsDataToFile($data);
      $this->saveLotteryDataToFile(array_merge(
        ['id' => $lotteryId, 'name' => $lotteryName],
        ['objective' => 'winners']
      ));
    }
  }

  private function fetchWinners($lottery_id)
  {
    return Client::whereHas('tickets', function ($query) use ($lottery_id) {
      $query->whereHas('payments')
        ->where('winner', true)
        ->where('active', true)
        ->whereHas('lottery', fn($q) => $q->where('id', $lottery_id));
    })
      ->with(['tickets' => function ($query) use ($lottery_id) {
        $query->where('winner', true)
          ->where('lottery_id', $lottery_id)
          ->with('prize');
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
            'id' => $ticket['id'],
            'prize' => $ticket->prize ? "#{$ticket['order']} *{$ticket->prize->name}* x{$ticket->prize->quantity}" : null
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

  private function fetchLosers($lottery_id, $winnerIds)
  {
    return Client::whereHas('tickets', function ($query) use ($lottery_id) {
      $query->whereHas('payments')
        ->where('winner', false)
        ->where('active', true)
        ->whereHas('lottery', fn($q) => $q->where('id', $lottery_id));
    })
      ->whereNotIn('id', $winnerIds)
      ->select([
        DB::raw("name || ' ' || last_name as client_name"),
        DB::raw("code || '' || phone as client_phone"),
      ])
      ->get()
      ->toArray();
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
      $formatted_tickets = [];
      foreach ($tickets as $ticket) {
        $prizeInfo = "";
        if ($ticket['prize']) {
          $prizeInfo = " (Premio: {$ticket['prize']})";
        }
        $formatted_tickets[] = "- *{$ticket['number']}* {$prizeInfo}";
      }

      $ticket_numbers = implode("\n", $formatted_tickets);
      $message = str_replace(
        ['SALUTE', 'CLIENT_NAME', 'APP_NAME', 'LOTTERY_NAME', 'TICKETS'],
        [$salute, $client['client_name'], $app_name, $client['lottery_name'], $ticket_numbers],
        $winner_message
      );
      $data[] = [
        'message' => $message,
        'chatId' => $chatId
      ];
    }

    foreach ($losers as $client) {
      $chatId = substr($client['client_phone'], 1);
      $message = str_replace(
        ['SALUTE', 'CLIENT_NAME', 'APP_NAME', 'LOTTERY_NAME'],
        [$salute, $client['client_name'], $app_name, ''],
        $looser_message
      );
      $data[] = [
        'message' => $message,
        'chatId' => $chatId
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
}
