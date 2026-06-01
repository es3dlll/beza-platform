# 17 - تطبيق React (React Implementation) - إنشاء المحفظة المزدوجة (W1 Create Wallet)

## هيكل الملفات

```
user-frontend/src/
├── pages/
│   └── RegisterPage.jsx
├── components/
│   └── Register/
│       ├── RegisterForm.jsx
│       └── RegisterSuccess.jsx
├── hooks/
│   └── useRegister.js
└── services/
    └── api.js
```

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
  register: (data) => api.post('/register', data),
};

export default api;
```

## هوك مخصص (Custom Hook)

```javascript
// hooks/useRegister.js
import { useState, useCallback } from 'react';
import { authApi } from '../services/api';
import { tokenService } from '../services/tokenService';

export function useRegister() {
  const [state, setState] = useState({
    loading: false,
    success: null,
    error: null,
    user: null,
    wallets: null,
  });

  const submit = useCallback(async ({ name, phone, password, pinCode }) => {
    setState(prev => ({ ...prev, loading: true, error: null, success: null }));

    try {
      const response = await authApi.register({
        name,
        phone,
        password,
        pin_code: pinCode,
      });

      const { user, token, expires_in, wallets } = response.data.data;

      tokenService.saveTokens({ accessToken: token, expiresIn: expires_in });

      setState({
        loading: false,
        success: true,
        error: null,
        user,
        wallets,
      });

      return { user, token, wallets };
    } catch (err) {
      const message =
        err.response?.data?.message ||
        err.response?.data?.errors?.[Object.keys(err.response?.data?.errors || {})[0]]?.[0] ||
        'حدث خطأ غير متوقع';

      setState(prev => ({
        ...prev,
        loading: false,
        success: false,
        error: message,
      }));

      throw new Error(message);
    }
  }, []);

  return { ...state, submit };
}
```

## RegisterPage

```jsx
// pages/RegisterPage.jsx
import React from 'react';
import RegisterForm from '../components/Register/RegisterForm';
import { useRegister } from '../hooks/useRegister';

export default function RegisterPage() {
  const { loading, success, error, user, wallets, submit } = useRegister();

  if (success && user) {
    return (
      <div className="register-success">
        <div className="success-icon">✓</div>
        <h1>مرحباً بك في Beza!</h1>
        <p>تم إنشاء حسابك والمحافظ بنجاح</p>

        <div className="wallets-card">
          <h3>محافظك</h3>
          <div className="wallet-row">
            <span>محفظة SYP</span>
            <span>{wallets.syp.wallet_number}</span>
            <span className="balance">{wallets.syp.balance} SYP</span>
          </div>
          <div className="wallet-row">
            <span>محفظة USD</span>
            <span>{wallets.usd.wallet_number}</span>
            <span className="balance bonus">{wallets.usd.balance} USD</span>
          </div>
          <div className="bonus-note">+ 5$ هدية ترحيبية! 🎉</div>
        </div>

        <button
          onClick={() => window.location.href = '/dashboard'}
          className="btn-primary"
        >
          الذهاب للرئيسية
        </button>
      </div>
    );
  }

  return (
    <div className="register-page">
      <h1>إنشاء حساب جديد</h1>
      <div className="bonus-banner">
        احصل على 5$ هدية عند التسجيل!
      </div>
      <RegisterForm onSubmit={submit} loading={loading} error={error} />
    </div>
  );
}
```

## RegisterForm Component

```jsx
// components/Register/RegisterForm.jsx
import React, { useState } from 'react';

export default function RegisterForm({ onSubmit, loading, error }) {
  const [form, setForm] = useState({
    name: '',
    phone: '',
    password: '',
    pinCode: '',
  });
  const [errors, setErrors] = useState({});

  const validate = () => {
    const errs = {};
    if (!form.name || form.name.length < 2) errs.name = 'الاسم مطلوب (حرفان على الأقل)';
    if (!form.phone) errs.phone = 'رقم الهاتف مطلوب';
    if (!form.password || form.password.length < 8) errs.password = 'كلمة المرور 8 أحرف على الأقل';
    if (!form.pinCode || form.pinCode.length !== 4) errs.pinCode = 'PIN يجب أن يكون 4 أرقام';
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!validate()) return;
    onSubmit({ ...form });
  };

  const handleChange = (field) => (e) => {
    setForm(prev => ({ ...prev, [field]: e.target.value }));
    if (errors[field]) setErrors(prev => ({ ...prev, [field]: null }));
  };

  return (
    <form onSubmit={handleSubmit} className="register-form">
      <div className="form-group">
        <label>الاسم</label>
        <input type="text" value={form.name} onChange={handleChange('name')} />
        {errors.name && <span className="error">{errors.name}</span>}
      </div>

      <div className="form-group">
        <label>رقم الهاتف</label>
        <input type="tel" value={form.phone} onChange={handleChange('phone')} dir="ltr" placeholder="963XXXXXXXXX" />
        {errors.phone && <span className="error">{errors.phone}</span>}
      </div>

      <div className="form-group">
        <label>كلمة المرور</label>
        <input type="password" value={form.password} onChange={handleChange('password')} />
        {errors.password && <span className="error">{errors.password}</span>}
      </div>

      <div className="form-group">
        <label>PIN (4 أرقام)</label>
        <input type="password" value={form.pinCode} onChange={handleChange('pinCode')} maxLength={4} inputMode="numeric" />
        {errors.pinCode && <span className="error">{errors.pinCode}</span>}
      </div>

      {error && <div className="alert alert-error">{error}</div>}

      <button type="submit" disabled={loading} className="btn-primary">
        {loading ? 'جاري التسجيل...' : 'تسجيل'}
      </button>
    </form>
  );
}
```

## CSS

```css
.register-page { max-width: 480px; margin: 0 auto; padding: 24px; }
.bonus-banner { background: #fef3c7; color: #92400e; padding: 12px; border-radius: 8px; text-align: center; margin: 16px 0; font-weight: 600; }
.register-form { display: flex; flex-direction: column; gap: 20px; }
.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group label { font-weight: 600; color: #374151; }
.form-group input { padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; }
.form-group input:focus { border-color: #2563eb; outline: none; }
.error { color: #dc2626; font-size: 13px; }
.alert-error { background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 8px; }
.btn-primary { background: #2563eb; color: white; padding: 14px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; }
.btn-primary:disabled { background: #93c5fd; cursor: not-allowed; }
.register-success { text-align: center; padding: 48px 24px; }
.success-icon { width: 64px; height: 64px; background: #059669; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px; }
.wallets-card { background: #f9fafb; border-radius: 12px; padding: 20px; margin: 24px 0; text-align: right; }
.wallet-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e7eb; }
.balance { font-weight: 700; }
.balance.bonus { color: #059669; }
.bonus-note { margin-top: 12px; color: #059669; font-weight: 600; }
```
