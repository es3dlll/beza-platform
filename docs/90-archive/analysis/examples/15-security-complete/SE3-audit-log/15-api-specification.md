# 15 - API سجل التدقيق (API Specification)

## المسارات

```php
Route::middleware(['auth:jwt', 'admin'])->prefix('admin/audit-logs')->group(function () {
    Route::get('/', [AuditLogController::class, 'index']);
    Route::get('/{id}', [AuditLogController::class, 'show']);
    Route::get('/events/types', [AuditLogController::class, 'eventTypes']);
    Route::get('/stats/summary', [AuditLogController::class, 'stats']);
});
```

## GET /api/v1/admin/audit-logs

```json
// معاملات التصفية
// ?event_type=transfer_created
// &user_id=1
// &from=2026-01-01
// &to=2026-05-27
// &per_page=50

{
    "success": true,
    "data": [
        {
            "id": 1,
            "event_type": "transfer_created",
            "loggable_type": "App\\Models\\Transaction",
            "loggable_id": 42,
            "user": { "id": 1, "name": "أحمد" },
            "data": {
                "amount": 100.00,
                "currency": "USD",
                "reference_number": "BZ260527143200A1B2C3"
            },
            "ip": "192.168.1.100",
            "user_agent": "Dart/3.0 (dart:io)",
            "created_at": "2026-05-27T14:32:00+03:00"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 50,
        "total": 15234
    }
}
```

## GET /api/v1/admin/audit-logs/stats/summary

```json
{
    "success": true,
    "data": {
        "today": 234,
        "this_week": 1567,
        "total": 15234,
        "top_events": [
            { "event_type": "login", "count": 5000 },
            { "event_type": "transfer_created", "count": 3000 },
            { "event_type": "api_request", "count": 2000 }
        ]
    }
}
```
