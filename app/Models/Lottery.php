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
            ->pluck('number', 'id')
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

        return $this->tickets->count() / $this->total_price;
    }
}
