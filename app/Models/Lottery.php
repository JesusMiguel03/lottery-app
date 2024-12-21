<?php

namespace App\Models;

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
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function prizes()
    {
        return $this->hasMany(Prize::class);
    }
}
