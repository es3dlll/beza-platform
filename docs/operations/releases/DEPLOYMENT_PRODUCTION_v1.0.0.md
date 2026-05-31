# دليل النشر للإنتاج - منصة بيزا الإصدار 1.0.0

## المتطلبات المسبقة
- خادم يعمل بـ PHP 8.4 مع امتدادات: mbstring, pdo, openssl, intl, bcmath
- قاعدة بيانات MySQL 8.0+ أو MariaDB 10.6+
- Redis 6+ للذاكرة المؤقتة وقوائم الانتظار
- بيئة Node.js 20+ و npm 9+ لبناء الواجهات الأمامية
- وصول SSH مع صلاحيات sudo للعمليات النظامية

## خطوات النشر

1. جلب أحدث نسخة من الفرع الرئيسي
   git fetch origin && git checkout main && git pull origin main

2. تثبيت تبعيات PHP بدون حزم التطوير
   composer install --no-dev --optimize-autoloader --no-interaction

3. نسخ ملف البيئة وتوليد المفاتيح
   cp .env.production .env
   php artisan key:generate --force
   php artisan config:cache && php artisan route:cache && php artisan view:cache

4. تطبيق هياكل قاعدة البيانات
   php artisan migrate --force --no-interaction

5. بناء الواجهات الأمامية
   cd frontend/admin && npm ci && npm run build && cd ../..
   cd frontend/mobile && flutter build apk --release && cd ../..

6. تشغيل المهاجرين للبذور الأولية
   php artisan db:seed --class=InitialDataSeeder --force

7. تفعيل جدولة المهام
   * * * * * cd /var/www/beza && php artisan schedule:run >> /dev/null 2>&1
   أضف هذا السطر إلى crontab -e للمستخدم المناسب

8. تشغيل عمال قوائم الانتظار كخدمة نظام
   أنشئ ملف /etc/systemd/system/beza-queue.service بالمحتوى المناسب وشغّله بـ:
   sudo systemctl enable beza-queue && sudo systemctl start beza-queue

9. التحقق من الصحة النهائية
   php artisan test --testsuite=Feature --use-baseline=deprecation-baseline.xml
   curl -H "Authorization: Bearer <token>" https://api.beza.sy/v1/ledger/health

10. تفعيل المراقبة
    تأكد أن نقطة /v1/ledger/metrics قابلة للوصول من خادم Prometheus
    راقب سجلات /storage/logs/audit.log للتدقيق المالي

## التراجع الآمن
في حال اكتشاف مشكلة حرجة:
1. أعد تشغيل التطبيق على الإصدار السابق عبر:
   git checkout <previous-commit-hash>
2. أعد بناء الواجهات الأمامية
3. نفذ: php artisan config:clear && php artisan cache:clear
4. راقب السجلات للتأكد من استقرار النظام

## جهات الاتصال للطوارئ
- الفريق التقني: +963-XXX-XXXX
- مسؤول الامتثال: compliance@beza.sy
- الدعم الفني للمصرف المركزي: support@cbs.gov.sy
