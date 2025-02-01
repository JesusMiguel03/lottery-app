<?php

namespace App\Models;

use Carbon\Carbon;
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

    protected static function booted()
    {
        static::deleting(function ($client) {
            $client->tickets()->update([
                'client_id' => null,
                'alerts' => 0,
                'winner' => false,
                'notified_at' => null,
            ]);
        });
    }

    public function getFullNameAttribute()
    {
        return "{$this->name} {$this->last_name}";
    }

    public function getDocumentAttribute()
    {
        return "{$this->doc_type}-{$this->doc}";
    }

    public function getPhoneNumberAttribute()
    {
        return "{$this->code}-{$this->phone}";
    }

    public function getPendingTicketsJson()
    {
        $tickets = $this->tickets()
            ->with(['lottery' => function ($query) {
                $query->select(['id', 'name', 'total_tickets', 'total_price', 'initial_date', 'final_date']);
            }])
            ->whereDoesntHave('payment')
            ->select(['id', 'number', 'lottery_id'])
            ->get()
            ->toArray();

        $processed_tickets = [];
        foreach ($tickets as $ticket) {
            $ticket_price = round($ticket['lottery']['total_price'] / $ticket['lottery']['total_tickets'], 2);
            $processed_tickets[] = [
                'id' => $ticket['id'],
                'number' => $ticket['number'],
                'name' => $ticket['lottery']['name'],
                'price' => $ticket_price,
                'initial_date' => Carbon::createFromFormat(
                    'd/m/Y',
                    $ticket['lottery']['initial_date']
                )->translatedFormat('l, d M Y'),
                'final_date' => Carbon::createFromFormat(
                    'd/m/Y',
                    $ticket['lottery']['final_date']
                )->translatedFormat('l, d M Y')
            ];
        }

        return $processed_tickets;
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

    public function get_prizes()
    {
        return $this->tickets()
            ->where('winner', 1)
            ->with('lottery.prizes')
            ->get()
            ->map(fn($ticket) => $ticket->lottery->prizes);
    }
}
