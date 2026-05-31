<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

final class JournalEntry extends Model
{
    use HasFactory;

    protected $table = 'journal_entries';

    protected $fillable = [
        'id',
        'transaction_id',
        'description',
        'description_ar',
        'previous_hash',
        'hash',
        'metadata',
    ];

    protected $casts = [
        'metadata' => 'array',
    ];

    public $incrementing = false;

    protected $keyType = 'string';

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_entry_id');
    }

    public function previousEntry(): BelongsTo
    {
        return $this->belongsTo(self::class, 'previous_hash', 'hash');
    }
}
