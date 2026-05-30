<?php

declare(strict_types=1);

namespace Modules\Remittance\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Modules\Identity\Models\User;

final class Beneficiary extends Model
{
    use HasUlids;

    protected $table = 'beneficiaries';

    protected $fillable = [
        'user_id',
        'full_name_ar',
        'full_name_en',
        'phone',
        'national_id',
        'relationship',
        'governorate',
        'city',
        'address',
        'kyc_completed',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'kyc_completed' => 'boolean',
            'metadata' => 'array',
        ];
    }

    public function user()
    {
        return $this->belongsTo(User::class);
    }
}
