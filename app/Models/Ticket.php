<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'active',
        'order',
        'winner',
        'number',
        'alerts',
        'notified_at',
        'client_id',
        'lottery_id',
        'created_at',
        'updated_at',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function payment()
    {
        return $this->hasOne(Payment::class);
    }

    public function lottery()
    {
        return $this->belongsTo(Lottery::class);
    }

    public function getTicketOwnerNameAttribute()
    {
        return "#{$this->number}. {$this->client->fullName}";
    }

    public function scopePendingPayment($query)
    {
        return $query->whereDoesntHave('payment');
    }
}
