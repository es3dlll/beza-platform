<?php

declare(strict_types=1);

namespace App\Modules\EventBus\Models;

use Illuminate\Database\Eloquent\Model;

final class DeadLetterEvent extends Model
{
    protected $fillable = [
        'id',
        'event_id',
        'event_type',
        'consumer_name',
        'payload',
        'headers',
        'error_message',
        'error_trace',
        'attempts',
        'status',
        'failed_at',
    ];

    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'headers' => 'array',
            'failed_at' => 'datetime',
        ];
    }

    public $incrementing = false;
    protected $keyType = 'string';
}
