<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Payment extends Model
{
    protected $fillable = [
        'amount',
        'ref',
        'type',
        'ticket_id',
        'currency_id'
    ];

    public function ticket()
    {
        return $this->belongsTo(Ticket::class);
    }

    public function currency()
    {
        return $this->belongsTo(Currency::class);
    }
}
