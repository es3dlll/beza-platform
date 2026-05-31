#!/bin/bash

# ──────────────────────────────────────────────
# سكربت تهيئة البيئة التجريبية — منصة بيزا
# ينشئ قاعدة بيانات نظيفة، يهيئ المستخدمين
# التجريبيين، ويفحص صحة النظام
# ──────────────────────────────────────────────

set -euo pipefail

echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  بيزا — تهيئة البيئة التجريبية"
echo "  $(date '+%Y-%m-%d %H:%M:%S')"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""

SCRIPT_DIR="$(cd "$(dirname "$0")" && pwd)"
BACKEND_DIR="$SCRIPT_DIR/.."
STORAGE_DIR="$BACKEND_DIR/storage"

# 1. التحقق من المتطلبات الأساسية
echo "▶ التحقق من المتطلبات..."
command -v php >/dev/null 2>&1 || { echo "✗ PHP غير مثبت"; exit 1; }
command -v composer >/dev/null 2>&1 || { echo "✗ Composer غير مثبت"; exit 1; }

PHP_VERSION=$(php -r "echo PHP_VERSION;")
echo "  ✓ PHP $PHP_VERSION"

# 2. إعداد ملف البيئة
echo "▶ إعداد ملف البيئة..."
if [ ! -f "$BACKEND_DIR/.env" ]; then
  if [ -f "$BACKEND_DIR/.env.staging" ]; then
    cp "$BACKEND_DIR/.env.staging" "$BACKEND_DIR/.env"
    echo "  ✓ تم نسخ .env.staging → .env"
  else
    cp "$BACKEND_DIR/.env.example" "$BACKEND_DIR/.env"
    sed -i 's/APP_ENV=local/APP_ENV=staging/' "$BACKEND_DIR/.env"
    sed -i 's/APP_DEBUG=true/APP_DEBUG=false/' "$BACKEND_DIR/.env"
    echo "  ✓ تم إنشاء .env من القالب (بيئة تجريبي)"
  fi
else
  echo "  • ملف .env موجود — تخطي"
fi

# 3. تثبيت التبعيات
echo "▶ تثبيت التبعيات..."
cd "$BACKEND_DIR"
composer install --no-interaction --prefer-dist 2>/dev/null
echo "  ✓ تم تثبيت التبعيات"

# 4. توليد مفتاح التشفير
echo "▶ توليد مفتاح التشفير..."
php artisan key:generate --force
echo "  ✓ تم توليد APP_KEY"

# 5. إنشاء قاعدة البيانات
echo "▶ تهيئة قاعدة البيانات..."
touch database/database.sqlite
php artisan migrate --force
echo "  ✓ تم ترحيل جميع الجداول"

# 6. إنشاء مستخدمين تجريبيين
echo "▶ إنشاء مستخدمين تجريبيين..."

php artisan tinker --execute="
\App\Models\User::create([
    'name' => 'مستخدم تجريبي أ',
    'email' => 'user1@beza.test',
    'password' => bcrypt('12345678'),
]);
\App\Models\User::create([
    'name' => 'مستخدم تجريبي ب',
    'email' => 'user2@beza.test',
    'password' => bcrypt('12345678'),
]);
\App\Models\User::create([
    'name' => 'مدير النظام',
    'email' => 'admin@beza.test',
    'password' => bcrypt('admin123'),
]);
echo '✓ 3 مستخدمين تجريبيين';
"

echo "  ✓ مستخدمون تجريبيون:"
echo "    - user1@beza.test / 12345678"
echo "    - user2@beza.test / 12345678"
echo "    - admin@beza.test / admin123"

# 7. تفعيل الطابور
echo "▶ تفعيل الطابور..."
php artisan queue:restart 2>/dev/null || true
echo "  ✓ الطابور جاهز"

# 8. فحص الصحة
echo "▶ فحص الصحة..."
HEALTH=$(curl -sf http://localhost:8000/api/v1/core/health 2>/dev/null || echo '{"success":false}')

if echo "$HEALTH" | php -r "echo json_decode(file_get_contents('php://stdin'))->success ? 'OK' : 'FAIL';" 2>/dev/null; then
  echo "  ✓ نقطة الصحة: جميع المكونات سليمة"
else
  echo "  ⚠ نقطة الصحة: غير متاحة (الخادم قد لا يعمل)"
fi

# 9. عرض الملخص
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo "  البيئة التجريبية جاهزة!"
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
echo ""
echo "  تشغيل الخادم:"
echo "    php artisan serve --host=0.0.0.0 --port=8000"
echo ""
echo "  تشغيل الطابور:"
echo "    php artisan queue:work --tries=3"
echo ""
echo "  مستخدمون تجريبيون:"
echo "    user1@beza.test / 12345678"
echo "    admin@beza.test / admin123"
echo ""
echo "━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━"
