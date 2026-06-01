# قائمة التدقيق للإنتاج — WAP

## الأداء
- [ ] `npm run build` — 0 errors, 0 warnings
- [ ] Lighthouse Performance ≥ 90
- [ ] حجم الحزمة < 120KB (gzip)
- [ ] FCP < 1.5s على 3G
- [ ] LCP < 2.5s
- [ ] لا render-blocking resources

## الأمان
- [ ] CSP مضبوط — no unsafe-inline
- [ ] HSTS مفعل
- [ ] HttpOnly; Secure; SameSite=Strict على Cookie
- [ ] Rate Limiting على API (50 req/min)
- [ ] Idempotency paylaşılan كل تحويل
- [ ] لا secrets في frontend code
- [ ]全て المدخلات Sanitize

## PWA
- [ ] Manifest.json صحيح (icons, theme_color, display)
- [ ] Service Worker يعمل ويعالج fetch events
- [ ] Pre-cache للصفحات الأساسية
- [ ] Install prompt يعمل
- [ ] Offline page يعرض عند عدم الاتصال

## Offline Queue
- [ ] IndexedDB تخزين + استرجاع
- [ ] مزامنة تلقائية عند عودة الاتصال
- [ ] Idempotency key يمنع التكرار
- [ ] حد أقصى 5 محاولات ← تعليم كـ failed
- [ ] واجهة مستخدم لمراجعة Queue

## الاختبارات
- [ ] Playwright: 01-auth — جميع السيناريوهات
- [ ] Playwright: 02-balance — full + minimal
- [ ] Playwright: 03-transfer — نجاح + فشل + idempotency
- [ ] Playwright: 06-offline-queue — قطع + مزامنة
- [ ] k6: 50 مستخدم متزامن — < 200ms متوسط
- [ ] `php artisan test` — جميع الاختبارات تمر

## التوثيق
- [ ] WAP-SPEC.md محدّث
- [ ] README.md في frontend/wap/
- [ ] API endpoints موثقة
- [ ] جميع الـ edge cases موثقة

## النشر
- [ ] `NODE_ENV=production`
- [ ] `NEXT_PUBLIC_API_URL` يشير إلى API الحقيقي
- [ ] Font files مضغوطة (woff2)
- [ ] CDN للتوزيع (اختياري)
