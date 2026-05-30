<?php

declare(strict_types=1);

namespace Modules\IAM\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;
use Modules\Identity\Models\User;

class Policy extends Model
{
    protected $table = 'policies';

    protected $keyType = 'string';

    public $incrementing = false;

    public $primaryKey = 'id';

    protected $fillable = [
        'id', 'name', 'resource', 'action', 'effect', 'conditions',
    ];

    protected $casts = [
        'conditions' => 'array',
    ];

    public function evaluate(User $user, array $context = []): bool
    {
        if ($this->conditions === null || $this->conditions === []) {
            return true;
        }

        foreach ($this->conditions as $key => $value) {
            $resolved = match (true) {
                str_starts_with((string) $key, 'user.') => data_get($user, substr((string) $key, 5)),
                str_starts_with((string) $key, 'context.') => data_get($context, substr((string) $key, 8)),
                default => data_get($context, (string) $key),
            };

            if ((string) $resolved !== (string) $value) {
                return false;
            }
        }

        return true;
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->id ??= (string) Str::ulid();
        });
    }
}
