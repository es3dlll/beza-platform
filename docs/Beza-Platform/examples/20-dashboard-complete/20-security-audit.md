# 20 - أمان الصفحة (Security Audit)

## 1. حماية التوكن (Token Security)

| الممارسة | الحالة | الشرح |
|----------|--------|-------|
| تخزين التوكن في `localStorage` | ✓ آمن (ضمن حدود) | SPA لا تستخدم cookies لتجنب CSRF |
| مسح التوكن عند 401 | ✓ | interceptor يعيد التوجيه إلى /login |
| Token refresh | ✓ | `tokenService.getValidToken()` يحاول التجديد التلقائي |
| URL parameter cleanup | ✓ | `history.replaceState` يزيل `?sakk_token=` من الـ URL |
| لا تخزين PIN | ✓ | الـ PIN يُرسل مباشرة ولا يُحفظ محلياً |

## 2. منع IDOR

```jsx
// المستخدم الحاظي من Auth Context (آمن)
const { user, setUser } = useAuth();
// → user هو المستخدم المصادق فقط
// → لا يمكن تزوير to_phone لأن API يتحقق من وجود المستلم
```

## 3. Input Sanitization

```jsx
// Exchange amount: فقط أرقام ونقطة
onChange={(e) => setExAmt(e.target.value.replace(/[^0-9.]/g, ''))}
```

ملاحظة: هذا الحماية للـ UX فقط. الـ API يقوم بالتحقق الكامل.

## 4. Rate Limiting & Brute Force

```jsx
// لا يوجد rate limiting في الـ Frontend (يتعامل معه Backend)
// Backend: throttle:30,1 → 30 طلب في الدقيقة
```

## 5. CORS

```jsx
// الـ API يسمح بـ CORS من أي أصل (*)
// آمن للتطوير المحلي — يجب تضييقه في الإنتاج
```

## 6. PIN Protection

```jsx
// PIN:
// - مخفي (type="password")
// - maxLength=4 (يتطابق مع API)
// - لا يُخزن محلياً
// - لا يُسجل في console أو network logs (HTTPS)
```

## 7. Clickjacking Protection

```jsx
// الصفحة في SPA (تخدم من index.html واحد)
// يجب إضافة header X-Frame-Options: DENY في الإنتاج
```

## 8. XSS Protection

```jsx
// React يقوم بـ auto-escaping لكل النصوص
// {user?.name || 'مستخدم'} ← آمن من XSS
// أيقونات SVG تُبنى من paths محددة مسبقاً (ليست من user input)
```

## 9. CSRF Protection

```jsx
// SPA لا تستخدم cookies للمصادقة (تستخدم Bearer Token)
// لذلك CSRF ليس threat
// API لا يقبل الطلبات بدون Authorization header
```

## 10. قائمة التحقق (Security Checklist)

| البند | الحالة |
|-------|--------|
| كل طلبات API محمية بـ Bearer Token | ✓ |
| لا توجد بيانات حساسة في localStorage غير التوكن | ✓ |
| PIN لا يُخزن محلياً | ✓ |
| Input sanitization في الحقول الرقمية | ✓ |
| React auto-escaping ضد XSS | ✓ |
| CSRF غير مطبق (Bearer Token) | ✓ |
| Rate limiting على الـ Backend | ✓ |
| CORS مفتوح (للdevelop) | ✓ |
| التوكن يُمسح عند 401 | ✓ |
| URL parameter يُنظف بعد الاستخدام | ✓ |
