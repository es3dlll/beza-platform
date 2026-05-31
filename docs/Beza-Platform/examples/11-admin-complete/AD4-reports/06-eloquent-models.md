# 06 - الموديلز (Eloquent Models)

## DailyReport Model

```php
<?php
// app/Models/Admin/DailyReport.php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class DailyReport extends Model
{
    protected $table = 'daily_reports';

    protected $fillable = [
        'date', 'total_transactions', 'total_volume', 'total_fees',
        'new_users', 'active_users', 'transaction_breakdown',
    ];

    protected $casts = [
        'date'                 => 'date',
        'total_volume'         => 'decimal:2',
        'total_fees'           => 'decimal:2',
        'transaction_breakdown'=> 'json',
    ];

    public function scopeBetween($query, $start, $end)
    {
        return $query->whereBetween('date', [$start, $end]);
    }

    public function scopeLatestFirst($query)
    {
        return $query->orderByDesc('date');
    }
}
```

## OperationalCost Model

```php
<?php
// app/Models/Admin/OperationalCost.php

namespace App\Models\Admin;

use Illuminate\Database\Eloquent\Model;

class OperationalCost extends Model
{
    protected $table = 'operational_costs';

    protected $fillable = [
        'date', 'category', 'description', 'amount', 'currency',
    ];

    protected $casts = [
        'date'   => 'date',
        'amount' => 'decimal:2',
    ];

    public function scopeByCategory($query, string $category)
    {
        return $query->where('category', $category);
    }

    public function scopeBetween($query, $start, $end)
    {
        return $query->whereBetween('date', [$start, $end]);
    }
}
```

## Report Data Transfer Objects

```php
<?php
// app/DTOs/Admin/DailyReportData.php

namespace App\DTOs\Admin;

class DailyReportData
{
    public function __construct(
        public readonly string $date,
        public readonly int    $totalTransactions,
        public readonly float  $totalVolume,
        public readonly float  $totalFees,
        public readonly int    $newUsers,
        public readonly int    $activeUsers,
        public readonly array  $transactionBreakdown,
        public readonly ?float $growthPercent = null,
    ) {}
}
```
