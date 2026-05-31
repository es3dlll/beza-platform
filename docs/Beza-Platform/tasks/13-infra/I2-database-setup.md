# I2 - إعداد قاعدة البيانات

## الوصف
إنشاء وهيكلة قاعدة بيانات MySQL.

## إنشاء قاعدة البيانات
```sql
CREATE DATABASE beza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## تشغيل الترحيلات
```bash
php artisan migrate
```

## الجداول الأساسية (16 جدولاً)
| الجدول | الوصف |
|--------|-------|
| users | المستخدمون |
| wallets | المحافظ (SYP + USD لكل مستخدم) |
| transactions | جميع المعاملات المالية |
| merchants | حسابات التجار |
| merchant_products | منتجات التاجر |
| merchant_orders | طلبات المتجر |
| agents | الوكلاء (مكاتب الصرافة) |
| agent_transactions | معاملات الوكلاء |
| deals | الصفقات الاستثمارية |
| deal_investments | استثمارات المستخدمين في الصفقات |
| virtual_cards | البطاقات الافتراضية/الفيزيائية |
| card_transactions | معاملات البطاقات |
| kyc_documents | وثائق التحقق من الهوية |
| audit_logs | سجل التدقيق |
| notifications | الإشعارات |
| settings | إعدادات النظام |

## Seeders
```bash
php artisan db:seed --class=RolesAndPermissionsSeeder
php artisan db:seed --class=InvestmentPoolsSeeder
php artisan db:seed --class=SettingsSeeder
```

## Redis
- الجلسات: `SESSION_DRIVER=redis`
- الكاش: `CACHE_DRIVER=redis`
- قوائم الانتظار: `QUEUE_CONNECTION=redis`

## اختبارات
- تشغيل migrations ← لا أخطاء
- تشغيل seeders ← بيانات البداية
- التحقق من وجود جميع الجداول
