# Beza Platform — نظرة عامة

**منصة محفظة رقمية سورية — Fintech متكامل**
**آخر تحديث:** 2026-05-31 | **الفرع:** `feature/phase5-deploy-wap-admin`

---

## الرؤية

منصة مالية رقمية تقدم خدمات الدفع، التحويل، التجارة الإلكترونية، والشمول المالي لأكثر من 22 مليون مقيم و6 ملايين مغترب في سوريا.

## المكدس التقني

| الطبقة | التقنية |
|--------|---------|
| Backend | PHP ^8.3 / Laravel ^13.8 / MySQL 8.0 |
| Cache/Queue | Redis (predis ^3.4) |
| WAP Frontend | Next.js 16.2.6 / React 19.2.4 / TypeScript ^5 / Tailwind CSS ^4 |
| Admin Panel | Next.js 16.2.6 (منفصل، port 3000) |
| Mobile | Flutter 3.41+ / Dart 3.8+ |
| API | RESTful + OpenAPI 3.1 |
| Testing | PHPUnit ^12.5, Playwright ^1.52/^1.60, k6 |
| Monitoring | OpenTelemetry + Grafana |

## الجمهور المستهدف

| الفئة | الوصف |
|-------|-------|
| الأفراد | تحويل P2P، دفع فواتير، شحن رصيد |
| التجار | قبول مدفوعات، روابط دفع، QR |
| الوكلاء | إيداع/سحب نقدي، إدارة العملاء |
| الشركات | رواتب، تسوية مالية، فواتير |

## مسار المستخدم الأساسي (MVP)

تسجيل → إنشاء محفظة → شحن رصيد → تحويل → إشعار

## القيم الأساسية

- **الأمان أولاً** — WORM Ledger، تشفير شامل، TOTP
- **السرعة** — معالجة فورية للمعاملات
- **الشفافية** — رسوم واضحة، سجل تدقيق كامل
- **الشمول** — دعم المناطق الريفية عبر شبكة وكلاء

---

## هيكل التوثيق

```
01-guide/           ← أدلة عامة
02-architecture/    ← مبادئ معمارية، وحدات، ADRs
03-backend/         ← Laravel 13 Modular Monolith
04-frontend/        ← React 19 Admin + Flutter Mobile
05-api/             ← OpenAPI، Endpoint Matrix
06-compliance/      ← أمان، AML/KYC، حماية بيانات
07-infrastructure/  ← Docker، نشر، نسخ احتياطي
08-operations/      ← Releases، Runbooks، مراقبة
09-security/        ← تقارير اختراق، تدقيق أمني
90-archive/         ← أرشيف تاريخي
```

**المرجع:** `docs/README.md` ← الفهرس الكامل
