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

    public function totalPayed(): Attribute
    {
        return Attribute::make(
            get: function () {
                $ticket_price = $this->ticket_price();
                $tickets = $this->tickets()
                    ->whereHas('payment')
                    ->where(function ($query) {
                        return $query->whereNotNull('id');
                    })
                    ->select('id')
                    ->count();

                $total_price = $ticket_price * $tickets;
                return $total_price;
            }
        );
    }

    public function dateRange(): Attribute
    {
        return Attribute::make(
            get: fn(string | null $value, array $attributes): string => Carbon::createFromFormat('d/m/Y', $attributes['initial_date'])->format('d-m-Y') . ' al ' . Carbon::createFromFormat('d/m/Y', $attributes['final_date'])->format('d-m-Y')
        );
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
            ->whereDoesntHave('payment')
            ->whereHas('client')
            ->join('clients', 'tickets.client_id', '=', 'clients.id')
            ->selectRaw("tickets.id as id, number, (clients.name || ' ' || clients.last_name) as client_name")
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

    public function ticket_price()
    {
        if (empty($this->total_price)) {
            return 0;
        }

        return $this->total_price / $this->tickets->count();
    }

    public function get_payed_tickets()
    {
        return $this->tickets()->whereHas('payment')->get();
    }

    public function get_winners()
    {
        return $this->tickets()->where('winner', true)->with('client')->get();
    }
}
