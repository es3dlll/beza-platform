# 17 - تطبيق React (React Implementation) - إتمام الصفقة + توزيع الأرباح

## AdminDealCompletePage

```jsx
import React, { useState } from 'react';
import { useParams } from 'react-router-dom';
import api from '../../services/api';

export default function AdminDealCompletePage() {
  const { id } = useParams();
  const [profitActual, setProfitActual] = useState('');
  const [loading, setLoading] = useState(false);
  const [result, setResult] = useState(null);
  const [error, setError] = useState(null);

  const handleComplete = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await api.post(`/admin/deals/${id}/complete`, {
        profit_actual: Number(profitActual),
      });
      setResult(res.data.data);
    } catch (err) {
      setError(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="admin-deal-complete">
      <h1>إتمام الصفقة</h1>
      <div className="form-group">
        <label>نسبة الربح الفعلية (%)</label>
        <input
          type="number"
          value={profitActual}
          onChange={e => setProfitActual(e.target.value)}
          step="0.01"
        />
      </div>
      <button onClick={handleComplete} disabled={loading}>
        {loading ? 'جاري التوزيع...' : 'إتمام وتوزيع الأرباح'}
      </button>
      {error && <div className="alert-error">{error}</div>}
      {result && (
        <div className="result">
          <p>إجمالي الأرباح: {result.total_profit} USD</p>
          <p>عدد المستثمرين: {result.investors_count}</p>
        </div>
      )}
    </div>
  );
}
```
