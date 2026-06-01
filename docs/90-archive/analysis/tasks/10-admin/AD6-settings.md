# AD6 - إعدادات النظام (Admin)

## الوصف
إدارة إعدادات المنصة: الرسوم، الحدود، أسعار الصرف.

## الإعدادات العامة
`GET /api/v1/admin/settings`
`PUT /api/v1/admin/settings`

## إعدادات الرسوم
`GET /api/v1/admin/settings/fees`
`PUT /api/v1/admin/settings/fees`

### الحقول
| المفتاح | القيمة الافتراضية |
|---------|-------------------|
| transfer | 0 |
| exchange | 0.5 |
| card_load | 1.5 |
| merchant_settlement_percent | 2.5 |
| merchant_settlement_fixed | 0.30 |
| agent_cash_out | 1.0 |
| withdraw_to_bank | 1.0 |
| deposit_by_card | 2.5 |

## إعدادات الحدود
`PUT /api/v1/admin/settings/limits`

| المفتاح | القيمة الافتراضية |
|---------|-------------------|
| daily_transfer_usd | 2000 |
| daily_transfer_syp | 2000000 |
| min_deposit_usd | 10 |
| min_deposit_syp | 10000 |

## إعدادات أسعار الصرف
`PUT /api/v1/admin/settings/exchange-rate`

### الحقول
| الحقل | الوصف |
|-------|-------|
| rate | سعر الصرف SYP/USD |
| margin | هامش الربح % (افتراضي 0.5%) |

## واجهات المستخدم
- React Admin: GeneralSettings, FeeSettings, RateSettings

## التخزين
- config/beza.php (في git)
- جدول settings في قاعدة البيانات (للإعدادات الديناميكية)

## اختبارات
- عرض الإعدادات ← 200
- تحديث رسوم التحويل ← 200
- تعيين حد يومي جديد ← 200
- محاولة تعديل بدون صلاحية ← 403
