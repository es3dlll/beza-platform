# 12 - نظام الإشعارات والقائمة المنسدلة (Notification System)

## هيكل الإشعارات

```jsx
const notifications = [
  { id: 1, type: 'in',    title: 'تحويل وارد',    desc: '٥٠٬٠٠٠ ل.س من أحمد الخطيب', time: 'منذ ١٠ د' },
  { id: 2, type: 'out',   title: 'تحويل صادر',    desc: '١٢٬٥٠٠ ل.س إلى كهرباء سورية', time: 'منذ ٢ س' },
  { id: 3, type: 'rate',  title: 'تحديث السعر',   desc: '١ USD = ١٢٬٥٠٠ SYP',         time: 'منذ ٥ س' },
  { id: 4, type: 'check', title: 'تم توثيق الحساب', desc: 'تم تأكيد معلومات الهوية بنجاح', time: 'منذ ١ ي' },
];
```

## نظام الأيقونات الملونة (Color-coded Icons)

| النوع | اللون | الأيقونة | المعنى |
|-------|-------|----------|--------|
| `in` | أخضر `rgba(34,197,94,0.4)` | ArrowDown | تحويل وارد |
| `out` | أحمر `rgba(239,68,68,0.4)` | ArrowUp | تحويل صادر |
| `rate` | ذهبي `#F5A623` | Refresh | تحديث سعر |
| `check` | أخضر `rgba(34,197,94,0.4)` | CheckCircle | توثيق الحساب |

## أيقونات SVG

كل نوع له مسار SVG فريد:

```jsx
const icons = {
  in:    { path: 'M12 19V5m0 0l-7 7m7-7l7 7' },
  out:   { path: 'M12 5v14m0 0l7-7m-7 7l-7-7' },
  rate:  { path: 'M4 4v5h.582m15.356 2A8.001 8.001 0 004.582 9m0 0H9m11 11v-5h-.581m0 0a8.003 8.003 0 01-15.357-2m15.357 2H15' },
  check: { path: 'M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z' },
};
```

## القائمة المنسدلة (Dropdown Menu)

| العنصر | الخصائص |
|--------|---------|
| الموقع | `absolute left-0 top-full mt-2 z-40` |
| العرض | `w-80` |
| الخلفية | `rgba(10,14,34,0.98)` + `backdropFilter: blur(24px)` |
| الحدود | `1px solid rgba(255,255,255,0.06)` |
| الظل | `0 20px 60px rgba(0,0,0,0.4)` |
| الزوايا | `rounded-2xl` |
| القناع | `fixed inset-0 z-30` (يغلق عند النقر خارجه) |

## تدفق التفاعل

```
User → نقر جرس الإشعارات
    → showNotifs = true
    → Dropdown يظهر مع قناع خلفي
    → نقر على إشعار → navigate(/notifications/:id)
    → نقر "عرض جميع" → navigate(/notifications)
    → نقر خارج dropdown → يختفي (useEffect + mousedown)
```
