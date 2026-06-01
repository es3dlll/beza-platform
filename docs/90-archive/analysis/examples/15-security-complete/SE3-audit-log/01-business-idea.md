# 01 - أهمية سجل التدقيق (Audit Log) — فكرة العمل

## ما هو سجل التدقيق؟

سجل التدقيق (Audit Log) هو **سجل زمني غير قابل للتعديل** يسجل كل حدث مهم في النظام: **من فعل ماذا ومتى**. يجب أن يكون:

- **Immutable**: لا يمكن تعديل أو حذف السجلات بعد كتابتها
- **Chronological**: مرتّبة زمنياً لتسهيل التتبع
- **Verifiable**: يمكن التحقق من سلامتها (مثلاً باستخدام التوقيع الرقمي)

## المتطلبات القانونية

في سوريا، تخضع المعاملات المالية الإلكترونية لقانون مكافحة غسل الأموال (AML) رقم 31 لعام 2010، الذي ينص على:

| المتطلب | التفاصيل |
|---------|----------|
| فترة الاحتفاظ | **7 سنوات** من تاريخ آخر معاملة للعميل |
| غرامات عدم الامتثال | قد تصل إلى 5 ملايين ليرة سورية |
| الجهة الرقابية | مصرف سوريا المركزي — هيئة مكافحة غسل الأموال |
| التقارير | تقديم تقارير دورية عن المعاملات المشبوهة (STR) |

## أنواع الأحداث المسجلة

| الفئة | الأحداث | مستوى الأهمية |
|-------|---------|--------------|
| المصادقة | login, logout, login_failed, 2fa_enabled, 2fa_disabled | عالي |
| الأمان | pin_changed, password_changed, pin_failed, device_added | عالي |
| المعاملات | transfer_created, transfer_completed, transfer_failed, deposit, withdraw | عالي جداً |
| المحفظة | wallet_updated, wallet_frozen, wallet_unfrozen, balance_reset | عالي |
| KYC | kyc_submitted, kyc_verified, kyc_rejected, kyc_document_uploaded | متوسط |
| المشرف | admin_action, user_blocked, user_suspended, user_verified, role_changed | عالي جداً |
| الإعدادات | settings_changed, fee_updated, limit_changed, feature_toggled | متوسط |
| البطاقات | card_created, card_blocked, card_pin_changed, card_limit_updated | عالي |

## بنية جدول سجل التدقيق

```sql
CREATE TABLE audit_logs (
    id          BIGINT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id     BIGINT UNSIGNED NULL,
    event_type  VARCHAR(50)     NOT NULL,    -- login, transfer_created, etc.
    description VARCHAR(500)    NOT NULL,    -- وصف الحدث بالعربية أو الإنجليزية
    ip_address  VARCHAR(45)     NULL,        -- IPv4 أو IPv6
    user_agent  TEXT            NULL,        -- معلومات المتصفح أو الجهاز
    metadata    JSON            NULL,        -- بيانات إضافية (request_id, amount, etc.)
    created_at  TIMESTAMP       NOT NULL DEFAULT CURRENT_TIMESTAMP,

    INDEX idx_event_type (event_type),
    INDEX idx_user_id (user_id),
    INDEX idx_created_at (created_at)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4;
```

## سيناريو: تحقيق مشرف في معاملة مشبوهة

مستخدم يُدعى "أحمد" يشتبه بتحويل غير مصرح به. المشرف يتتبع كالتالي:

```php
// 1. البحث في سجل التدقيق عن تحويل معين
$logs = AuditLog::where('event_type', 'transfer_created')
    ->whereBetween('created_at', ['2026-05-20 00:00:00', '2026-05-27 23:59:59'])
    ->where('metadata->amount', '>', 5000000)   // مبالغ كبيرة
    ->orderBy('created_at', 'desc')
    ->get();

// 2. لكل نتيجة، نتحقق من جهاز المستخدم
foreach ($logs as $log) {
    $metadata = json_decode($log->metadata, true);
    echo "المعاملة #{$metadata['transaction_id']} - {$metadata['amount']} ل.س\n";
    echo "الجهاز: {$log->user_agent}\n";
    echo "IP: {$log->ip_address}\n";

    // 3. هل الجهاز معروف للمستخدم؟
    $knownDevices = AuditLog::where('user_id', $log->user_id)
        ->where('event_type', 'device_added')
        ->where('metadata->device_id', $metadata['device_id'])
        ->exists();

    if (! $knownDevices) {
        Log::warning("جهاز غير معروف للمستخدم {$log->user_id}");
    }
}
```

## معايير القبول (Acceptance Criteria) لاكتمال التدقيق

1. **لا يمكن تجاوز أي حدث مالي**: كل معاملة (تحويل، إيداع، سحب) تسجل تلقائياً
2. **الحذف ممنوع**: لا يوجد واجهة لحذف audit logs، حتى للمشرفين
3. **التعديل ممنوع**: السجلات `read-only` بعد كتابتها
4. **الترتيب الزمني مضمون**: الطابع الزمني مرتب بالساعة الذرية (NTP sync)
5. **ربط السجلات**: كل حدث يمكن تتبعه إلى طلب HTTP محدد (`request_id`)
6. **فترة الاحتفاظ 7 سنوات**: أرشفة تلقائية بعدها

## الامتثال مع قوانين AML

```php
// إنشاء تقرير عن المعاملات المشبوهة (STR - Suspicious Transaction Report)
public function generateSTR(): array
{
    $suspicious = AuditLog::where('event_type', 'transfer_created')
        ->whereRaw("JSON_EXTRACT(metadata, '$.amount') > 10000000")     // > 10 مليون
        ->orWhere(function ($q) {
            $q->where('event_type', 'login')
              ->where('ip_address', 'NOT IN', function ($sub) {
                  $sub->select('ip_address')->from('user_known_ips');
              });
        })
        ->get();

    return [
        'report_date' => now()->toDateString(),
        'total_suspicious' => $suspicious->count(),
        'details' => $suspicious->map(fn($log) => [
            'event_id'   => $log->id,
            'user_id'    => $log->user_id,
            'action'     => $log->event_type,
            'amount'     => json_decode($log->metadata, true)['amount'] ?? 0,
            'timestamp'  => $log->created_at,
        ]),
    ];
}
```

## ملخص منافع سجل التدقيق

| الفائدة | الشرح |
|---------|-------|
| **الشفافية** | كل إجراء في النظام مسجل ويمكن مراجعته |
| **المساءلة** | المشرفون يعلمون أن أفعالهم مراقبة |
| **التحقيق الجنائي** | إثبات قانوني في حال النزاعات أو الاحتيال |
| **الامتثال القانوني** | تلبية متطلبات AML وهيئة مكافحة غسل الأموال |
| **تحليل الأداء** | تتبع الأخطاء وفهم سلوك المستخدمين |
