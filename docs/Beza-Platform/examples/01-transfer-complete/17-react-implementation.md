# 17 - تطبيق React (React Implementation) - التحويل بين المستخدمين (P2P Transfer)

## هيكل الملفات

```
user-frontend/src/
├── pages/
│   └── TransferPage.jsx
├── components/
│   └── Transfer/
│       ├── TransferForm.jsx
│       ├── RecipientField.jsx
│       ├── AmountField.jsx
│       └── PinField.jsx
├── hooks/
│   └── useTransfer.js
├── services/
│   └── api.js
└── context/
    └── AuthContext.jsx
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

export const transferApi = {
  send: (data) => api.post('/transfer', data),
};

export default api;
```

## هوك مخصص (Custom Hook)

```javascript
// hooks/useTransfer.js
import { useState, useCallback } from 'react';
import { transferApi } from '../services/api';

export function useTransfer() {
  const [state, setState] = useState({
    loading: false,
    success: null,
    error: null,
    transaction: null,
    newBalance: null,
  });

  const submit = useCallback(async ({ toPhone, amount, currency, pin, description }) => {
    setState(prev => ({ ...prev, loading: true, error: null, success: null }));

    try {
      const response = await transferApi.send({
        to_phone: toPhone,
        amount,
        currency,
        pin,
        description: description || undefined,
      });

      const { transaction, new_balance } = response.data.data;

      setState({
        loading: false,
        success: true,
        error: null,
        transaction,
        newBalance: new_balance,
      });

      return { transaction, newBalance: new_balance };
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
        transaction: null,
        newBalance: null,
      }));

      throw new Error(message);
    }
  }, []);

  const reset = useCallback(() => {
    setState({ loading: false, success: null, error: null, transaction: null, newBalance: null });
  }, []);

  return { ...state, submit, reset };
}
```

## TransferForm Component

```jsx
// components/Transfer/TransferForm.jsx
import React, { useState } from 'react';

export default function TransferForm({ onSubmit, loading, error }) {
  const [form, setForm] = useState({
    toPhone: '',
    amount: '',
    currency: 'USD',
    pin: '',
    description: '',
  });
  const [errors, setErrors] = useState({});

  const validate = () => {
    const errs = {};
    if (!form.toPhone) errs.toPhone = 'رقم الهاتف مطلوب';
    if (!form.amount || isNaN(form.amount) || Number(form.amount) < 1)
      errs.amount = 'مبلغ صحيح مطلوب';
    if (!form.pin || form.pin.length !== 4)
      errs.pin = 'PIN يجب أن يكون 4 أرقام';
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!validate()) return;
    onSubmit({
      toPhone: form.toPhone,
      amount: Number(form.amount),
      currency: form.currency,
      pin: form.pin,
      description: form.description,
    });
  };

  const handleChange = (field) => (e) => {
    setForm(prev => ({ ...prev, [field]: e.target.value }));
    if (errors[field]) setErrors(prev => ({ ...prev, [field]: null }));
  };

  return (
    <form onSubmit={handleSubmit} className="transfer-form">
      <div className="form-group">
        <label>رقم الهاتف</label>
        <input
          type="tel"
          value={form.toPhone}
          onChange={handleChange('toPhone')}
          placeholder="963XXXXXXXXX"
          dir="ltr"
        />
        {errors.toPhone && <span className="error">{errors.toPhone}</span>}
      </div>

      <div className="form-group">
        <label>المبلغ</label>
        <div className="amount-input">
          <input
            type="number"
            value={form.amount}
            onChange={handleChange('amount')}
            min="1"
            step="0.01"
            dir="ltr"
          />
          <select value={form.currency} onChange={handleChange('currency')}>
            <option value="USD">USD</option>
            <option value="SYP">SYP</option>
          </select>
        </div>
        {errors.amount && <span className="error">{errors.amount}</span>}
      </div>

      <div className="form-group">
        <label>رمز PIN</label>
        <input
          type="password"
          value={form.pin}
          onChange={handleChange('pin')}
          maxLength={4}
          inputMode="numeric"
          pattern="[0-9]*"
          autoComplete="off"
        />
        {errors.pin && <span className="error">{errors.pin}</span>}
      </div>

      <div className="form-group">
        <label>وصف (اختياري)</label>
        <input
          type="text"
          value={form.description}
          onChange={handleChange('description')}
          maxLength={255}
        />
      </div>

      {error && <div className="alert alert-error">{error}</div>}

      <button type="submit" disabled={loading} className="btn-primary">
        {loading ? 'جاري التحويل...' : 'تحويل'}
      </button>
    </form>
  );
}
```

## TransferPage

```jsx
// pages/TransferPage.jsx
import React from 'react';
import TransferForm from '../components/Transfer/TransferForm';
import { useTransfer } from '../hooks/useTransfer';

export default function TransferPage() {
  const { loading, success, error, transaction, newBalance, submit, reset } = useTransfer();

  if (success && transaction) {
    return (
      <div className="transfer-success">
        <div className="success-icon">✓</div>
        <h1>تم التحويل بنجاح</h1>

        <div className="transaction-details">
          <div className="detail-row">
            <span>رقم المرجع</span>
            <span dir="ltr">{transaction.reference_number}</span>
          </div>
          <div className="detail-row">
            <span>المبلغ</span>
            <span>{transaction.amount} {transaction.currency}</span>
          </div>
          <div className="detail-row">
            <span>المستلم</span>
            <span>{transaction.receiver.name}</span>
          </div>
          <div className="detail-row">
            <span>الرصيد المتبقي</span>
            <span className="balance">{newBalance} {transaction.currency}</span>
          </div>
        </div>

        <button onClick={reset} className="btn-primary">
          تحويل جديد
        </button>
      </div>
    );
  }

  return (
    <div className="transfer-page">
      <h1>تحويل</h1>
      <p className="subtitle">حول أموال لمستخدم آخر في Beza</p>
      <p className="fee-note">الخدمة مجانية — 0% رسوم</p>

      <div className="limits-info">
        <span>الحد اليومي: 2,000 USD / 2,000,000 SYP</span>
      </div>

      <TransferForm onSubmit={submit} loading={loading} error={error} />
    </div>
  );
}
```

## CSS (أساسي)

```css
/* TransferPage.css — مضمن في الـ RTL */

.transfer-page { max-width: 480px; margin: 0 auto; padding: 24px; }
.transfer-form { display: flex; flex-direction: column; gap: 20px; }
.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group label { font-weight: 600; color: #374151; }
.form-group input,
.form-group select {
  padding: 12px; border: 1px solid #d1d5db; border-radius: 8px;
  font-size: 16px; transition: border-color 0.2s;
}
.form-group input:focus,
.form-group select:focus { border-color: #2563eb; outline: none; }
.amount-input { display: flex; gap: 8px; }
.amount-input input { flex: 1; }
.amount-input select { width: 100px; }
.error { color: #dc2626; font-size: 13px; }
.alert-error {
  background: #fef2f2; color: #dc2626; padding: 12px;
  border-radius: 8px; border: 1px solid #fecaca;
}
.btn-primary {
  background: #2563eb; color: white; padding: 14px; border: none;
  border-radius: 8px; font-size: 16px; font-weight: 600;
  cursor: pointer; transition: background 0.2s;
}
.btn-primary:disabled { background: #93c5fd; cursor: not-allowed; }
.btn-primary:hover:not(:disabled) { background: #1d4ed8; }
.transfer-success { text-align: center; padding: 48px 24px; }
.success-icon {
  width: 64px; height: 64px; background: #059669; color: white;
  border-radius: 50%; display: flex; align-items: center;
  justify-content: center; font-size: 32px; margin: 0 auto 16px;
}
.transaction-details { margin: 24px 0; text-align: right; }
.detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e7eb; }
.balance { font-weight: 700; color: #059669; }
.limits-info {
  background: #eff6ff; padding: 8px 12px; border-radius: 6px;
  font-size: 13px; color: #2563eb; text-align: center; margin: 12px 0;
}
.fee-note { color: #059669; font-weight: 500; }
.subtitle { color: #6b7280; }
```
