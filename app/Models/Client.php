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
}
