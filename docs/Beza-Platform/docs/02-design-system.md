# 2. الهوية البصرية ونظام التصميم (Design System)

## 2.1 الألوان (Colors)

```css
:root {
  /* الألوان الأساسية */
  --beza-purple: #6366F1;      /* الثقة والتكنولوجيا */
  --beza-green: #10B981;       /* النمو والاستثمار */
  --beza-gold: #F59E0B;        /* التميز والجوائز */
  --beza-red: #EF4444;         /* التحذيرات والإجراءات الحساسة */
  --beza-blue: #3B82F6;        /* المعلومات والروابط */

  /* درجات الألوان */
  --beza-purple-dark: #4F46E5;
  --beza-purple-light: #818CF8;
  --beza-green-dark: #059669;
  --beza-green-light: #34D399;

  /* الرمادي المتدرج (محايد) */
  --gray-50: #F9FAFB;
  --gray-100: #F3F4F6;
  --gray-200: #E5E7EB;
  --gray-300: #D1D5DB;
  --gray-400: #9CA3AF;
  --gray-500: #6B7280;
  --gray-600: #4B5563;
  --gray-700: #374151;
  --gray-800: #1F2937;
  --gray-900: #111827;

  /* المسافات (نظام 8px) */
  --space-0: 0;
  --space-1: 0.25rem;   /* 4px */
  --space-2: 0.5rem;    /* 8px */
  --space-3: 0.75rem;   /* 12px */
  --space-4: 1rem;      /* 16px */
  --space-5: 1.25rem;   /* 20px */
  --space-6: 1.5rem;    /* 24px */
  --space-8: 2rem;      /* 32px */
  --space-10: 2.5rem;   /* 40px */
  --space-12: 3rem;     /* 48px */
  --space-16: 4rem;     /* 64px */
  --space-20: 5rem;     /* 80px */
  --space-24: 6rem;     /* 96px */
  --space-32: 8rem;     /* 128px */

  /* الخطوط */
  --font-arabic: 'Cairo', 'Tajawal', 'Almarai', sans-serif;
  --font-english: 'Inter', 'SF Pro Text', system-ui;

  /* أحجام الخطوط */
  --text-xs: 0.75rem;    /* 12px */
  --text-sm: 0.875rem;   /* 14px */
  --text-base: 1rem;     /* 16px */
  --text-lg: 1.125rem;   /* 18px */
  --text-xl: 1.25rem;    /* 20px */
  --text-2xl: 1.5rem;    /* 24px */
  --text-3xl: 1.875rem;  /* 30px */
  --text-4xl: 2.25rem;   /* 36px */
  --text-5xl: 3rem;      /* 48px */
  --text-6xl: 3.75rem;   /* 60px */

  /* الظلال */
  --shadow-xs: 0 1px 2px rgba(0,0,0,0.05);
  --shadow-sm: 0 1px 3px rgba(0,0,0,0.1);
  --shadow-md: 0 4px 6px -1px rgba(0,0,0,0.1);
  --shadow-lg: 0 10px 15px -3px rgba(0,0,0,0.1);
  --shadow-xl: 0 20px 25px -5px rgba(0,0,0,0.1);
  --shadow-2xl: 0 25px 50px -12px rgba(0,0,0,0.25);
  --shadow-brand: 0 4px 14px 0 rgba(99,102,241,0.4);

  /* الحواف المستديرة */
  --radius-sm: 0.25rem;
  --radius-md: 0.375rem;
  --radius-lg: 0.5rem;
  --radius-xl: 0.75rem;
  --radius-2xl: 1rem;
  --radius-3xl: 1.5rem;
  --radius-full: 9999px;

  /* فترات التحريك */
  --transition-fast: 150ms;
  --transition-base: 250ms;
  --transition-slow: 350ms;
  --ease: cubic-bezier(0.4, 0, 0.2, 1);
}
```

## 2.2 الخطوط والطباعة (Typography)

```css
body {
  font-family: var(--font-arabic);
  font-size: var(--text-base);
  line-height: 1.5;
  color: var(--gray-900);
  background-color: var(--gray-50);
  direction: rtl;
}

h1, .h1 {
  font-size: var(--text-5xl);
  font-weight: 800;
  background: linear-gradient(135deg, var(--beza-purple), var(--beza-green));
  background-clip: text;
  -webkit-background-clip: text;
  color: transparent;
  line-height: 1.2;
  margin-bottom: var(--space-4);
}

h2, .h2 {
  font-size: var(--text-4xl);
  font-weight: 700;
  line-height: 1.3;
  margin-bottom: var(--space-3);
}

h3, .h3 { font-size: var(--text-3xl); font-weight: 600; }
h4, .h4 { font-size: var(--text-2xl); font-weight: 600; }
h5, .h5 { font-size: var(--text-xl); font-weight: 500; }
h6, .h6 { font-size: var(--text-lg); font-weight: 500; }

.text-muted { color: var(--gray-500); }
.text-small { font-size: var(--text-sm); }
.text-large { font-size: var(--text-lg); }
```

## 2.3 الأزرار (Buttons)

```css
.btn {
  display: inline-flex;
  align-items: center;
  justify-content: center;
  gap: var(--space-2);
  padding: var(--space-3) var(--space-6);
  font-weight: 600;
  border-radius: var(--radius-lg);
  transition: all var(--transition-base) var(--ease);
  cursor: pointer;
  border: none;
  font-family: inherit;
}

.btn-primary {
  background: linear-gradient(135deg, var(--beza-purple), var(--beza-green));
  color: white;
  box-shadow: var(--shadow-md);
}

.btn-primary:hover {
  transform: translateY(-2px);
  box-shadow: var(--shadow-lg);
}

.btn-primary:active {
  transform: translateY(0);
}

.btn-secondary {
  background-color: var(--gray-100);
  color: var(--gray-800);
  border: 1px solid var(--gray-200);
}

.btn-secondary:hover {
  background-color: var(--gray-200);
}

.btn-outline {
  background: transparent;
  border: 2px solid var(--beza-purple);
  color: var(--beza-purple);
}

.btn-outline:hover {
  background: var(--beza-purple);
  color: white;
}

.btn-danger {
  background: linear-gradient(135deg, var(--beza-red), #DC2626);
  color: white;
}

.btn-sm {
  padding: var(--space-2) var(--space-4);
  font-size: var(--text-sm);
}

.btn-lg {
  padding: var(--space-4) var(--space-8);
  font-size: var(--text-base);
}

.btn-icon {
  padding: var(--space-2);
  border-radius: var(--radius-full);
}
```

## 2.4 البطاقات (Cards)

```css
.card {
  background: white;
  border-radius: var(--radius-2xl);
  box-shadow: var(--shadow-sm);
  transition: all var(--transition-base) var(--ease);
  overflow: hidden;
}

.card-hoverable:hover {
  transform: translateY(-4px);
  box-shadow: var(--shadow-lg);
}

.card-header {
  padding: var(--space-6);
  border-bottom: 1px solid var(--gray-100);
}

.card-body {
  padding: var(--space-6);
}

.card-footer {
  padding: var(--space-6);
  border-top: 1px solid var(--gray-100);
  background: var(--gray-50);
}
```

## 2.5 النماذج والحقول (Forms)

```css
.form-group {
  margin-bottom: var(--space-4);
}

.form-label {
  display: block;
  margin-bottom: var(--space-2);
  font-size: var(--text-sm);
  font-weight: 500;
  color: var(--gray-700);
}

.form-input {
  width: 100%;
  padding: var(--space-3) var(--space-4);
  border: 2px solid var(--gray-200);
  border-radius: var(--radius-lg);
  font-family: inherit;
  font-size: var(--text-base);
  transition: all var(--transition-fast) var(--ease);
  background: white;
}

.form-input:focus {
  outline: none;
  border-color: var(--beza-purple);
  box-shadow: 0 0 0 3px rgba(99,102,241,0.1);
}

.form-input.error {
  border-color: var(--beza-red);
}

.form-error {
  margin-top: var(--space-2);
  font-size: var(--text-xs);
  color: var(--beza-red);
}

.form-hint {
  margin-top: var(--space-2);
  font-size: var(--text-xs);
  color: var(--gray-500);
}
```

## 2.6 الجداول (Tables)

```css
.table-container {
  overflow-x: auto;
  border-radius: var(--radius-2xl);
  background: white;
  box-shadow: var(--shadow-sm);
}

.table {
  width: 100%;
  border-collapse: collapse;
}

.table thead {
  background: var(--gray-50);
  border-bottom: 1px solid var(--gray-200);
}

.table th {
  padding: var(--space-4);
  text-align: right;
  font-weight: 600;
  color: var(--gray-600);
  font-size: var(--text-sm);
}

.table td {
  padding: var(--space-4);
  border-bottom: 1px solid var(--gray-100);
}

.table tbody tr:hover {
  background: var(--gray-50);
}
```

## 2.7 التنبيهات والإشعارات (Alerts)

```css
.alert {
  padding: var(--space-4) var(--space-6);
  border-radius: var(--radius-lg);
  margin-bottom: var(--space-4);
  display: flex;
  align-items: center;
  gap: var(--space-3);
}

.alert-success {
  background: #D1FAE5;
  color: #065F46;
  border-right: 4px solid var(--beza-green);
}

.alert-error {
  background: #FEE2E2;
  color: #991B1B;
  border-right: 4px solid var(--beza-red);
}

.alert-warning {
  background: #FEF3C7;
  color: #92400E;
  border-right: 4px solid var(--beza-gold);
}

.alert-info {
  background: #DBEAFE;
  color: #1E40AF;
  border-right: 4px solid var(--beza-blue);
}
```

## 2.8 التجاوب (Responsive) و RTL

```css
* {
  margin: 0;
  padding: 0;
  box-sizing: border-box;
}

html {
  direction: rtl;
  scroll-behavior: smooth;
}

.container {
  max-width: 1280px;
  margin: 0 auto;
  padding: 0 var(--space-4);
}

/* نقاط التوقف */
@media (max-width: 1024px) {
  .container { max-width: 1024px; }
  html { font-size: 15px; }
}

@media (max-width: 768px) {
  .container { padding: 0 var(--space-4); }
  html { font-size: 14px; }
  h1 { font-size: var(--text-4xl); }
  h2 { font-size: var(--text-3xl); }
}

@media (max-width: 640px) {
  .container { padding: 0 var(--space-3); }
  .card-body { padding: var(--space-4); }
}
```
