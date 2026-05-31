# 15 - API تقارير الاحتيال (API Specification)

## المسارات

```php
Route::middleware(['auth:jwt', 'admin'])->prefix('admin/fraud')->group(function () {
    Route::get('/report', [FraudReportController::class, 'index']);
    Route::get('/stats', [FraudReportController::class, 'stats']);
    Route::post('/{id}/approve', [FraudReportController::class, 'approve']);
    Route::post('/{id}/reject', [FraudReportController::class, 'reject']);
});

Route::middleware('auth:jwt')->prefix('admin')->group(function () {
    Route::get('/blocked-ips', [FraudReportController::class, 'listBlockedIps']);
    Route::post('/blocked-ips', [FraudReportController::class, 'blockIp']);
    Route::post('/blocked-ips/{id}/unblock', [FraudReportController::class, 'unblockIp']);
});
```

## GET /api/v1/admin/fraud/report

```json
{
    "success": true,
    "data": [
        {
            "id": 1,
            "transaction_id": 42,
            "user": { "id": 1, "name": "أحمد", "phone": "963900000001" },
            "amount": 5000.00,
            "currency": "USD",
            "triggered_rules": [
                { "rule": "high_amount", "message": "مبلغ كبير" },
                { "rule": "new_device", "message": "جهاز جديد" }
            ],
            "risk_score": 60,
            "status": "pending",
            "created_at": "2026-05-27T14:32:00+03:00"
        }
    ],
    "meta": {
        "current_page": 1,
        "per_page": 20,
        "total": 5
    }
}
```

## GET /api/v1/admin/fraud/stats

```json
{
    "success": true,
    "data": {
        "pending": 3,
        "approved": 12,
        "rejected": 2,
        "total": 17,
        "high_risk": 1
    }
}
```

## POST /api/v1/admin/fraud/{id}/approve

```json
{
    "success": true,
    "message": "تمت الموافقة على المعاملة"
}
```

## POST /api/v1/admin/fraud/{id}/reject

```json
// الطلب
{ "reason": "نشاط غير معتاد — اشتباه بغسيل أموال" }

// الرد
{
    "success": true,
    "message": "تم رفض المعاملة"
}
```

## POST /api/v1/admin/blocked-ips

```json
// الطلب
{ "ip": "192.168.1.100", "reason": "محاولات اختراق متكررة" }

// الرد
{
    "success": true,
    "message": "تم حظر IP بنجاح"
}
```
