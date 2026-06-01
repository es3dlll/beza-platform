# 17 - تطبيق React (React Implementation) — رمز التحقق (OTP)

## هوك مخصص (Custom Hook)

```javascript
// hooks/useOtp.js
import { useState, useCallback } from 'react';
import { authApi } from '../services/api';

export function useOtp() {
  const [state, setState] = useState({ loading: false, error: null, codeSent: false, verified: false });

  const requestOtp = useCallback(async (phone) => {
    setState({ loading: true, error: null, codeSent: false, verified: false });
    try {
      await authApi.post('/auth/request-otp', { phone });
      setState(prev => ({ ...prev, loading: false, codeSent: true }));
    } catch (err) {
      setState(prev => ({ ...prev, loading: false, error: err.response?.data?.message || 'فشل الإرسال' }));
    }
  }, []);

  const verifyOtp = useCallback(async (phone, code) => {
    setState(prev => ({ ...prev, loading: true, error: null }));
    try {
      await authApi.post('/auth/verify-otp', { phone, otp: code });
      setState(prev => ({ ...prev, loading: false, verified: true }));
    } catch (err) {
      setState(prev => ({ ...prev, loading: false, error: err.response?.data?.message || 'رمز خاطئ' }));
    }
  }, []);

  return { ...state, requestOtp, verifyOtp };
}
```

## OTP Verification Page

```jsx
// pages/OtpVerificationPage.jsx
import React, { useState, useEffect, useRef } from 'react';
import { useOtp } from '../hooks/useOtp';

export default function OtpVerificationPage({ phone }) {
  const { loading, error, codeSent, verified, requestOtp, verifyOtp } = useOtp();
  const [code, setCode] = useState(['', '', '', '', '', '']);
  const inputRefs = useRef([]);

  useEffect(() => { requestOtp(phone); }, []);

  useEffect(() => {
    if (verified) window.location.href = '/dashboard';
  }, [verified]);

  const handleChange = (index, value) => {
    if (!/^\d?$/.test(value)) return;
    const newCode = [...code];
    newCode[index] = value;
    setCode(newCode);
    if (value && index < 5) inputRefs.current[index + 1]?.focus();
    if (index === 5 && newCode.every(c => c)) {
      verifyOtp(phone, newCode.join(''));
    }
  };

  return (
    <div className="otp-page">
      <h1>تأكيد رقم الهاتف</h1>
      <p>أدخل الرمز المرسل إلى {phone}</p>
      <div className="otp-inputs">
        {code.map((digit, i) => (
          <input key={i} ref={el => inputRefs.current[i] = el}
            type="text" maxLength={1} value={digit}
            onChange={e => handleChange(i, e.target.value)}
            className="otp-digit" inputMode="numeric" />
        ))}
      </div>
      {error && <div className="alert alert-error">{error}</div>}
      <button onClick={() => requestOtp(phone)} disabled={loading}>إعادة إرسال</button>
    </div>
  );
}
```
