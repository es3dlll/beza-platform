<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class AuditLog extends Model
{
    protected $fillable = [
        'user_id',
        'method',
        'path',
        'ip',
        'user_agent',
        'fingerprint',
    ];

    protected function casts(): array
    {
        return [
            'user_id' => 'integer',
        ];
    }
}
