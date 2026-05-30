<?php

declare(strict_types=1);

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

final class Profile extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    protected $fillable = [
        'user_id',
        'full_name',
        'full_name_ar',
        'national_id',
        'date_of_birth',
        'gender',
        'address',
        'city',
        'province',
    ];

    protected $casts = [
        'date_of_birth' => 'date',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function fullNameArabic(): ?string
    {
        if ($this->full_name_ar !== null) {
            return $this->full_name_ar;
        }

        if ($this->full_name === null) {
            return null;
        }

        $parts = explode(' ', $this->full_name);

        if (count($parts) < 2) {
            return $this->full_name;
        }

        $first = $parts[0];
        $last = end($parts);

        $arabicNames = config('identity.arabic_name_map', []);

        $firstAr = $arabicNames[$first] ?? $first;
        $lastAr = $arabicNames[$last] ?? $last;

        return "{$firstAr} {$lastAr}";
    }
}
