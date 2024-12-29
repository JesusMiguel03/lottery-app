<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Ticket extends Model
{
    protected $fillable = [
        'active',
        'winner',
        'number',
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
}
