# 17 - تطبيق React (React Implementation) — تسجيل الدخول (Login)

## هوك مخصص (Custom Hook)

```javascript
// hooks/useLogin.js
import { useState, useCallback } from 'react';
import { authApi } from '../services/api';
import { tokenService } from '../services/tokenService';

export function useLogin() {
  const [state, setState] = useState({ loading: false, error: null, requires2fa: false, tempToken: null });

  const submit = useCallback(async ({ phone, password, deviceId }) => {
    setState(prev => ({ ...prev, loading: true, error: null }));
    try {
      const response = await authApi.login({ phone, password, device_id: deviceId });
      const { token, expires_in, user, requires_2fa } = response.data.data;

      if (requires_2fa) {
        setState({ loading: false, error: null, requires2fa: true, tempToken: token });
        return { requires2fa: true, token };
      }

      tokenService.saveTokens({ accessToken: token, expiresIn: expires_in });
      localStorage.setItem('user', JSON.stringify(user));
      setState({ loading: false, error: null, requires2fa: false, tempToken: null });
      return { requires2fa: false, user };
    } catch (err) {
      const message = err.response?.data?.message || 'حدث خطأ';
      setState(prev => ({ ...prev, loading: false, error: message }));
      throw new Error(message);
    }
  }, []);

  return { ...state, submit };
}
```

## LoginPage

```jsx
// pages/LoginPage.jsx
import React, { useState } from 'react';
import { useLogin } from '../hooks/useLogin';
import TwoFactorForm from '../components/TwoFactorForm';

export default function LoginPage() {
  const { loading, error, requires2fa, submit } = useLogin();
  const [phone, setPhone] = useState('');
  const [password, setPassword] = useState('');

  if (requires2fa) {
    return <TwoFactorForm />;
  }

  const handleSubmit = async (e) => {
    e.preventDefault();
    try {
      await submit({ phone, password });
      window.location.href = '/dashboard';
    } catch {} // error shown in state
  };

  return (
    <div className="login-page">
      <h1>تسجيل الدخول</h1>
      <form onSubmit={handleSubmit}>
        <input placeholder="رقم الهاتف" value={phone} onChange={e => setPhone(e.target.value)} />
        <input type="password" placeholder="كلمة المرور" value={password} onChange={e => setPassword(e.target.value)} />
        {error && <div className="alert alert-error">{error}</div>}
        <button type="submit" disabled={loading}>{loading ? 'جاري...' : 'دخول'}</button>
      </form>
      <a href="/register">ليس لديك حساب؟ سجل الآن</a>
    </div>
  );
}
```
