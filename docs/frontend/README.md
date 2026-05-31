# Frontend — الأمامي (React 19 + Flutter 3.29)

> **الهدف:** توثيق واجهات المستخدم لمنصة بيزا — لوحة الإدارة وتطبيق المحفظة  
> **الجمهور المستهدف:** مطورو Frontend، مصممو UI/UX، قادة الفرق  
> **العلاقة:** هذا القسم يطبق [نظام التصميم](design-system/01-brand.md) و[المعايير الأمنية](../compliance/security-policies/README.md)

---

## الملفات

| الملف | الوصف |
|-------|-------|
| [`ADMIN.md`](ADMIN.md) | لوحة تحكم الإدارة React 19 — Feature-Sliced Design |
| [`MOBILE.md`](MOBILE.md) | تطبيق المحفظة Flutter 3.29 — Clean Architecture |
| [`design-system/`](design-system/) | نظام التصميم الموحد (الألوان، المكونات، الحركة) |

---

## مكونات Frontend

### 1. لوحة الإدارة (Admin Panel) — React 19

| خاصية | القيمة |
|-------|--------|
| **الإطار** | React 19 + TypeScript |
| **التوجيه** | React Router v7 مع Guards (Auth, Role, Device) |
| **إدارة الحالة** | Zustand (global) + React Query (server state) |
| **البيانات** | Axios + interceptors (Auth, Error, RTL) |
| **التصميم** | Tailwind CSS + MUI مع دعم RTL الكامل |
| **الهيكل** | Feature-Sliced Design (app/features/entities/shared/lib) |
| **الجمهور** | فريق الإدارة، مسؤولي الامتثال، المشغلين |

### 2. تطبيق المحفظة (Mobile App) — Flutter 3.29

| خاصية | القيمة |
|-------|--------|
| **الإطار** | Flutter 3.29 + Dart 3.x |
| **الهندسة** | Clean Architecture (data/domain/presentation) لكل feature |
| **إدارة الحالة** | Riverpod (موصى به) أو Bloc |
| **التخزين المحلي** | Hive (بيانات) + Secure Storage (توكنات) |
| **الاتصال** | Dio + WebSocket + Interceptors (Auth, Retry) |
| **التوجيه** | GoRouter مع Guards |
| **حقن التبعية** | GetIt |
| **الجمهور** | المستخدمون النهائيون (22 مليون مقيم + 6 ملايين مغترب) |

---

## وضع Offline-First (خاص بالتطبيق)

بسبب ظروف الكهرباء والإنترنت في سوريا، تطبيق Flutter يجب أن:
- يخزن البيانات الأساسية محلياً (Hive/SQLite)
- يعمل في وضع غير متصل للعمليات غير الحرجة
- يستأنف المعاملات المقطوعة تلقائياً
- يستخدم آلية إعادة محاولة ذكية (exponential backoff)

---

## العلاقة مع الأقسام الأخرى

- **العمارة** (`../architecture/PRINCIPLES.md`): الهيكل المعماري العام للمشروع
- **الأمان** (`../compliance/security-policies/`): Device Binding، JWT، المصادقة البيومترية
- **الخلفي** (`../backend/`): نقاط API والاستجابات الموحدة
- **الإشعارات** (`../operations/notifications/`): Push Notification للموبايل
- **المراقبة** (`../operations/observability/`): تتبع الأداء من جهة العميل
- **الاختبارات** (`../architecture/testing/`): أنماط اختبار الواجهات
