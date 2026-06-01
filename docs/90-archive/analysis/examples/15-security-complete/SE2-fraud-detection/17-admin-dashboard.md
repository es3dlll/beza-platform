# 17 - لوحة تحكم الاحتيال (Admin Dashboard)

## Fraud Dashboard Component

```jsx
import React, { useState, useEffect } from 'react';
import api from '../services/api';

export default function FraudDashboard() {
  const [flagged, setFlagged] = useState([]);
  const [stats, setStats] = useState({});

  useEffect(() => {
    loadFlagged();
    loadStats();
  }, []);

  const loadFlagged = async () => {
    const res = await api.get('/admin/fraud/report');
    setFlagged(res.data.data);
  };

  const loadStats = async () => {
    const res = await api.get('/admin/fraud/stats');
    setStats(res.data.data);
  };

  const approveTransaction = async (id) => {
    await api.post(`/admin/fraud/${id}/approve`);
    loadFlagged();
    loadStats();
  };

  const rejectTransaction = async (id) => {
    const reason = prompt('سبب الرفض:');
    if (!reason) return;
    await api.post(`/admin/fraud/${id}/reject`, { reason });
    loadFlagged();
    loadStats();
  };

  return (
    <div className="fraud-dashboard">
      <h1>لوحة تحكم الاحتيال</h1>

      <div className="stats-grid">
        <div className="stat-card pending">
          <h3>{stats.pending}</h3>
          <p>قيد المراجعة</p>
        </div>
        <div className="stat-card approved">
          <h3>{stats.approved}</h3>
          <p>تمت الموافقة</p>
        </div>
        <div className="stat-card rejected">
          <h3>{stats.rejected}</h3>
          <p>مرفوض</p>
        </div>
        <div className="stat-card high-risk">
          <h3>{stats.high_risk}</h3>
          <p>مخاطرة عالية</p>
        </div>
      </div>

      <table className="fraud-table">
        <thead>
          <tr>
            <th>رقم المعاملة</th>
            <th>المستخدم</th>
            <th>المبلغ</th>
            <th>درجة الخطورة</th>
            <th>القواعد المشغلة</th>
            <th>الحالة</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          {flagged.map(item => (
            <tr key={item.id}>
              <td>{item.transaction?.reference_number || '-'}</td>
              <td>{item.user?.name}</td>
              <td>{item.amount} {item.currency}</td>
              <td>
                <span className={`risk-badge risk-${item.risk_level}`}>
                  {item.risk_score}
                </span>
              </td>
              <td>
                {item.triggered_rules.map((rule, i) => (
                  <span key={i} className="rule-tag">{rule.message}</span>
                ))}
              </td>
              <td>{item.status}</td>
              <td>
                <button onClick={() => approveTransaction(item.id)}>موافقة</button>
                <button onClick={() => rejectTransaction(item.id)}>رفض</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```
