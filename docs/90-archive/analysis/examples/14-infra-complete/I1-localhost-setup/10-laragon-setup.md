# 10 - إعداد Laragon (Laragon Setup)

## ما هو Laragon؟

Laragon هو بيئة تطوير متكاملة لويندوز توفر Apache/MySQL/Redis/Mailpit بنقرة واحدة. سنستخدمه بدلاً من Docker للتطوير المحلي.

## تثبيت Laragon

1. حمل Laragon من [laragon.org/download](https://laragon.org/download/) (اختر النسخة الكاملة Full)
2. شغّل المثبت — المسار الافتراضي `C:\laragon\`
3. افتح Laragon — ستجد Apache + MySQL شغالين تلقائياً

## إعداد الخدمات

### MySQL
- Laragon يشغل MySQL تلقائياً على port 3306
- الدخول: `root` بدون كلمة سر (افتراضياً)
- استخدم **HeidiSQL** (مدمج مع Laragon) لإنشاء قاعدة بيانات `beza`

### Redis
- من Laragon → Menu → Redis → Start
- يعمل على port 6379

### Mailpit (اختبار الإيميلات)
- من Laragon → Menu → Mailpit → Start
- SMTP: port 1025, UI: http://localhost:8025

## إضافة Virtual Host

1. Laragon → Menu → Apache → Virtual Hosts
2. أضف:
   - **Domain:** `beza.test`
   - **Folder:** `C:\laragon\www\Beza-Platform`
3. Laragon يعيد تشغيل Apache تلقائياً
4. الآن `http://beza.test` يشير إلى مجلد المشروع

## إنشاء قاعدة البيانات

افتح HeidiSQL (Laragon → Tools → HeidiSQL) أو أي عميل MySQL:

```sql
CREATE DATABASE beza CHARACTER SET utf8mb4 COLLATE utf8mb4_unicode_ci;
```

## تشغيل المشاريع

### Laravel API (port 8000)

```bash
# داخل C:\laragon\www\Beza-Platform\Beza-api
php artisan serve --port=8000
```

### React Admin (port 5173)

```bash
# داخل C:\laragon\www\Beza-Platform\Beza-admin
npm run dev
```

### React SPA (port 5174)

```bash
# داخل C:\laragon\www\Beza-Platform\Beza-spa
npm run dev -- --port=5174
```

### Next.js Landing (port 3000)

```bash
# داخل C:\laragon\www\Beza-Platform\Beza-landing
npm run dev
```

## اختصارات مفيدة

| الأمر | الوظيفة |
|-------|---------|
| `Laragon → Start All` | تشغيل Apache + MySQL + Redis |
| `Laragon → Stop All` | إيقاف الكل |
| `php artisan serve --port=8000` | تشغيل Laravel API |
| `npm run dev` | تشغيل React/Vite |

## مقارنة: Laragon vs Docker

| الميزة | Laragon | Docker |
|--------|---------|-------|
| السرعة | فوري — لا حاجة لسحب images | بطيء في أول تشغيل |
| سهولة الاستخدام | واجهة ويندوز رسومية | CLI + ملفات YAML |
| الذاكرة | ~100MB | ~1-2GB |
| مناسب للإنتاج | لا — للـ dev فقط | نعم |
