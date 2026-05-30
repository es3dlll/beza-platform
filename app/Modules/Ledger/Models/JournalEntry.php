<?php
declare(strict_types=1);

namespace Modules\Ledger\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphOne;
use Illuminate\Support\Str;

class JournalEntry extends Model
{
    protected $table = 'journal_entries';

    protected $keyType = 'string';

    public $incrementing = false;

    protected $primaryKey = 'id';

    protected $fillable = [
        'id', 'reference_type', 'reference_id', 'description', 'description_ar',
        'entry_date', 'currency', 'total_debit', 'total_credit',
        'status', 'reversal_of', 'created_by', 'approved_by',
    ];

    protected $casts = [
        'entry_date' => 'date',
        'total_debit' => 'integer',
        'total_credit' => 'integer',
    ];

    public function lines(): HasMany
    {
        return $this->hasMany(JournalLine::class, 'journal_entry_id');
    }

    public function transaction(): MorphOne
    {
        return $this->morphOne(LedgerTransaction::class, 'transactionable');
    }

    public function reversalOf(): BelongsTo
    {
        return $this->belongsTo(self::class, 'reversal_of');
    }

    public function reversedBy(): HasMany
    {
        return $this->hasMany(self::class, 'reversal_of');
    }

    public function post(): void
    {
        if ($this->status !== 'draft') {
            throw new \RuntimeException('Only draft entries can be posted.');
        }

        if (!$this->isBalanced()) {
            throw new \RuntimeException('Cannot post an unbalanced journal entry.');
        }

        \DB::transaction(function () {
            $this->status = 'posted';
            $this->save();

            foreach ($this->lines as $line) {
                $account = $line->account;

                if ($line->type === 'debit') {
                    $account->debit($line->amount);
                } else {
                    $account->credit($line->amount);
                }

                $account->save();
            }
        });
    }

    public function reverse(string $reason): JournalEntry
    {
        if ($this->status !== 'posted') {
            throw new \RuntimeException('Only posted entries can be reversed.');
        }

        $reversal = \DB::transaction(function () use ($reason) {
            $reversal = new self();
            $reversal->id = (string) Str::ulid();
            $reversal->reference_type = $this->reference_type;
            $reversal->reference_id = $this->reference_id;
            $reversal->description = $reason;
            $reversal->description_ar = $this->description_ar;
            $reversal->entry_date = now()->toDateString();
            $reversal->currency = $this->currency;
            $reversal->status = 'posted';
            $reversal->reversal_of = $this->id;
            $reversal->created_by = $this->created_by;
            $reversal->save();

            foreach ($this->lines as $line) {
                $reversalLine = new JournalLine();
                $reversalLine->id = (string) Str::ulid();
                $reversalLine->journal_entry_id = $reversal->id;
                $reversalLine->account_id = $line->account_id;
                $reversalLine->type = $line->type === 'debit' ? 'credit' : 'debit';
                $reversalLine->amount = $line->amount;
                $reversalLine->currency = $line->currency;
                $reversalLine->description = $reason;
                $reversalLine->description_ar = $line->description_ar;
                $reversalLine->save();

                $account = $line->account;
                if ($reversalLine->type === 'debit') {
                    $account->debit($line->amount);
                } else {
                    $account->credit($line->amount);
                }
                $account->save();
            }

            $reversal->total_debit = $this->total_credit;
            $reversal->total_credit = $this->total_debit;
            $reversal->save();

            $this->status = 'reversed';
            $this->save();

            return $reversal;
        });

        return $reversal;
    }

    public function isBalanced(): bool
    {
        return $this->total_debit === $this->total_credit;
    }

    public function scopeByDate(Builder $query, string $from, string $to): Builder
    {
        return $query->whereBetween('entry_date', [$from, $to]);
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Model $model) {
            $model->id ??= (string) Str::ulid();
        });
    }
}
