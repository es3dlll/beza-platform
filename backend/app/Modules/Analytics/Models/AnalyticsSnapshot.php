<?php

declare(strict_types=1);

namespace App\Modules\Analytics\Models;

use Illuminate\Database\Eloquent\Concerns\HasUlids;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

final class AnalyticsSnapshot extends Model
{
    use HasFactory, HasUlids;

    protected $table = 'analytics_snapshots';

    protected $fillable = [
        'snapshot_date',
        'metrics',
    ];

    protected $casts = [
        'snapshot_date' => 'date',
        'metrics' => 'array',
    ];

    public function scopeForDate($q, string $date) { return $q->whereDate('snapshot_date', $date); }
    public function scopeDateRange($q, string $from, string $to) { return $q->whereDate('snapshot_date', '>=', $from)->whereDate('snapshot_date', '<=', $to); }
}
