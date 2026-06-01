# 14 - تحسين الأداء (Performance Optimization)

## تحميل الصفحة الأولي (Initial Load)

| المورد | الحجم | المصدر | ملاحظة |
|--------|-------|--------|--------|
| bundle JS (Vite) | ~150KB gzipped | Vite build | Tree-shaken + code split |
| QR Code library | ~20KB gzipped | qrcode npm | يُحمّل مع bundle |
| API call 1: balance | ~500B | `/wallet/balance` | أولوية قصوى |
| API call 2: user | ~300B | `/auth/me` | متزامن مع 1 |
| API call 3: rates | ~200B | `/wallet/rates` | غير حرج |
| أيقونات | ~10KB | @heroicons/react | Tree-shaken |

## استراتيجيات التحسين

### 1. تقليل الـ Re-renders

```jsx
// ❌ لا: إعادة حساب في كل ريندر
const greeting = computeGreeting(hr);

// ✓ نعم: قيمة محسوبة مرة واحدة
const greeting = hr >= 5 && hr < 12 ? 'صباح الخير' : 'مساء الخير';
```

### 2. الأيقونات الخاملة (Lazy Icons)

استخدام SVG paths مباشرة بدلاً من استيراد مكونات أيقونات كبيرة:

```jsx
// بدلاً من استيراد أيقونة full component
import { BellIcon } from '@heroicons/react/24/outline';

// استخدام SVG مباشر في تكرار الإشعارات
<svg className="w-4 h-4" viewBox="0 0 24 24" fill="none" stroke={color} ...>
  <path d={path} />
</svg>
```

### 3. استخدام CSS Animations بدلاً من JS

```jsx
// استخدم CSS animations بدلاً من setInterval أو requestAnimationFrame
animate-pulse  /* Tailwind's built-in */
transition-all duration-200
hover:scale-[1.02]
active:scale-[0.98]
```

### 4. Lazy State Initialization

```jsx
// الحالة تبدأ بشكل بسيط
const [balance, setBalance] = useState(null);  // null → Skeleton
const [qrDataUrl, setQrDataUrl] = useState(''); // '' → أيقونة placeholder
```

### 5. تحميل QR Code

```jsx
useEffect(() => {
  QRCode.toDataURL(
    JSON.stringify({ type: 'sakk_transfer', wallet: '63...' }),
    { width: 200, margin: 2 }  // دقة كافية لشاشات Retina
  ).then(setQrDataUrl);
}, []);
```

## أهداف الأداء

| المقياس | الهدف | الحالي |
|---------|-------|--------|
| First Paint (FP) | < 1s | ~500ms |
| Time to Interactive | < 2s | ~1.2s |
| API response time | < 200ms p95 | ~120ms |
| Bundle size (gzip) | < 200KB | ~180KB |
| Lighthouse Performance | > 90 | - |
