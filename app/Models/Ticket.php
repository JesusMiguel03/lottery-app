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
        'lottery_id'
    ];

    public function client()
    {
        return $this->belongsTo(Client::class);
    }
}
