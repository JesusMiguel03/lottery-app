<?php

namespace App\Models;

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

    public function fullName(): Attribute
    {
        return Attribute::make(
            get: fn(string | null $value, array $attributes): string => $attributes['name'] . ' ' . $attributes['last_name']
        );
    }
    public function document(): Attribute
    {
        return Attribute::make(
            get: fn(string | null $value, array $attributes): string => $attributes['doc_type'] . '-' . $attributes['doc']
        );
    }
    public function phoneNumber(): Attribute
    {
        return Attribute::make(
            get: fn(string | null $value, array $attributes): string => $attributes['code'] . $attributes['phone']
        );
    }

    public function tickets()
    {
        return $this->hasMany(Ticket::class);
    }
}
