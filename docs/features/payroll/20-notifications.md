# 20 — Notifications

---

## Channels

| Channel | Priority | Delivery Guarantee | Notes |
|---------|----------|-------------------|-------|
| Push (FCM) | Primary | Best-effort | Requires Beza app installed |
| SMS | Secondary | High (via Syriatel/MTN) | Arabic text, sender: "Beza" |
| In-app inbox | Fallback | Guaranteed | Stored in DB, shown on app open |

## Notification Templates (Arabic)

### Batch Completed — Company HR

```
📧 الدفع: تمت معالجة دفعة الرواتب بنجاح

الشركة: شركة الشام للصناعات الحديدية
رقم الدفعة: B-2026-05-001
عدد الموظفين: 150
المبلغ الإجمالي: 120,000,000 ل.س
تاريخ المعالجة: 29 مايو 2026

الحالة: ✅ تم بنجاح (150/150)
الرسوم: 600,000 ل.س

يمكنك تنزيل كشوف الرواتب من لوحة التحكم.
```

### Partial Failure — Company HR

```
⚠️ دفعة الرواتب تحتاج إلى مراجعة

الدفعة: B-2026-05-001
تم بنجاح: 148 من 150
فشل: 2 موظف

الموظفون الفاشلون:
- أحمد علي (رقم الموظف: EMP-012) — السبب: المحفظة غير مفعلة
- خالد عمر (رقم الموظف: EMP-045) — السبب: رصيد غير كافٍ

يرجى مراجعة لوحة التحكم لإعادة المحاولة.
```

### Salary Credited — Employee

```
📱 تم إيداع راتبك

الموظف: أحمد علي
الشركة: شركة الشام للصناعات الحديدية
المبلغ: 1,200,000 ل.س
التاريخ: 29 مايو 2026
رقم الدفعة: B-2026-05-001

عرض كشف الراتب: [رابط]
```

### Reminder: Settlement Due — Company Finance

```
⏰ تذكير: تسوية الدفعة مستحقة

الشركة: شركة الشام للصناعات الحديدية
الدفعة: B-2026-05-001
المبلغ المستحق: 95,000,000 ل.س
تاريخ الاستحقاق: 30 مايو 2026

يرجى تسوية المبلغ قبل الساعة 5:00 مساءً لتجنب تعليق الخدمة.
```

## Delivery Rules

| Notification | Push | SMS | In-App | Recipient |
|-------------|------|-----|--------|-----------|
| Batch completed | ✅ | ✅ (summary) | ✅ | HR + Finance contacts |
| Partial failure | ✅ | ✅ (urgent) | ✅ | HR |
| Employee paid | ✅ | ✅ | ✅ | Employee |
| Retry exhausted (3x) | ✅ | ✅ | ✅ | HR |
| Settlement due (T+1) | ✅ | ✅ | ✅ | Finance |
| Settlement overdue | ✅ | ✅ | ✅ | Finance + Admin |
| Wallet activation needed | ✅ | ✅ (link) | ❌ | Employee |

## SMS Budget (Monthly Estimate)

| Volume | Cost (SYP) | Notes |
|--------|-----------|-------|
| 25,000 SMS | ~SYP 500,000 | For 50 companies averaging 500 employees paid monthly |
