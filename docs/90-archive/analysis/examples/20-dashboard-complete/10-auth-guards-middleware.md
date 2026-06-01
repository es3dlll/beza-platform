# 10 - حماية الصفحة والمصادقة (Auth Guards)

## حماية المسار في App.jsx

```jsx
function Protected({ children }) {
  const { isAuth } = useAuth();
  if (!isAuth) return <Navigate to="/login" replace />;
  return children;
}

<Route path="/" element={<Protected><AppLayout /></Protected>}>
  <Route index element={<Dashboard />} />
  {/* ... باقي المسارات المحمية */}
</Route>
```

## تدفق التوكن (Auto-login)

```jsx
// main.jsx — معالجة التوكن قبل إنشاء React
const params = new URLSearchParams(window.location.search);
const sakkToken = params.get('sakk_token');
if (sakkToken) {
  tokenService.set(sakkToken);
  window.history.replaceState({}, '', window.location.pathname);
}
```

هذا يضمن أن التوكن موجود في localStorage **قبل** أن يتحقق `AuthContext` من `isAuth`.

## تسجيل الخروج

- مسح التوكن من localStorage
- `navigate('/login')`

## صلاحيات إضافية

| الشرط | الإجراء |
|-------|---------|
| بدون توكن | توجيه إلى /login |
| توكن منتهي | refresh تلقائي (interceptor) |
| توكن غير صالح | مسح وإعادة توجيه |
