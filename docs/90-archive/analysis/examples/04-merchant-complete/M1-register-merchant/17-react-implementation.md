# 17 - تطبيق React (React Implementation) - تسجيل تاجر (Merchant Registration)

```jsx
// hooks/useMerchantRegister.js
import { useState, useCallback } from 'react';
import { merchantApi } from '../services/api';

export function useMerchantRegister() {
  const [state, setState] = useState({ loading: false, success: false, error: null, merchant: null });
  const submit = useCallback(async (formData) => {
    setState(prev => ({ ...prev, loading: true, error: null }));
    try {
      const res = await merchantApi.register(formData);
      setState({ loading: false, success: true, error: null, merchant: res.data.data.merchant });
    } catch (err) {
      setState({ loading: false, success: false, error: err.response?.data?.message || 'فشل التسجيل', merchant: null });
    }
  }, []);
  return { ...state, submit };
}

// pages/merchant/MerchantRegisterPage.jsx
export default function MerchantRegisterPage() {
  const { loading, error, submit } = useMerchantRegister();
  const handleSubmit = (e) => { e.preventDefault(); submit(new FormData(e.target)); };
  return (
    <div>
      <h1>تسجيل تاجر</h1>
      {error && <div className="alert-error">{error}</div>}
      <form onSubmit={handleSubmit}>
        <input name="business_name" placeholder="اسم المتجر" required />
        <input name="commercial_registration" placeholder="السجل التجاري" required />
        <button type="submit" disabled={loading}>{loading ? 'جاري...' : 'تسجيل'}</button>
      </form>
    </div>
  );
}
```
