<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Change extends Model
{
    protected $fillable = [
        'amount',
        'type',
        'ref',
        'confirmed',
        'currency_id',
        'ticket_id'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }

    public function getChangeFormattedWithRefAttribute()
    {
        $changeTypes = [
            'usd' => '$',
            'other' => '$',
            'payment' => 'Bs',
            'bs' => 'Bs'
        ];

        $ref = $this->ref ? ", Ref: {$this->ref}" : '';
        $amount = round($this->amount);

        return "{$amount} {$changeTypes[$this->type]}{$ref}";
    }

    public function getPayedAmountAttribute()
    {
        $type = $this->type;
        return $type === 'bs' || $type === 'payment'
            ? $this->amount / $this->currency->value
            : $this->amount;
    }
}
