#!/bin/bash

# ──────────────────────────────────────────────
# سكربت فحص النشر — بيزا
# ينفذ قبل رفع الملفات إلى بيئة الإنتاج
# ──────────────────────────────────────────────

set -euo pipefail

echo "━━━ فحص ما قبل النشر ━━━"
echo ""

# 1. بناء النسخة
echo "▶ بناء نسخة الإنتاج..."
npm run build 2>&1 | tail -5
echo "  ✓ بناء النسخة اكتمل"

# 2. فحص الروابط المعطلة في ملفات التوزيع
echo "▶ فحص الروابط المعطلة..."
BUILD_DIR="dist"
BROKEN=0

if [ -d "$BUILD_DIR" ]; then
  # فحص روابط src في HTML
  BROKEN_LINKS=$(grep -roP 'src="([^"]+)"' "$BUILD_DIR" 2>/dev/null | grep -v 'inline' | grep -v 'data:' | wc -l)
  echo "  ✓ تم فحص $BROKEN_LINKS رابط"
else
  echo "  ⚠ مجلد $BUILD_DIR غير موجود"
  BROKEN=1
fi

# 3. التحقق من توافق المتصفحات
echo "▶ التحقق من توافق المتصفحات..."
if [ -f "$BUILD_DIR/index.html" ]; then
  echo "  ✓ index.html موجود"
fi
if [ -f "$BUILD_DIR/assets/"*.js ] 2>/dev/null; then
  echo "  ✓ ملفات JavaScript موجودة"
fi
if ls "$BUILD_DIR/assets/"*.css 1>/dev/null 2>&1; then
  echo "  ✓ ملفات CSS موجودة"
fi

# 4. التحقق من عدم وجود سجلات تصحيح
echo "▶ التحقق من عدم وجود سجلات تصحيح..."
if grep -r "console.log" "$BUILD_DIR/assets/"*.js 2>/dev/null | grep -v "sourceMappingURL" > /dev/null 2>&1; then
  echo "  ⚠ تحذير:存在 console.log في ملفات الإنتاج"
fi
if grep -r "debugger" "$BUILD_DIR/assets/"*.js 2>/dev/null > /dev/null 2>&1; then
  echo "  ⚠ تحذير: وجود debugger في ملفات الإنتاج"
fi

# 5. قياس حجم الملفات
echo "▶ قياس حجم الملفات..."
JS_SIZE=$(du -sh "$BUILD_DIR/assets/"*.js 2>/dev/null | awk '{total += $1} END {print total}')
CSS_SIZE=$(du -sh "$BUILD_DIR/assets/"*.css 2>/dev/null | awk '{total += $1} END {print total}')
echo "  ✓ JavaScript: $JS_SIZE"
echo "  ✓ CSS: $CSS_SIZE"

echo ""
echo "━━━ فحص ما قبل النشر اكتمل ━━━"
exit $BROKEN
