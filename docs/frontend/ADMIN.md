# Admin Panel - React 19

## نظرة عامة

لوحة تحكم إدارة المنصة بتقنية React 19 مع TypeScript، Feature-Sliced Design، و Tailwind CSS/MUI.

## هيكل المشروع

```
admin/
├── src/
│   ├── app/                        # تكوين التطبيق
│   │   ├── routes/                 # React Router v7
│   │   ├── layouts/                # تخطيطات الصفحات
│   │   └── loaders/                # Data loaders
│   ├── features/                   # Feature-Sliced Design
│   │   ├── auth/                   # المصادقة
│   │   ├── users/                  # إدارة المستخدمين
│   │   ├── kyc/                    # مراجعة KYC
│   │   ├── transactions/           # البحث في المعاملات
│   │   ├── fraud/                  # حالات الاحتيال
│   │   ├── fx/                     # إدارة أسعار الصرف
│   │   ├── agents/                 # إدارة الوكلاء
│   │   ├── reports/                # التقارير والتحليلات
│   │   └── settings/               # إعدادات النظام
│   ├── entities/                   # كيانات الأعمال (نوع، واجهة)
│   ├── shared/                     # مكونات مشتركة
│   │   ├── ui/                     # Button, Modal, Table, Form
│   │   ├── api/                    # Axios + interceptors
│   │   ├── hooks/                  # Hooks مخصصة
│   │   ├── utils/                  # دوال مساعدة
│   │   ├── constants/              # ثوابت
│   │   ├── types/                  # TypeScript types
│   │   └── i18n/                   # ar, en, ku, hy
│   ├── lib/                        # تكوين مكتبات
│   │   ├── axios.ts
│   │   ├── query.ts                # React Query
│   │   ├── store.ts                # Zustand
│   │   └── theme.ts
│   ├── assets/
│   ├── main.tsx
│   └── vite-env.d.ts
├── public/
├── index.html
├── vite.config.ts
├── tsconfig.json
├── tailwind.config.js
└── package.json
```

## الميزات الرئيسية

- لوحة إحصائيات حية (Real-time Dashboard)
- إدارة المستخدمين (بحث، فلترة، تفاصيل، حظر)
- مراجعة KYC (صور الهوية، التحقق اليدوي)
- مراقبة المعاملات المالية
- إدارة أسعار الصرف
- إدارة شبكة الوكلاء
- التقارير والتحليلات
- سجل التدقيق (Audit Log)
- إعدادات النظام
