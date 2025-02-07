<?php

namespace App\Models;

use Carbon\Carbon;
use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Lottery extends Model
{
    protected $fillable = [
        'name',
        'description',
        'finished_at',
        'total_winners',
        'total_tickets',
        'ticket_price',
        'initial_date',
        'final_date',
        'total_price'
    ];

    public function totalLeft(): Attribute
    {
        return Attribute::make(
            get: function () {
                if (empty($this->total_price)) {
                    return 0;
                }

                return $this->total_price - $this->totalPayed;
            }
        );
    }

    public function getTotalPayedAttribute()
    {
        $tickets = $this->tickets()
            ->whereHas('payments')
            ->where(function ($query) {
                return $query->whereNotNull('id');
            })
            ->select('id')
            ->count();

        $total_price = $this->ticket_price * $tickets;
        return $total_price;
    }

    public function getDateRangeAttribute()
    {
        $start = Carbon::createFromFormat('d/m/Y', $this->initial_date);
        $end = Carbon::createFromFormat('d/m/Y', $this->final_date);
        $diff = $start->diffInDays($end);
        $plural = $diff >= 2 ? 's' : '';
        return "{$diff} día{$plural}";
    }

    public function getFullName(): Attribute
    {
        return Attribute::make(
            get: fn(string | null $value, array $attributes): string => "#{$attributes['id']} - {$attributes['name']}"
        );
    }

    public function isActive(): Attribute
    {
        return Attribute::make(
            get: fn(string | null $value, array $attributes) => Carbon::now()->between(Carbon::createFromFormat('d/m/Y', $attributes['initial_date']), Carbon::createFromFormat('d/m/Y', $attributes['final_date'])) ? 'Disponible' : 'No disponible'
        );
    }

    public function getTicketsOccupedAttribute()
    {
        return $this->tickets->whereNotNull('client_id')->count();
    }

    public function getLotteryDateAttribute()
    {
        if ($this->finished_at) {
            return Carbon::createFromFormat('Y-m-d H:i:s', $this->finished_at)->translatedFormat('l, d M Y');
        }

        return 'Pendiente';
    }

    public function totalPrizesValue(): Attribute
    {
        return Attribute::make(
            get: fn(string | null $value, array $attributes) => $this->prizes->sum('value')
        );
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function prizes()
    {
        return $this->hasMany(Prize::class);
    }

    public function not_payed_tickets()
    {
        return $this->tickets()
            ->whereHas('client')
            ->join('clients', 'tickets.client_id', '=', 'clients.id')
            ->selectRaw("tickets.id as id, number, (clients.name || ' ' || clients.last_name) as client_name")
            ->selectRaw("tickets.*")
            ->selectRaw("COALESCE(SUM(CASE 
        WHEN payments.type IN ('bs', 'payment') THEN payments.amount / currencies.value 
        ELSE payments.amount 
        END), 0) as total_paid")
            ->leftJoin('payments', 'tickets.id', '=', 'payments.ticket_id')
            ->leftJoin('currencies', 'payments.currency_id', '=', 'currencies.id')
            ->groupBy('tickets.id', 'number', 'clients.name', 'clients.last_name', 'tickets.lottery_id')
            ->havingRaw("COALESCE(SUM(CASE 
        WHEN payments.type IN ('bs', 'payment') THEN payments.amount / currencies.value 
        ELSE payments.amount 
        END), 0) < (SELECT ticket_price FROM lotteries WHERE id = tickets.lottery_id)")
            ->get()
            ->mapWithKeys(function ($item) {
                return [$item->id => "{$item->number} - {$item->client_name}"];
            })
            ->toArray();
    }

    public function tickets_left()
    {
        return $this->tickets->where('client_id', null)->pluck('id')->toArray();
    }

    public function get_by_client(Client $client)
    {
        return $this->tickets->where('client_id', $client->id)->pluck('id')->toArray();
    }

    public function get_payed_tickets()
    {
        return $this->tickets()
            ->whereHas('client')
            ->join('clients', 'tickets.client_id', '=', 'clients.id')
            ->selectRaw("tickets.id as id, number, (clients.name || ' ' || clients.last_name) as client_name")
            ->selectRaw("tickets.*")
            ->selectRaw("COALESCE(SUM(CASE 
        WHEN payments.type IN ('bs', 'payment') THEN payments.amount / currencies.value 
        ELSE payments.amount 
        END), 0) as total_paid")
            ->leftJoin('payments', 'tickets.id', '=', 'payments.ticket_id')
            ->leftJoin('currencies', 'payments.currency_id', '=', 'currencies.id')
            ->groupBy('tickets.id', 'number', 'clients.name', 'clients.last_name', 'tickets.lottery_id')
            ->havingRaw("COALESCE(SUM(CASE 
        WHEN payments.type IN ('bs', 'payment') THEN payments.amount / currencies.value 
        ELSE payments.amount 
        END), 0) >= (SELECT ticket_price FROM lotteries WHERE id = tickets.lottery_id)") // Changed < to >=
            ->get();
    }

    public function getWinners()
    {
        return $this->tickets()->where('winner', true)->with('client')->get();
    }

    public function getNotifiedTickets()
    {
        return $this->tickets()->whereNotNull('notified_at')->get();
    }
}
