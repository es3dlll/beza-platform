# 17 - تطبيق React (React Implementation) - تسوية مدفوعات التاجر (Merchant Settlement)

```jsx
// hooks/useSettlement.js
import { useState, useCallback } from 'react';
import { merchantApi } from '../services/api';

export function useSettlement() {
  const [state, setState] = useState({ loading: false, settlement: null, error: null, calculation: null });
  const requestSettlement = useCallback(async (currency) => {
    setState(prev => ({ ...prev, loading: true, error: null }));
    try {
      const res = await merchantApi.requestSettlement({ currency });
      setState({ loading: false, settlement: res.data.data, error: null, calculation: null });
      return res.data.data;
    } catch (err) {
      setState(prev => ({ ...prev, loading: false, error: err.response?.data?.message || 'فشل التسوية' }));
    }
  }, []);
  const calculate = useCallback(async (currency) => {
    const res = await merchantApi.calculateSettlement({ currency });
    setState(prev => ({ ...prev, calculation: res.data.data }));
  }, []);
  return { ...state, requestSettlement, calculate };
}

// pages/SettlementPage.jsx
export default function SettlementPage() {
  const { loading, settlement, error, calculation, requestSettlement, calculate } = useSettlement();
  const [currency, setCurrency] = useState('USD');

  return (
    <div className="settlement-page">
      <h1>التسوية البنكية</h1>
      {error && <div className="error">{error}</div>}
      {settlement && <div className="success">تم تقديم طلب التسوية بنجاح</div>}
      <div className="calculation" onClick={() => calculate(currency)}>
        {calculation && (
          <div>
            <p>المبيعات: {calculation.total_sales} {calculation.currency}</p>
            <p>رسوم Beza: -{calculation.beza_fee}</p>
            <p>المرتجعات: -{calculation.refunds}</p>
            <p>رسوم تحويل: -{calculation.transfer_fee}</p>
            <p><strong>الصافي: {calculation.net_amount} {calculation.currency}</strong></p>
          </div>
        )}
      </div>
      <select value={currency} onChange={e => setCurrency(e.target.value)}>
        <option value="USD">USD</option>
        <option value="SYP">SYP</option>
      </select>
      <button onClick={() => requestSettlement(currency)} disabled={loading}>
        {loading ? 'جاري...' : 'طلب تسوية'}
      </button>
    </div>
  );
}
```
