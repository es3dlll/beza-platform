<?php

declare(strict_types=1);

namespace Modules\Identity\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Str;

final class OtpCode extends Model
{
    use HasUlids;

    protected $keyType = 'string';

    public $incrementing = false;

    public const PURPOSE_REGISTER = 'register';

    public const PURPOSE_LOGIN = 'login';

    public const PURPOSE_CHANGE_PHONE = 'change_phone';

    public const PURPOSE_FORGOT_PIN = 'forgot_pin';

    public const MAX_ATTEMPTS = 5;

    public const EXPIRY_MINUTES = 10;

    protected $fillable = [
        'user_id',
        'phone',
        'purpose',
        'code_hash',
        'attempts',
        'max_attempts',
        'expires_at',
        'verified_at',
    ];

    protected $casts = [
        'attempts' => 'integer',
        'max_attempts' => 'integer',
        'expires_at' => 'datetime',
        'verified_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class, 'user_id', 'id');
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    public function hasReachedMaxAttempts(): bool
    {
        $max = $this->max_attempts ?? self::MAX_ATTEMPTS;

        return $this->attempts >= $max;
    }

    public function markAsVerified(): void
    {
        $this->verified_at = now();
        $this->save();
    }

    public function incrementAttempts(): void
    {
        $this->increment('attempts');
    }

    public function isValid(): bool
    {
        return ! $this->isExpired()
            && ! $this->hasReachedMaxAttempts()
            && $this->verified_at === null;
    }

    public function scopeValid(Builder $query): Builder
    {
        return $query
            ->whereNull('verified_at')
            ->where('expires_at', '>', now())
            ->whereColumn('attempts', '<', 'max_attempts');
    }

    public function scopeByPhone(Builder $query, string $phone): Builder
    {
        return $query->where('phone', $phone);
    }

    public function scopeByPurpose(Builder $query, string $purpose): Builder
    {
        return $query->where('purpose', $purpose);
    }

    public function scopeUnverified(Builder $query): Builder
    {
        return $query->whereNull('verified_at');
    }

    public function minutesUntilExpiry(): int
    {
        if ($this->expires_at === null) {
            return 0;
        }

        return (int) max(0, now()->diffInMinutes($this->expires_at, false));
    }

    public function remainingAttempts(): int
    {
        $max = $this->max_attempts ?? self::MAX_ATTEMPTS;

        return max(0, $max - $this->attempts);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (self $model) {
            if (empty($model->{$model->getKeyName()})) {
                $model->{$model->getKeyName()} = (string) Str::ulid();
            }
        });
    }
}
