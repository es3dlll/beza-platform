<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class WapRoute extends Model
{
    protected $fillable = [
        'method', 'pattern', 'target', 'roles', 'priority', 'is_active',
    ];

    protected function casts(): array
    {
        return [
            'roles' => 'array',
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }
}
