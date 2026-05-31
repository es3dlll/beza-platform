<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class LedgerEntry extends Model
{
    use HasFactory, HasUlids;

    protected $fillable = [
        'debit_wallet_id',
        'credit_wallet_id',
        'amount_fils',
        'currency',
        'description',
        'reference_type',
        'reference_id',
        'metadata',
    ];

    protected function casts(): array
    {
        return [
            'amount_fils' => 'integer',
            'metadata' => 'array',
        ];
    }

    // Append-only guard: prevent updates and deletes.
    public static function boot(): void
    {
        parent::boot();

        static::updating(function (): never {
            throw new \RuntimeException('سجل دفتر الأستاذ غير قابل للتعديل');
        });

        static::deleting(function (): never {
            throw new \RuntimeException('سجل دفتر الأستاذ غير قابل للحذف');
        });
    }
}
