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

    public function getPayedAmountAttribute()
    {
        $type = $this->type;
        return $type === 'bs' || $type === 'payment'
            ? $this->amount / $this->currency->value
            : $this->amount;
    }

    public function getTypeTranslatedAttribute()
    {
        $types = [
            'usd' => 'Dólares efectivo',
            'bs' => 'Bolìvares efectivo',
            'payment' => 'Pago mòvil',
            'other' => 'Otros, divisas'
        ];

        return "{$types[$this->type]}";
    }

    public function getPaymentFormattedAttribute()
    {
        $paymentTypes = [
            'usd' => '$',
            'other' => '$',
            'payment' => 'Bs',
            'bs' => 'Bs'
        ];

        return "{$this->amount} {$paymentTypes[$this->type]}";
    }

    public function getPaymentFormattedWithRefAttribute()
    {
        $paymentTypes = [
            'usd' => '$',
            'other' => '$',
            'payment' => 'Bs',
            'bs' => 'Bs'
        ];

        $ref = $this->ref ? ", Ref: {$this->ref}" : '';

        return "{$this->amount} {$paymentTypes[$this->type]}{$ref}";
    }
}
