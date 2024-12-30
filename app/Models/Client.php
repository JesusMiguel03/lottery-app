<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Casts\Attribute;
use Illuminate\Database\Eloquent\Model;

class Client extends Model
{
    protected $fillable = [
        'name',
        'last_name',
        'doc',
        'doc_type',
        'code',
        'phone'
    ];

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn(string | null $value, array $attributes): string => $attributes['name'] . ' ' . $attributes['last_name']
        );
    }
    public function document(): Attribute
    {
        return Attribute::make(
            get: fn(string | null $value, array $attributes): string => $attributes['doc_type'] . '-' . $attributes['doc']
        );
    }
    public function phoneNumber(): Attribute
    {
        return Attribute::make(
            get: fn(string | null $value, array $attributes): string => $attributes['code'] . $attributes['phone']
        );
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function get_lotteries()
    {
        return $this->tickets()->with('lottery')->get()->unique()->pluck('lottery.name', 'lottery_id')->toArray();
    }

    public function get_tickets_count($filter)
    {
        $query = $this->tickets();
        if ($filter === 'monthly') {
            $firstDayOfMonth = now()->startOfMonth();
            $lastDayOfMonth = now()->endOfMonth();
            $query->whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth]);
        }

        return $query->count();
    }

    public function get_lotteries_count($filter)
    {
        $query = $this->tickets()->with('lottery')->distinct('lottery_id');
        if ($filter === 'monthly') {
            $firstDayOfMonth = now()->startOfMonth();
            $lastDayOfMonth = now()->endOfMonth();
            $query->whereBetween('created_at', [$firstDayOfMonth, $lastDayOfMonth]);
        }

        return $query->count('lottery_id');
    }

    public function get_total_payed()
    {
        return $this->tickets()
            ->has('payment')
            ->withSum('payment as total_payment_amount', 'amount')->get()->sum('total_payment_amount');
    }

    public function get_total_debt()
    {
        return array_sum($this->tickets()
            ->with('lottery')
            ->whereDoesntHave('payment')
            ->get()
            ->map(
                function ($ticket) {
                    return $ticket->lottery->ticket_price();
                }
            )->toArray());
    }

    public function get_lotteries_won()
    {
        return $this->tickets()
            ->with('lottery')
            ->distinct('lottery_id')
            ->where('winner', 1)
            ->count();
    }

    public function get_estimated_prizes_value()
    {
        return $this->tickets()
            ->where('winner', 1)
            ->with('lottery.prizes')
            ->get()
            ->sum(fn($ticket) => $ticket->lottery->prizes->sum('value'));
    }

    public function get_pending_tickets()
    {
        return $this->tickets()->with('lottery')->whereDoesntHave('payment')->get();
    }
}
