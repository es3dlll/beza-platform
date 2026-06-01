<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Transaction extends Model
{
    protected $fillable = [
        'sender_wallet_id',
        'receiver_wallet_id',
        'amount',
        'currency',
        'type',
        'status',
        'idempotency_key',
        'note',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'integer',
        ];
    }
}
