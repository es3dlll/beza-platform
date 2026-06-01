# 17 - تطبيق React (React Implementation) - المشاركة في صفقة

## DealInvestPage

```jsx
import React, { useState } from 'react';
import { useParams } from 'react-router-dom';
import api from '../../services/api';

export default function DealInvestPage() {
  const { id } = useParams();
  const [amount, setAmount] = useState('');
  const [loading, setLoading] = useState(false);
  const [deal, setDeal] = useState(null);
  const [error, setError] = useState(null);
  const [success, setSuccess] = useState(null);

  React.useEffect(() => {
    api.get(`/deals/${id}`).then(res => setDeal(res.data.data));
  }, [id]);

  const handleInvest = async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await api.post(`/deals/${id}/invest`, { amount: Number(amount) });
      setSuccess(res.data);
    } catch (err) {
      setError(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setLoading(false);
    }
  };

  if (!deal) return <div>جاري التحميل...</div>;

  return (
    <div className="deal-invest">
      <h1>{deal.title}</h1>
      <div className="deal-stats">
        <p>المبلغ المتبقي: {deal.remaining_amount} {deal.currency}</p>
        <p>الربح المتوقع: {deal.expected_profit_percentage}%</p>
        <div className="progress-bar">
          <div style={{width: `${deal.progress_percentage}%`}} />
        </div>
      </div>

      {error && <div className="alert-error">{error}</div>}
      {success && <div className="alert-success">تم الاستثمار بنجاح!</div>}

      <input
        type="number"
        value={amount}
        onChange={e => setAmount(e.target.value)}
        placeholder="المبلغ"
        min="10"
      />
      <button onClick={handleInvest} disabled={loading}>
        {loading ? 'جاري...' : 'استثمر'}
      </button>
    </div>
  );
}
```
