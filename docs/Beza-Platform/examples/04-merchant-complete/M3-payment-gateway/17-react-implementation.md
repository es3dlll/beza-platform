# 17 - تطبيق React (React Implementation) - بوابة الدفع (Payment Gateway)

```jsx
// hooks/usePaymentLink.js
import { useState, useCallback } from 'react';
import { merchantApi } from '../services/api';

export function usePaymentLink() {
  const [state, setState] = useState({ loading: false, link: null, error: null });
  const create = useCallback(async (data) => {
    setState(prev => ({ ...prev, loading: true, error: null }));
    try { const res = await merchantApi.createPaymentLink(data);
      setState({ loading: false, link: res.data.data, error: null }); }
    catch (err) { setState({ loading: false, link: null, error: err.response?.data?.message }); }
  }, []);
  return { ...state, create };
}

// pages/PaymentLinkPage.jsx
import { usePaymentLink } from '../hooks/usePaymentLink';
export default function PaymentLinkPage() {
  const { loading, link, error, create } = usePaymentLink();
  const [form, setForm] = useState({ amount: '', currency: 'USD', expiryHours: 24 });
  const handleSubmit = (e) => { e.preventDefault(); create(form); };
  return (
    <div>
      <h1>رابط دفع</h1>
      {error && <div className="error">{error}</div>}
      {link && <div>الرابط: {window.location.origin}/pay/{link.token}</div>}
      <form onSubmit={handleSubmit}>
        <input type="number" placeholder="المبلغ" value={form.amount} onChange={e => setForm({...form, amount: e.target.value})} required />
        <select value={form.currency} onChange={e => setForm({...form, currency: e.target.value})}><option value="USD">USD</option><option value="SYP">SYP</option></select>
        <button type="submit" disabled={loading}>{loading ? 'جاري...' : 'إنشاء رابط'}</button>
      </form>
    </div>
  );
}
```
