# 08 - دليل أوامر artisan, npm, flutter (Commands Reference)

## Laravel Artisan

| الأمر | الوصف |
|-------|--------|
| `php artisan serve --host=localhost --port=8000` | تشغيل خادم التطوير |
| `php artisan queue:work` | تشغيل معالج قائمة الانتظار |
| `php artisan migrate` | تشغيل الترحيلات |
| `php artisan migrate:fresh --seed` | إعادة إنشاء الجداول مع البيانات |
| `php artisan db:seed` | تشغيل البذور |
| `php artisan make:model ModelName -m` | إنشاء موديل مع ميغريشن |
| `php artisan make:controller Api/ControllerName` | إنشاء متحكم |
| `php artisan make:service ServiceName` | إنشاء خدمة |
| `php artisan make:event EventName` | إنشاء حدث |
| `php artisan make:listener ListenerName` | إنشاء مستمع |
| `php artisan make:exception ExceptionName` | إنشاء استثناء |
| `php artisan make:test TestName` | إنشاء اختبار |
| `php artisan key:generate` | توليد مفتاح التطبيق |
| `php artisan storage:link` | ربط التخزين |
| `php artisan route:list` | عرض جميع المسارات |
| `php artisan cache:clear` | مسح الكاش |
| `php artisan config:clear` | مسح إعدادات الكاش |

## NPM

| الأمر | الوصف |
|-------|--------|
| `npm run dev` | تشغيل خادم التطوير (Vite) |
| `npm run build` | بناء للإنتاج |
| `npm run lint` | فحص الكود |
| `npm run format` | تنسيق الكود |

## Flutter

| الأمر | الوصف |
|-------|--------|
| `flutter pub get` | تحميل الحزم |
| `flutter run` | تشغيل التطبيق |
| `flutter build apk` | بناء APK |
| `flutter build ios` | بناء iOS |
| `flutter test` | تشغيل الاختبارات |
| `flutter analyze` | تحليل الكود |
| `flutter clean` | تنظيف المشروع |
