# Admin Panel — React 19

## نظرة عامة

لوحة تحكم إدارة المنصة بتقنية React 19 مع TypeScript، Feature-Sliced Design (FSD)، Tailwind CSS/MUI.

## هيكل المشروع

```
admin/
├── src/
│   ├── app/                          # تكوين التطبيق (FSD: app layer)
│   │   ├── routes/                   # React Router v7 — تعريف المسارات مع guards
│   │   │   ├── index.tsx             # تعريف المسارات
│   │   │   ├── guards/              # AuthGuard, RoleGuard, DeviceGuard
│   │   │   └── loaders/             # Data loaders للصفحات
│   │   ├── layouts/                  # تخطيطات الصفحات
│   │   │   ├── MainLayout.tsx       # التخطيط الأساسي (شريط جانبي + رأس)
│   │   │   ├── AuthLayout.tsx       # تخطيط صفحات الدخول
│   │   │   └── RTL.tsx             # دعم اتجاه النص RTL
│   │   └── providers/               # Provider composition
│   │       ├── AuthProvider.tsx      # حالة المصادقة (global)
│   │       ├── ThemeProvider.tsx     # RTL-aware theme
│   │       └── QueryProvider.tsx     # React Query provider
│   ├── features/                     # ميزات مجمعة (FSD: features layer)
│   │   ├── auth/                     # المصادقة (دخول، خروج، تجديد التوكن)
│   │   │   ├── api/                  # دوال API للمصادقة
│   │   │   ├── model/               # أنواع البيانات والحالة
│   │   │   └── ui/                  # شاشات الدخول
│   │   ├── users/                    # إدارة المستخدمين
│   │   │   ├── api/                 # User CRUD API
│   │   │   ├── model/               # User types, roles
│   │   │   └── ui/                  # UserList, UserDetail, UserForm
│   │   ├── kyc/                      # مراجعة KYC
│   │   │   ├── api/
│   │   │   ├── model/               # KYC levels, documents
│   │   │   └── ui/                  # KYCReview, KYCQueue, DocumentViewer
│   │   ├── transactions/             # البحث في المعاملات
│   │   ├── fraud/                    # حالات الاحتيال
│   │   ├── fx/                       # إدارة أسعار الصرف
│   │   ├── agents/                   # إدارة الوكلاء
│   │   ├── reports/                  # التقارير والتحليلات
│   │   └── settings/                 # إعدادات النظام
│   ├── entities/                     # كيانات الأعمال (FSD: entities layer)
│   │   ├── user/                     # User type, interface, default values
│   │   │   ├── index.ts             # User interface + UserStatus enum
│   │   │   └── api.ts               # دوال API الأساسية للكيانات
│   │   ├── transaction/
│   │   ├── wallet/
│   │   └── agent/
│   ├── shared/                       # مكونات وخدمات مشتركة (FSD: shared layer)
│   │   ├── ui/                       # مكتبة المكونات
│   │   │   ├── Button/
│   │   │   ├── Modal/
│   │   │   ├── Table/
│   │   │   ├── Form/                # Form components with RTL support
│   │   │   ├── Card/
│   │   │   └── DataTable/           # جدول متقدم مع فرز وبحث
│   │   ├── api/                      # API client
│   │   │   ├── axios.ts             # Axios instance + interceptors
│   │   │   ├── interceptors/        # Auth, Error, RTL headers
│   │   │   └── types.ts             # ApiResponse generic type
│   │   ├── hooks/                    # React Hooks مخصصة
│   │   │   ├── useAuth.ts           # حالة المصادقة
│   │   │   ├── useDebounce.ts
│   │   │   ├── usePagination.ts
│   │   │   └── useRTL.ts            # كشف اتجاه النص الحالي
│   │   ├── utils/                    # دوال مساعدة
│   │   │   ├── money.ts             # تنسيق الأموال (فلس ← ل.س)
│   │   │   ├── validators.ts
│   │   │   └── formatters.ts        # تاريخ، أرقام، عملة
│   │   ├── constants/                # ثوابت النظام
│   │   ├── types/                    # TypeScript types العامة
│   │   │   ├── api.ts               # ApiResponse, PaginatedResponse
│   │   │   ├── money.ts             # MoneyType, Currency
│   │   │   └── user.ts              # Role, Permission
│   │   └── i18n/                     # الترجمات
│   │       ├── ar/                   # العربية
│   │       ├── en/                   # الإنجليزية
│   │       ├── ku/                   # الكردية
│   │       └── hy/                   # الأرمنية
│   ├── lib/                          # تكوين المكتبات الخارجية
│   │   ├── axios.ts                 # Axios instance with base config
│   │   ├── query.ts                 # React Query configuration
│   │   ├── store.ts                 # Zustand store (global state)
│   │   └── theme.ts                 # MUI/Chakra theme with RTL
│   ├── assets/                       # صور، أيقونات، خطوط
│   │   ├── fonts/
│   │   ├── icons/
│   │   └── images/
│   ├── main.tsx                      # نقطة الدخول
│   └── vite-env.d.ts
├── public/
│   ├── favicon.ico
│   └── manifest.json
├── index.html
├── vite.config.ts
├── tsconfig.json
├── tailwind.config.js
├── eslint.config.js
└── package.json
```

---

## إدارة حالة المصادقة

```tsx
// app/providers/AuthProvider.tsx
// المصادقة مركزية — كل الصفحات تصل لحالة المستخدم عبر useAuth()
interface AuthState {
  user: User | null;
  token: string | null;
  permissions: Permission[];
  login: (phone: string, pin: string) => Promise<void>;
  logout: () => void;
  refreshToken: () => Promise<void>;
}
```

## إدارة صلاحيات الصفحات

```tsx
// app/routes/guards/RoleGuard.tsx
// كل مسار يمكن حمايته بدور (RBAC) أو سمة (ABAC)
<Route element={<RoleGuard roles={['admin', 'compliance']} />}>
  <Route path="kyc" element={<KYCReview />} />
</Route>
```

## معالجة أخطاء API

```tsx
// shared/api/interceptors/error.interceptor.ts
// معالجة مركزية: 401 ← تجديد التوكن، 403 ← Redirect، 422 ← عرض الأخطاء
axiosInstance.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) await refreshToken();
    if (error.response?.status === 403) navigate('/forbidden');
    return Promise.reject(error);
  }
);
```

## دعم RTL

- `theme.ts`: MUI theme مع `direction: 'rtl'` عند اختيار العربية
- `tailwind.config.js`: إعدادات RTL للـ spacing والـ margin
- كل مكون UI يختبر بالاتجاهين (RTL/LTR)
- `useRTL()` hook: يُرجع `isRTL` و `direction` للاستخدام في المكونات

## العلاقة مع الأقسام الأخرى

- **التصميم:** [`design-system/`](design-system/) — الألوان، الخطوط، المكونات
- **الأمان:** [`../compliance/security-policies/`](../compliance/security-policies/) — JWT, RBAC/ABAC
- **الخلفي:** [`../backend/OVERVIEW.md`](../backend/OVERVIEW.md) — نقاط API
- **الاختبارات:** [`../architecture/testing/`](../architecture/testing/) — أنماط الاختبار
