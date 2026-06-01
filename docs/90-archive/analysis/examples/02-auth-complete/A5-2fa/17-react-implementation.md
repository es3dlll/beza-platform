# 17 - تطبيق React (React Implementation) - A5: المصادقة الثنائية (2FA - TOTP)

## API Service

```javascript
// services/api.js — إضافة دوال 2FA
export const twoFactorApi = {
  enable: () => api.post('/auth/2fa/enable'),
  verify: (code) => api.post('/auth/2fa/verify', { code }),
  disable: (password, code) => api.post('/auth/2fa/disable', { password, code }),
};
```

## هوك مخصص (Custom Hook)

```javascript
// hooks/useTwoFactor.js
import { useState, useCallback } from 'react';
import { twoFactorApi } from '../services/api';

export function useTwoFactor() {
  const [state, setState] = useState({
    loading: false, error: null,
    setupData: null, enabled: false,
  });

  const enable = useCallback(async () => {
    setState(prev => ({ ...prev, loading: true, error: null }));
    try {
      const response = await twoFactorApi.enable();
      setState(prev => ({ ...prev, loading: false, setupData: response.data.data }));
    } catch (err) {
      setState(prev => ({ ...prev, loading: false, error: err.response?.data?.message }));
    }
  }, []);

  const verify = useCallback(async (code) => {
    setState(prev => ({ ...prev, loading: true, error: null }));
    try {
      await twoFactorApi.verify(code);
      setState(prev => ({ ...prev, loading: false, enabled: true, setupData: null }));
    } catch (err) {
      setState(prev => ({ ...prev, loading: false, error: err.response?.data?.message }));
    }
  }, []);

  const disable = useCallback(async (password, code) => {
    setState(prev => ({ ...prev, loading: true, error: null }));
    try {
      await twoFactorApi.disable(password, code);
      setState(prev => ({ ...prev, loading: false, enabled: false }));
    } catch (err) {
      setState(prev => ({ ...prev, loading: false, error: err.response?.data?.message }));
    }
  }, []);

  return { ...state, enable, verify, disable };
}
```

## TwoFactorSetupPage

```jsx
// pages/TwoFactorSetupPage.jsx
import React, { useState } from 'react';
import { useTwoFactor } from '../hooks/useTwoFactor';

export default function TwoFactorSetupPage() {
  const { loading, error, setupData, enabled, enable, verify } = useTwoFactor();
  const [code, setCode] = useState('');

  if (!setupData && !enabled) {
    return (
      <div className="twofa-page">
        <h1>المصادقة الثنائية</h1>
        <p>أضف طبقة أمان إضافية لحسابك</p>
        <button onClick={enable} disabled={loading}>
          {loading ? 'جاري...' : 'تفعيل 2FA'}
        </button>
        {error && <div className="alert alert-error">{error}</div>}
      </div>
    );
  }

  if (setupData) {
    return (
      <div className="twofa-setup">
        <h1>امسح رمز QR</h1>
        <p>استخدم Google Authenticator لمسح الرمز</p>
        <img src={setupData.qr_code} alt="QR Code" width="200" />
        <p>أو أدخل المفتاح: <strong>{setupData.secret}</strong></p>
        <input placeholder="أدخل الرمز (6 أرقام)" value={code} onChange={e => setCode(e.target.value)} maxLength={6} />
        <button onClick={() => verify(code)} disabled={loading || code.length !== 6}>
          {loading ? 'جاري...' : 'تأكيد'}
        </button>
        {error && <div className="alert alert-error">{error}</div>}
      </div>
    );
  }

  return (
    <div className="twofa-enabled">
      <h1>✓ تم التفعيل</h1>
      <p>المصادقة الثنائية مفعلة لحسابك</p>
    </div>
  );
}
```
