# 17 - تطبيق React (React Implementation) - إلغاء صفقة + استرجاع

## AdminDealCancelPage

```jsx
import React, { useState } from 'react';
import { useParams } from 'react-router-dom';
import api from '../../services/api';

export default function AdminDealCancelPage() {
  const { id } = useParams();
  const [reason, setReason] = useState('');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState(null);
  const [error, setError] = useState(null);

  const handleCancel = async () => {
    if (!confirm('هل أنت متأكد من إلغاء الصفقة؟ سيتم استرجاع مبالغ جميع المستثمرين.')) return;

    setLoading(true);
    setError(null);
    try {
      const res = await api.post(`/admin/deals/${id}/cancel`, { reason });
      setResult(res.data.data);
    } catch (err) {
      setError(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="admin-deal-cancel">
      <h1>إلغاء الصفقة</h1>
      <div className="warning-box">
        تحذير: سيتم استرجاع مبالغ جميع المستثمرين النشطين
      </div>
      <div className="form-group">
        <label>سبب الإلغاء</label>
        <textarea
          value={reason}
          onChange={e => setReason(e.target.value)}
          rows={4}
          placeholder="يرجى توضيح سبب الإلغاء"
        />
      </div>
      <button
        onClick={handleCancel}
        disabled={loading || reason.length < 10}
        className="btn-danger"
      >
        {loading ? 'جاري الإلغاء...' : 'إلغاء الصفقة واسترجاع المبالغ'}
      </button>
      {error && <div className="alert-error">{error}</div>}
      {result && (
        <div className="result">
          <p>تم استرجاع مبلغ {result.total_refunded} USD</p>
          <p>عدد المستثمرين: {result.investors_count}</p>
        </div>
      )}
    </div>
  );
}
```
