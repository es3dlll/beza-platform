<?php

declare(strict_types=1);

namespace App\Modules\Compliance\Models;

use Illuminate\Database\Eloquent\Model;

final class SanctionList extends Model
{
    protected $table = 'compliance_sanction_lists';

    protected $fillable = [
        'name',
        'alias',
        'phone',
        'device_fingerprint',
        'source',
        'match_type',
        'country',
        'sanction_ref',
        'active',
    ];

    protected $casts = [
        'active' => 'boolean',
    ];
}
