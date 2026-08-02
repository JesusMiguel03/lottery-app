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
        'change_value',
        'client_id',
        'lottery_id',
        'prize_id',
        'created_at',
        'updated_at',
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }

    public function payments()
    {
        return $this->hasMany(Payment::class);
    }

    public function prize()
    {
        return $this->belongsTo(Prize::class);
    }

    public function changesM()
    {
        return $this->hasMany(Change::class);
    }

    public function lottery()
    {
        return $this->belongsTo(Lottery::class);
    }

    public function getTicketOwnerNameAttribute()
    {
        return "#{$this->number}. {$this->client->full_name}";
    }

    public function scopePendingPayment($query)
    {
        return $query->whereDoesntHave('payments');
    }

    public function getTotalPayedAttribute()
    {
        return round($this->payments->sum('payed_amount'), 2);
    }

    public function getTotalChangeAttribute()
    {
        return round($this->changesM->sum('payed_amount'), 2);
    }
}
