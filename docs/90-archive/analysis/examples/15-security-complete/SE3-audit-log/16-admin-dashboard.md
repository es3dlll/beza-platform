# 16 - لوحة تحكم التدقيق (Admin Dashboard)

## AuditLogs Component

```jsx
import React, { useState, useEffect } from 'react';
import api from '../services/api';

export default function AuditLogsPage() {
  const [logs, setLogs] = useState([]);
  const [filters, setFilters] = useState({
    event_type: '',
    user_id: '',
    from: '',
    to: '',
  });

  useEffect(() => {
    loadLogs();
  }, []);

  const loadLogs = async () => {
    const params = new URLSearchParams();
    Object.entries(filters).forEach(([key, value]) => {
      if (value) params.append(key, value);
    });
    const res = await api.get(`/admin/audit-logs?${params}`);
    setLogs(res.data.data);
  };

  const eventLabels = {
    login: 'تسجيل دخول',
    transfer_created: 'تحويل',
    pin_changed: 'تغيير PIN',
    kyc_verified: 'توثيق KYC',
    admin_action: 'إجراء مشرف',
  };

  return (
    <div>
      <h1>سجل التدقيق</h1>

      <div className="filters">
        <select value={filters.event_type} onChange={e => setFilters({...filters, event_type: e.target.value})}>
          <option value="">كل الأحداث</option>
          <option value="login">تسجيل دخول</option>
          <option value="transfer_created">تحويل</option>
          <option value="pin_changed">تغيير PIN</option>
          <option value="kyc_verified">توثيق KYC</option>
          <option value="admin_action">إجراء مشرف</option>
        </select>

        <input type="date" value={filters.from} onChange={e => setFilters({...filters, from: e.target.value})} />
        <input type="date" value={filters.to} onChange={e => setFilters({...filters, to: e.target.value})} />

        <button onClick={loadLogs}>بحث</button>
      </div>

      <table>
        <thead>
          <tr>
            <th>التاريخ</th>
            <th>الحدث</th>
            <th>المستخدم</th>
            <th>التفاصيل</th>
            <th>IP</th>
          </tr>
        </thead>
        <tbody>
          {logs.map(log => (
            <tr key={log.id}>
              <td>{new Date(log.created_at).toLocaleString('ar')}</td>
              <td>{eventLabels[log.event_type] || log.event_type}</td>
              <td>{log.user?.name || '-'}</td>
              <td>{JSON.stringify(log.data)}</td>
              <td>{log.ip}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```
