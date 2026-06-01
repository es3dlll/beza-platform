# 17 - تطبيق React (React Implementation) - تحويل بين العملات (W3 Exchange)

## هيكل الملفات

```
user-frontend/src/
├── pages/
│   └── ExchangePage.jsx
├── components/
│   └── Exchange/
│       ├── ExchangeForm.jsx
│       ├── RateSummary.jsx
│       └── ExchangeSuccess.jsx
├── hooks/
│   └── useExchange.js
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

export const exchangeApi = {
  exchange: (data) => api.post('/wallet/exchange', data),
};

export default api;
```

## هوك مخصص (Custom Hook)

```javascript
// hooks/useExchange.js
import { useState, useCallback } from 'react';
import { exchangeApi } from '../services/api';

export function useExchange() {
  const [state, setState] = useState({
    loading: false,
    success: null,
    error: null,
    transaction: null,
    newBalances: null,
  });

  const submit = useCallback(async ({ fromCurrency, toCurrency, amount }) => {
    setState(prev => ({ ...prev, loading: true, error: null, success: null }));

    try {
      const response = await exchangeApi.exchange({
        from_currency: fromCurrency,
        to_currency: toCurrency,
        amount,
      });

      const { transaction, new_balances } = response.data.data;

      setState({
        loading: false,
        success: true,
        error: null,
        transaction,
        newBalances: new_balances,
      });

      return { transaction, newBalances: new_balances };
    } catch (err) {
      const message =
        err.response?.data?.message ||
        err.response?.data?.errors?.[Object.keys(err.response?.data?.errors || {})[0]]?.[0] ||
        'حدث خطأ في الصرافة';

      setState(prev => ({
        ...prev,
        loading: false,
        success: false,
        error: message,
      }));

      throw new Error(message);
    }
  }, []);

  const reset = useCallback(() => {
    setState({ loading: false, success: null, error: null, transaction: null, newBalances: null });
  }, []);

  return { ...state, submit, reset };
}
```

## ExchangePage

```jsx
// pages/ExchangePage.jsx
import React from 'react';
import ExchangeForm from '../components/Exchange/ExchangeForm';
import ExchangeSuccess from '../components/Exchange/ExchangeSuccess';
import { useExchange } from '../hooks/useExchange';

export default function ExchangePage() {
  const { loading, success, error, transaction, newBalances, submit, reset } = useExchange();

  if (success && transaction) {
    return <ExchangeSuccess transaction={transaction} newBalances={newBalances} onReset={reset} />;
  }

  return (
    <div className="exchange-page">
      <h1>صرافة</h1>
      <p className="subtitle">تحويل بين محفظة SYP و USD</p>

      <div className="info-box">
        <div className="info-row">الحد الأدنى: 1,000 SYP / 1 USD</div>
        <div className="info-row">الرسوم: 1.5%</div>
        <div className="info-row">السعر: 1 USD = 13,000 SYP</div>
      </div>

      <ExchangeForm onSubmit={submit} loading={loading} error={error} />
    </div>
  );
}
```

## ExchangeForm Component

```jsx
// components/Exchange/ExchangeForm.jsx
import React, { useState } from 'react';

export default function ExchangeForm({ onSubmit, loading, error }) {
  const [form, setForm] = useState({
    fromCurrency: 'SYP',
    toCurrency: 'USD',
    amount: '',
  });
  const [errors, setErrors] = useState({});

  const validate = () => {
    const errs = {};
    if (form.fromCurrency === form.toCurrency) errs.currencies = 'يجب اختيار عملتين مختلفتين';
    if (!form.amount || isNaN(form.amount) || Number(form.amount) <= 0) errs.amount = 'مبلغ صحيح مطلوب';
    setErrors(errs);
    return Object.keys(errs).length === 0;
  };

  const handleSubmit = (e) => {
    e.preventDefault();
    if (!validate()) return;
    onSubmit({ ...form, amount: Number(form.amount) });
  };

  const handleChange = (field) => (e) => {
    setForm(prev => ({ ...prev, [field]: e.target.value }));
  };

  const switchCurrencies = () => {
    setForm(prev => ({ ...prev, fromCurrency: prev.toCurrency, toCurrency: prev.fromCurrency }));
  };

  return (
    <form onSubmit={handleSubmit} className="exchange-form">
      <div className="currency-row">
        <div className="form-group">
          <label>من</label>
          <select value={form.fromCurrency} onChange={handleChange('fromCurrency')}>
            <option value="SYP">SYP - ل.س</option>
            <option value="USD">USD - $</option>
          </select>
        </div>

        <button type="button" onClick={switchCurrencies} className="switch-btn" title="تبديل العملات">
          ⇄
        </button>

        <div className="form-group">
          <label>إلى</label>
          <select value={form.toCurrency} onChange={handleChange('toCurrency')}>
            <option value="SYP">SYP - ل.س</option>
            <option value="USD">USD - $</option>
          </select>
        </div>
      </div>

      <div className="form-group">
        <label>المبلغ</label>
        <input
          type="number"
          value={form.amount}
          onChange={handleChange('amount')}
          min="0.01"
          step="0.01"
          dir="ltr"
        />
        {errors.amount && <span className="error">{errors.amount}</span>}
      </div>

      {errors.currencies && <div className="alert alert-error">{errors.currencies}</div>}
      {error && <div className="alert alert-error">{error}</div>}

      <button type="submit" disabled={loading} className="btn-primary">
        {loading ? 'جاري الصرافة...' : 'تأكيد الصرافة'}
      </button>
    </form>
  );
}
```

## ExchangeSuccess Component

```jsx
// components/Exchange/ExchangeSuccess.jsx
import React from 'react';

export default function ExchangeSuccess({ transaction, newBalances, onReset }) {
  return (
    <div className="exchange-success">
      <div className="success-icon">✓</div>
      <h1>تمت الصرافة بنجاح</h1>

      <div className="exchange-details">
        <div className="detail-row">
          <span>رقم المرجع</span>
          <span dir="ltr">{transaction.reference_number}</span>
        </div>
        <div className="detail-row highlight">
          <span>تم التحويل</span>
          <span>{transaction.amount.toLocaleString()} {transaction.from_currency}</span>
        </div>
        <div className="detail-row highlight">
          <span>المبلغ المحول</span>
          <span>{transaction.converted_amount.toFixed(2)} {transaction.to_currency}</span>
        </div>
        <div className="detail-row">
          <span>سعر الصرف</span>
          <span>1 {transaction.to_currency} = {transaction.rate.toLocaleString()} {transaction.from_currency}</span>
        </div>
        <div className="detail-row">
          <span>الرسوم</span>
          <span>{transaction.fee.toLocaleString()} {transaction.from_currency} ({transaction.fee_percentage}%)</span>
        </div>
      </div>

      <div className="new-balances">
        <h3>الرصيد الجديد</h3>
        <div className="balance-row">
          <span>SYP</span>
          <span>{newBalances.syp.toLocaleString()}</span>
        </div>
        <div className="balance-row">
          <span>USD</span>
          <span>{newBalances.usd.toFixed(2)}</span>
        </div>
      </div>

      <button onClick={onReset} className="btn-primary">صرافة جديدة</button>
    </div>
  );
}
```

## CSS

```css
.exchange-page { max-width: 480px; margin: 0 auto; padding: 24px; }
.subtitle { color: #6b7280; margin-bottom: 20px; }
.info-box { background: #eff6ff; border-radius: 8px; padding: 12px; margin-bottom: 20px; }
.info-row { font-size: 13px; color: #2563eb; padding: 4px 0; }
.exchange-form { display: flex; flex-direction: column; gap: 20px; }
.currency-row { display: flex; gap: 12px; align-items: flex-end; }
.currency-row .form-group { flex: 1; }
.switch-btn { background: #f3f4f6; border: 1px solid #d1d5db; border-radius: 50%; width: 44px; height: 44px; font-size: 20px; cursor: pointer; margin-bottom: 4px; }
.form-group { display: flex; flex-direction: column; gap: 4px; }
.form-group label { font-weight: 600; color: #374151; }
.form-group input, .form-group select { padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; }
.form-group input:focus, .form-group select:focus { border-color: #2563eb; outline: none; }
.error { color: #dc2626; font-size: 13px; }
.alert-error { background: #fef2f2; color: #dc2626; padding: 12px; border-radius: 8px; }
.btn-primary { background: #2563eb; color: white; padding: 14px; border: none; border-radius: 8px; font-size: 16px; font-weight: 600; cursor: pointer; }
.btn-primary:disabled { background: #93c5fd; cursor: not-allowed; }
.exchange-success { text-align: center; padding: 48px 24px; }
.success-icon { width: 64px; height: 64px; background: #059669; color: white; border-radius: 50%; display: flex; align-items: center; justify-content: center; font-size: 32px; margin: 0 auto 16px; }
.exchange-details { margin: 24px 0; text-align: right; }
.detail-row { display: flex; justify-content: space-between; padding: 12px 0; border-bottom: 1px solid #e5e7eb; }
.detail-row.highlight { font-weight: 600; color: #059669; }
.new-balances { background: #f9fafb; border-radius: 12px; padding: 16px; margin: 20px 0; }
.balance-row { display: flex; justify-content: space-between; padding: 8px 0; font-weight: 600; }
```
