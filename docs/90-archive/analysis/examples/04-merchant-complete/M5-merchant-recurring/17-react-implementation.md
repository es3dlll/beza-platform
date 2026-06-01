# 17 - تطبيق React (React Implementation) - الفوترة المتكررة (Merchant Recurring)

```jsx
// hooks/useSubscriptions.js
import { useState, useEffect, useCallback } from 'react';
import { merchantApi } from '../services/api';

export function useSubscriptions() {
  const [subscriptions, setSubscriptions] = useState([]);
  const [loading, setLoading] = useState(false);
  const loadSubscriptions = useCallback(async () => {
    setLoading(true);
    try { const res = await merchantApi.getSubscriptions(); setSubscriptions(res.data.data); }
    finally { setLoading(false); }
  }, []);
  const createSubscription = useCallback(async (data) => {
    const res = await merchantApi.createSubscription(data);
    setSubscriptions(prev => [res.data.data, ...prev]);
    return res.data.data;
  }, []);
  useEffect(() => { loadSubscriptions(); }, [loadSubscriptions]);
  return { subscriptions, loading, createSubscription };
}

// pages/SubscriptionPage.jsx
export default function SubscriptionPage() {
  const { subscriptions, loading, createSubscription } = useSubscriptions();
  const [form, setForm] = useState({ customer_phone: '', amount: '', currency: 'USD', interval: 'monthly', max_cycles: 12 });
  const handleSubmit = async (e) => { e.preventDefault(); await createSubscription(form); };
  return (
    <div>
      <h1>الاشتراكات</h1>
      <form onSubmit={handleSubmit}>
        <input placeholder="رقم العميل" value={form.customer_phone} onChange={e => setForm({...form, customer_phone: e.target.value})} required />
        <input type="number" placeholder="المبلغ" value={form.amount} onChange={e => setForm({...form, amount: e.target.value})} required />
        <button type="submit" disabled={loading}>إنشاء اشتراك</button>
      </form>
      {subscriptions.map(s => <div key={s.id}>{s.amount} {s.currency} - {s.status}</div>)}
    </div>
  );
}
```
