<?php

namespace App\Livewire;

use App\Models\Ticket;
use Illuminate\Database\Eloquent\Model;
use Livewire\Component;

class LotteryTicketsComponent extends Component
{
    public $record, $tickets, $clients;
    public $selectedTicket = null;
    public $ticketNumber, $ticketClientName, $ticketPrice, $ticketPaymentAmount, $ticketPaymentType, $ticketPayment, $ticketPaymentRef, $ticketWinner, $ticketWinnerOrder, $ticketNotified;

    public function mount(Model $record): void
    {
        $this->record = $record;

        $clients = [];
        $this->tickets = $record->tickets->map(function ($ticket) use (&$clients) {
            $client = $ticket->client;
            $color = $client ? 'success' : 'danger';

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
            $this->ticketPrice = $this->record->ticket_price();
            $this->ticketPayment = $ticket->payment;
            $this->ticketPaymentAmount = $ticket->payment->amount ?? 0;
            $this->ticketPaymentType = $ticket->payment->type_translated ?? '';
            $this->ticketPaymentRef = $ticket->payment->ref ?? '';
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
