<?php

declare(strict_types=1);

namespace App\Modules\AuditLog\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;

final class AuditLog extends Model
{
    use HasUlids;

    const UPDATED_AT = null;

    protected $fillable = [
        'user_id',
        'action',
        'resource_type',
        'resource_id',
        'metadata',
        'ip_address',
        'user_agent',
        'result',
    ];

    protected function casts(): array
    {
        return [
            'metadata' => 'array',
        ];
    }
}
