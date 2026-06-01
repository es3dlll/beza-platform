# 17 - تطبيق React (React Implementation) - شحن رصيد هاتف (Phone Topup)

## هيكل الملفات

```
user-frontend/src/
├── pages/
│   └── PhoneTopupPage.jsx
├── components/
│   └── PhoneTopup/
│       └── PhoneTopupForm.jsx
├── hooks/
│   └── usePhoneTopup.js
└── services/
    └── api.js
```

## هوك مخصص (Custom Hook)

```javascript
// hooks/usePhoneTopup.js
import { useState, useCallback } from 'react';
import api from '../services/api';

export function usePhoneTopup() {
  const [state, setState] = useState({
    loading: false,
    success: null,
    error: null,
    transaction: null,
    newBalance: null,
  });

  const submit = useCallback(async ({ amount, currency, pin }) => {
    setState(prev => ({ ...prev, loading: true, error: null, success: null }));

    try {
      const response = await api.post('/topup', {
        amount, currency, pin,
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
        err.response?.data?.message || 'حدث خطأ غير متوقع';

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

## PhoneTopupForm Component

```jsx
// components/PhoneTopup/PhoneTopupForm.jsx
import React, { useState } from 'react';

export default function PhoneTopupForm({ onSubmit, loading, error }) {
  const [form, setForm] = useState({
    amount: '',
    currency: 'USD',
    pin: '',
  });
  const [errors, setErrors] = useState({});

  const validate = () => {
    const errs = {};
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
      amount: Number(form.amount),
      currency: form.currency,
      pin: form.pin,
    });
  };

  return (
    <form onSubmit={handleSubmit} className="form">
      <div className="form-group">
        <label>المبلغ</label>
        <input
          type="number"
          value={form.amount}
          onChange={e => setForm(p => ({ ...p, amount: e.target.value }))}
          min="1" step="0.01"
        />
        {errors.amount && <span className="error">{errors.amount}</span>}
      </div>

      <div className="form-group">
        <label>العملة</label>
        <select value={form.currency} onChange={e => setForm(p => ({ ...p, currency: e.target.value }))}>
          <option value="USD">USD</option>
          <option value="SYP">SYP</option>
        </select>
      </div>

      <div className="form-group">
        <label>رمز PIN</label>
        <input type="password" value={form.pin} maxLength={4}
          onChange={e => setForm(p => ({ ...p, pin: e.target.value }))} />
        {errors.pin && <span className="error">{errors.pin}</span>}
      </div>

      {error && <div className="alert alert-error">{error}</div>}

      <button type="submit" disabled={loading} className="btn-primary">
        {loading ? 'جاري التنفيذ...' : 'تأكيد'}
      </button>
    </form>
  );
}
```

## PhoneTopupPage

```jsx
// pages/PhoneTopupPage.jsx
import React from 'react';
import PhoneTopupForm from '../components/PhoneTopup/PhoneTopupForm';
import { usePhoneTopup } from '../hooks/usePhoneTopup';

export default function PhoneTopupPage() {
  const { loading, success, error, transaction, newBalance, submit, reset } = usePhoneTopup();

  if (success && transaction) {
    return (
      <div className="success-page">
        <h1>تمت العملية بنجاح</h1>
        <p>رقم المرجع: {transaction.reference_number}</p>
        <p className="balance">الرصيد الجديد: {newBalance}</p>
        <button onClick={reset} className="btn-primary">عملية جديدة</button>
      </div>
    );
  }

  return (
    <div className="page">
      <h1>شحن رصيد هاتف</h1>
      <PhoneTopupForm onSubmit={submit} loading={loading} error={error} />
    </div>
  );
}
```
