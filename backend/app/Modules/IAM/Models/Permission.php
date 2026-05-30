<?php

declare(strict_types=1);

namespace Modules\IAM\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Support\Str;

final class Permission extends Model
{
    protected $keyType = 'string';

    public $incrementing = false;

    public $primaryKey = 'id';

    protected $fillable = [
        'id', 'name', 'guard_name', 'module', 'description',
    ];

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'role_permissions', 'permission_id', 'role_id')
            ->withTimestamps();
    }

    public function scopeByModule(Builder $query, string $module): Builder
    {
        return $query->where('module', $module);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->id ??= (string) Str::ulid();
        });
    }
}
