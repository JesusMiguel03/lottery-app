<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Prize extends Model
{
    protected $fillable = [
        'name',
        'quantity',
        'value',
        'order'
    ];

    public function lottery()
    {
        return $this->belongsTo(Lottery::class);
    }

    public function winner()
    {
        return $this->lottery->tickets()->where('order', $this->order);
    }
}
