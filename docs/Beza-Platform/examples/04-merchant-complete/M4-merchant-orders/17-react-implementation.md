# 17 - تطبيق React (React Implementation) - إدارة طلبات التاجر (Merchant Orders)

```jsx
// hooks/useOrders.js
import { useState, useEffect, useCallback } from 'react';
import { merchantApi } from '../services/api';

export function useOrders() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(false);
  const [filter, setFilter] = useState('');

  const loadOrders = useCallback(async () => {
    setLoading(true);
    try { const params = filter ? { status: filter } : {};
      const res = await merchantApi.getOrders(params); setOrders(res.data.data); }
    finally { setLoading(false); }
  }, [filter]);

  const updateStatus = useCallback(async (id, status) => {
    await merchantApi.updateOrderStatus(id, status);
    loadOrders();
  }, [loadOrders]);

  useEffect(() => { loadOrders(); }, [loadOrders]);
  return { orders, loading, filter, setFilter, updateStatus };
}

// pages/MerchantOrdersPage.jsx
export default function MerchantOrdersPage() {
  const { orders, loading, filter, setFilter, updateStatus } = useOrders();
  return (
    <div>
      <h1>الطلبات</h1>
      <select value={filter} onChange={e => setFilter(e.target.value)}>
        <option value="">الكل</option>
        <option value="pending">قيد الانتظار</option>
        <option value="processing">قيد المعالجة</option>
        <option value="shipped">تم الشحن</option>
        <option value="delivered">تم التوصيل</option>
      </select>
      {loading && <p>جاري التحميل...</p>}
      {orders.map(order => (
        <div key={order.id} className="order-card">
          <h3>طلب #{order.order_number}</h3>
          <p>الحالة: {order.status}</p>
          <p>المبلغ: {order.total_amount} {order.currency}</p>
          <button onClick={() => updateStatus(order.id, 'processing')}>معالجة</button>
          <button onClick={() => updateStatus(order.id, 'cancelled')}>إلغاء</button>
        </div>
      ))}
    </div>
  );
}
```
