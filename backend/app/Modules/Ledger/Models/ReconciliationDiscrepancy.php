<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Models;

use App\Domain\ValueObjects\Money;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;

final class ReconciliationDiscrepancy extends Model
{
    protected $table = 'reconciliation_discrepancies';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'report_id', 'account_id', 'discrepancy_type', 'severity',
        'expected_balance', 'actual_balance', 'difference', 'currency',
        'journal_line_id', 'transaction_reference', 'context', 'description',
        'resolution_steps', 'resolution_status', 'resolution_notes',
        'resolved_by', 'resolved_at', 'requires_cbs_notification', 'cbs_case_reference',
    ];

    protected $casts = [
        'context' => 'array',
        'resolution_steps' => 'array',
        'requires_cbs_notification' => 'boolean',
        'resolved_at' => 'datetime',
    ];

    public const TYPE_BALANCE_MISMATCH = 'balance_mismatch';
    public const TYPE_MISSING_ENTRY = 'missing_entry';
    public const TYPE_DUPLICATE_ENTRY = 'duplicate_entry';
    public const TYPE_ORPHANED_LINE = 'orphaned_line';

    public const SEVERITY_CRITICAL = 'critical';
    public const SEVERITY_HIGH = 'high';
    public const SEVERITY_MEDIUM = 'medium';
    public const SEVERITY_LOW = 'low';

    public function report(): BelongsTo
    {
        return $this->belongsTo(ReconciliationReport::class, 'report_id');
    }

    public function account(): BelongsTo
    {
        return $this->belongsTo(LedgerAccount::class, 'account_id');
    }

    public function isCritical(): bool
    {
        return $this->severity === self::SEVERITY_CRITICAL;
    }

    public function markResolved(string $notes, ?string $resolvedBy = null): void
    {
        $this->update([
            'resolution_status' => 'resolved',
            'resolution_notes' => $notes,
            'resolved_by' => $resolvedBy,
            'resolved_at' => now(),
        ]);
    }

    public function escalateToCBS(string $caseReference): void
    {
        $this->update([
            'requires_cbs_notification' => true,
            'cbs_case_reference' => $caseReference,
            'resolution_status' => 'escalated',
        ]);
    }
}
