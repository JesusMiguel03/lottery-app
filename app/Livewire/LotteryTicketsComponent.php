<?php

namespace App\Livewire;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class LotteryTicketsComponent extends Component
{
    public $record, $tickets, $clients;
    public $selectedTicket = null;
    public $ticketNumber, $ticketClientName, $ticketPrice, $ticketPayment, $ticketWinner, $ticketWinnerOrder, $ticketNotified;

    public function mount(Model $record): void
    {
        $this->record = $record;

        $clients = [];

        $ticket_price = $record->ticket_price;
        $this->tickets = $record->tickets->map(function ($ticket) use (&$clients, $ticket_price) {
            $client = $ticket->client;
            $color = $ticket->total_payed === 0 ? 'pending' : ($ticket->total_payed !== $ticket_price && $client
                ? 'not_payed'
                : ($client ? 'success' : 'danger'));

            if ($client) {
                if (isset($clients[$client->id])) {
                    $clients[$client->id]['tickets'][] = $ticket->id;
                } else {
                    $clients[$client->id] = [
                        'name' => $client->full_name,
                        'tickets' => [$ticket->id]
                    ];
                }
            }

            return [
                'color' => $color,
                'number' => $ticket->number,
                'id' => $ticket->id
            ];
        });
        $this->clients = $clients;
    }

    public function selectTicket($ticketId)
    {
        $this->selectedTicket = $ticketId;
        $ticket = Ticket::find($ticketId);
        if ($ticket) {
            $this->ticketNumber = $ticket->number;
            $this->ticketClientName = $ticket->client->full_name ?? 'Sin asignar';
            $this->ticketPrice =
                $ticket->total_payed === $this->record->ticket_price || $ticket->total_payed == 0
                ? $this->record->ticket_price : "{$ticket->total_payed} $ / {$this->record->ticket_price}";
            $this->ticketPayment = $ticket->payments;
            $this->ticketWinner = $ticket->winner;
            $this->ticketWinnerOrder = $ticket->order;
            $this->ticketNotified = $ticket->notified_at
                ? ($ticket->notified_at)->translatedFormat('Y-m-d h:i:s a')
                : 'Pendiente';
        }

        $this->dispatch($ticketId ? 'open-modal' : 'close-modal', id: 'ticketModal');
    }

    public function render()
    {
        return view('livewire.lottery-tickets-component');
    }
}
