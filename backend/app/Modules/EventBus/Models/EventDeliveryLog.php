<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Models;

use Illuminate\Database\Eloquent\Model;

final class EventDeliveryLog extends Model
{
    protected $fillable = [
        'id',
        'event_id',
        'event_type',
        'status',
        'payload',
        'consumer_name',
        'attempt',
        'error_message',
        'delivered_at',
        'consumed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'delivered_at' => 'datetime',
            'consumed_at' => 'datetime',
        ];
    }

    public $incrementing = false;
    protected $keyType = 'string';
}
