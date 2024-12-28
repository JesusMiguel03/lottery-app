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
    ];

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }

    public function prizes()
    {
        return $this->hasMany(Prize::class);
    }

    public function dateRange(): Attribute
    {
        return Attribute::make(
            get: fn(string | null $value, array $attributes): string => Carbon::createFromFormat('d/m/Y', $attributes['initial_date'])->format('d-m-Y') . ' al ' . Carbon::createFromFormat('d/m/Y', $attributes['final_date'])->format('d-m-Y')
        );
    }
}
