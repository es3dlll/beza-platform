# 17 - تطبيق React (React Implementation) - إنشاء صفقة (Admin)

## CreateDealPage

```jsx
import React, { useState } from 'react';
import api from '../../services/api';

export default function AdminDealCreatePage() {
  const [form, setForm] = useState({
    title: '', description: '', target_amount: '',
    currency: 'USD', expected_profit_percentage: '',
    duration_days: '', category: '', risk_level: 'medium',
  });
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  const handleSubmit = async (e) => {
    e.preventDefault();
    setLoading(true);
    setError(null);
    try {
      const res = await api.post('/admin/deals', form);
      setSuccess(res.data.data.deal);
    } catch (err) {
      setError(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="admin-deal-create">
      <h1>إنشاء صفقة جديدة</h1>
      {error && <div className="alert-error">{error}</div>}
      {success && <div className="alert-success">تم إنشاء الصفقة #{success.id}</div>}
      <form onSubmit={handleSubmit}>
        <input name="title" placeholder="عنوان الصفقة" onChange={e => setForm({...form, title: e.target.value})} required />
        <textarea name="description" placeholder="الوصف" onChange={e => setForm({...form, description: e.target.value})} />
        <input name="target_amount" type="number" placeholder="رأس المال المستهدف" onChange={e => setForm({...form, target_amount: e.target.value})} required />
        <select name="currency" onChange={e => setForm({...form, currency: e.target.value})}>
          <option value="USD">USD</option>
          <option value="SYP">SYP</option>
        </select>
        <input name="expected_profit_percentage" type="number" placeholder="نسبة الربح المتوقعة %" onChange={e => setForm({...form, expected_profit_percentage: e.target.value})} required />
        <input name="duration_days" type="number" placeholder="المدة (أيام)" onChange={e => setForm({...form, duration_days: e.target.value})} required />
        <button type="submit" disabled={loading}>{loading ? 'جاري الإنشاء...' : 'إنشاء صفقة'}</button>
      </form>
    </div>
  );
}
```
