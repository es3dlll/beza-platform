# 17 - تصدير التقارير (Export & Reporting)

## تصدير CSV

```php
<?php

namespace App\Exports;

use App\Models\AuditLog;
use Illuminate\Support\Collection;
use Maatwebsite\Excel\Concerns\FromCollection;
use Maatwebsite\Excel\Concerns\WithHeadings;

class AuditLogExport implements FromCollection, WithHeadings
{
    public function __construct(
        private ?string $eventType = null,
        private ?string $from = null,
        private ?string $to = null,
    ) {}

    public function collection(): Collection
    {
        $query = AuditLog::with('user')->orderBy('created_at', 'desc');

        if ($this->eventType) $query->where('event_type', $this->eventType);
        if ($this->from) $query->whereDate('created_at', '>=', $this->from);
        if ($this->to) $query->whereDate('created_at', '<=', $this->to);

        return $query->get()->map(fn($log) => [
            'created_at' => $log->created_at,
            'event_type' => $log->event_type,
            'user' => $log->user?->name ?? '-',
            'user_phone' => $log->user?->phone ?? '-',
            'data' => json_encode($log->data, JSON_UNESCAPED_UNICODE),
            'ip' => $log->ip,
        ]);
    }

    public function headings(): array
    {
        return ['التاريخ', 'الحدث', 'المستخدم', 'رقم الهاتف', 'التفاصيل', 'IP'];
    }
}
```

## تحميل CSV في Controller

```php
public function export(Request $request): \Symfony\Component\HttpFoundation\BinaryFileResponse
{
    return (new \Maatwebsite\Excel\Excel())->download(
        new AuditLogExport($request->event_type, $request->from, $request->to),
        'audit_logs_' . now()->format('Ymd') . '.csv',
        \Maatwebsite\Excel\Excel::CSV,
    );
}
```

## تقارير للجهات الرقابية

```php
public function complianceReport(string $from, string $to): JsonResponse
{
    $data = [
        'total_logins' => AuditLog::where('event_type', 'login')
            ->whereBetween('created_at', [$from, $to])->count(),
        'total_transactions' => AuditLog::where('event_type', 'transfer_created')
            ->whereBetween('created_at', [$from, $to])->count(),
        'total_kyc_approved' => AuditLog::where('event_type', 'kyc_verified')
            ->whereBetween('created_at', [$from, $to])->count(),
        'admin_actions' => AuditLog::where('event_type', 'admin_action')
            ->whereBetween('created_at', [$from, $to])->count(),
    ];

    return response()->json(['success' => true, 'data' => $data]);
}
```
