# 17 - تطبيق React (React Implementation) — تسجيل (Register)

## API Service

```javascript
// services/tokenService.js
import axios from 'axios';

const TOKEN_KEY = 'access_token';
const EXPIRES_AT_KEY = 'expires_at';
const REFRESH_TOKEN_KEY = 'refresh_token';

export const tokenService = {
  saveTokens({ accessToken, expiresIn = 3600, refreshToken }) {
    const expiresAt = Date.now() + expiresIn * 1000;
    localStorage.setItem(TOKEN_KEY, accessToken);
    localStorage.setItem(EXPIRES_AT_KEY, expiresAt.toString());
    if (refreshToken) {
      localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken);
    }
  },

  getValidToken() {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) return null;

    const expiresAt = parseInt(localStorage.getItem(EXPIRES_AT_KEY) || '0');
    if (Date.now() >= expiresAt) {
      return this._refreshToken();
    }
    return token;
  },

  async _refreshToken() {
    const refreshToken = localStorage.getItem(REFRESH_TOKEN_KEY);
    if (!refreshToken) {
      this.clearToken();
      return null;
    }

    try {
      const response = await axios.post('/api/v1/auth/refresh', {}, {
        headers: { Authorization: `Bearer ${refreshToken}` },
      });
      const { token, expires_in } = response.data.data;
      this.saveTokens({ accessToken: token, expiresIn: expires_in });
      return token;
    } catch {
      this.clearToken();
      window.location.href = '/login';
      return null;
    }
  },

  clearToken() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(EXPIRES_AT_KEY);
    localStorage.removeItem(REFRESH_TOKEN_KEY);
    localStorage.removeItem('user');
  },
};
```

```javascript
// services/api.js
import axios from 'axios';
import { tokenService } from './tokenService';

const api = axios.create({
  baseURL: 'http://localhost:8000/api/v1',
  headers: { Accept: 'application/json' },
});

api.interceptors.request.use(async (config) => {
  const token = tokenService.getValidToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      const refreshed = await tokenService._refreshToken?.();
      if (refreshed) {
        error.config.headers.Authorization = `Bearer ${refreshed}`;
        return axios(error.config);
      }
    }
    return Promise.reject(error);
  }
);

export const authApi = {
  register: (data) => api.post('/auth/register', data),
  login: (data) => api.post('/auth/login', data),
  logout: () => api.post('/auth/logout'),
};
```

## هوك مخصص (Custom Hook)

```javascript
// hooks/useRegister.js
import { useState, useCallback } from 'react';
import { authApi } from '../services/api';
import { tokenService } from '../services/tokenService';

export function useRegister() {
  const [state, setState] = useState({ loading: false, success: null, error: null, data: null });

  const submit = useCallback(async (formData) => {
    setState(prev => ({ ...prev, loading: true, error: null }));
    try {
      const response = await authApi.register({
        name: formData.name,
        phone: formData.phone,
        password: formData.password,
        password_confirmation: formData.passwordConfirmation,
        pin_code: formData.pinCode,
        pin_code_confirmation: formData.pinCodeConfirmation,
      });
      const { token, expires_in } = response.data.data;
      tokenService.saveTokens({ accessToken: token, expiresIn: expires_in });
      setState({ loading: false, success: true, error: null, data: response.data.data });
      return response.data.data;
    } catch (err) {
      const message = err.response?.data?.message || 'حدث خطأ';
      setState({ loading: false, success: false, error: message, data: null });
      throw new Error(message);
    }
  }, []);

  return { ...state, submit };
}
```

## RegisterPage

```jsx
// pages/RegisterPage.jsx
import React, { useState } from 'react';
import { useRegister } from '../hooks/useRegister';

export default function RegisterPage() {
  const { loading, success, error, submit } = useRegister();
  const [form, setForm] = useState({ name: '', phone: '', password: '', passwordConfirmation: '', pinCode: '', pinCodeConfirmation: '' });
  const [errors, setErrors] = useState({});

  const validate = () => {
    const errs = {};
    if (!form.name) errs.name = 'الاسم مطلوب';
    if (!form.phone || !/^09\d{8}$/.test(form.phone)) errs.phone = 'رقم هاتف صحيح مطلوب';
    if (form.password.length < 8) errs.password = '8 أحرف على الأقل';
    if (form.password !== form.passwordConfirmation) errs.passwordConfirmation = 'غير متطابق';
    if (form.pinCode.length !== 4) errs.pinCode = '4 أرقام';
    if (form.pinCode !== form.pinCodeConfirmation) errs.pinCodeConfirmation = 'غير متطابق';
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!validate()) return;
    submit(form).then(() => window.location.href = '/dashboard');
  };

  if (success) return <div>تم التسجيل بنجاح!</div>;

  return (
    <div className="register-page">
      <h1>إنشاء حساب جديد</h1>
      <form onSubmit={handleSubmit}>
        <input placeholder="الاسم" value={form.name} onChange={e => setForm({...form, name: e.target.value})} />
        {errors.name && <span className="error">{errors.name}</span>}
        <input placeholder="رقم الهاتف (09XXXXXXXX)" value={form.phone} onChange={e => setForm({...form, phone: e.target.value})} maxLength={10} />
        {errors.phone && <span className="error">{errors.phone}</span>}
        <input type="password" placeholder="كلمة المرور" value={form.password} onChange={e => setForm({...form, password: e.target.value})} />
        {errors.password && <span className="error">{errors.password}</span>}
        <input type="password" placeholder="تأكيد كلمة المرور" value={form.passwordConfirmation} onChange={e => setForm({...form, passwordConfirmation: e.target.value})} />
        <input type="password" placeholder="PIN (4 أرقام)" value={form.pinCode} onChange={e => setForm({...form, pinCode: e.target.value})} maxLength={4} />
        <input type="password" placeholder="تأكيد PIN" value={form.pinCodeConfirmation} onChange={e => setForm({...form, pinCodeConfirmation: e.target.value})} maxLength={4} />
        {error && <div className="alert alert-error">{error}</div>}
        <button type="submit" disabled={loading}>{loading ? 'جاري...' : 'تسجيل'}</button>
      </form>
    </div>
  );
}
```
