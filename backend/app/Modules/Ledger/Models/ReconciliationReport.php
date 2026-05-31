<?php

declare(strict_types=1);

namespace App\Modules\Ledger\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

final class ReconciliationReport extends Model
{
    use SoftDeletes;

    protected $table = 'reconciliation_reports';
    protected $keyType = 'string';
    public $incrementing = false;

    protected $fillable = [
        'id', 'report_type', 'scope', 'account_id', 'start_date', 'end_date', 'status',
        'is_balanced', 'total_accounts_checked', 'total_discrepancies_found',
        'discrepancy_amount', 'cbs_report_code', 'reporting_date', 'currency',
        'summary', 'execution_time_ms', 'initiated_by', 'started_at', 'completed_at',
    ];

    protected $casts = [
        'is_balanced' => 'boolean',
        'total_accounts_checked' => 'integer',
        'total_discrepancies_found' => 'integer',
        'execution_time_ms' => 'integer',
        'summary' => 'array',
        'reporting_date' => 'date',
        'start_date' => 'date',
        'end_date' => 'date',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
    ];

    public const TYPE_DAILY_TRIAL_BALANCE = 'cbs_trial_balance';
    public const TYPE_SETTLEMENT_REPORT = 'cbs_settlement';
    public const TYPE_BALANCE_SHEET = 'cbs_balance_sheet';
    public const TYPE_INCOME_STATEMENT = 'cbs_income_statement';
    public const TYPE_RECONCILIATION = 'reconciliation';

    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_PENDING = 'pending';
    public const STATUS_RUNNING = 'running';

    public function discrepancies(): HasMany
    {
        return $this->hasMany(ReconciliationDiscrepancy::class, 'report_id');
    }

    public function scopeCbsReports($query)
    {
        return $query->whereIn('report_type', [
            self::TYPE_DAILY_TRIAL_BALANCE,
            self::TYPE_SETTLEMENT_REPORT,
            self::TYPE_BALANCE_SHEET,
            self::TYPE_INCOME_STATEMENT,
        ]);
    }

    public function toCBSFormat(): array
    {
        return [
            'report_code' => $this->cbs_report_code,
            'reporting_date' => $this->reporting_date?->format('Y-m-d'),
            'currency' => $this->currency,
            'status' => $this->is_balanced ? 'BALANCED' : 'DISCREPANCY_DETECTED',
            'summary' => $this->summary ?? [],
            'discrepancy_count' => $this->total_discrepancies_found,
            'generated_at' => $this->completed_at?->toISOString(),
        ];
    }
}
