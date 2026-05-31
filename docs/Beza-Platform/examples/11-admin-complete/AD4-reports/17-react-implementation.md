# 17 - تطبيق React (React Implementation) - التقارير (Admin Reports)

## DailyReport Page

```jsx
// src/pages/admin/DailyReport.jsx
import React, { useState } from 'react';
import { useQuery } from '@tanstack/react-query';
import { adminApi } from '../../services/api';
import ReportCard from '../../components/admin/reports/ReportCard';
import TransactionBreakdownChart from '../../components/admin/reports/TransactionBreakdownChart';

export default function DailyReport() {
  const [date, setDate] = useState(new Date().toISOString().split('T')[0]);

  const { data, isLoading } = useQuery({
    queryKey: ['daily-report', date],
    queryFn: () => adminApi.getDailyReport(date),
  });

  if (isLoading) return <div>جاري التحميل...</div>;

  const report = data?.data;

  return (
    <div className="report-page">
      <h1>التقرير اليومي</h1>

      <div className="date-picker">
        <input
          type="date"
          value={date}
          onChange={(e) => setDate(e.target.value)}
          max={new Date().toISOString().split('T')[0]}
        />
      </div>

      <div className="report-cards">
        <ReportCard title="المعاملات" value={report.total_transactions} icon="exchange" color="#3b82f6" />
        <ReportCard title="حجم المعاملات" value={`${(report.total_volume / 1000000).toFixed(2)}M SYP`} icon="chart" color="#10b981" />
        <ReportCard title="الإيرادات" value={`${(report.total_fees / 1000).toFixed(2)}K SYP`} icon="trending-up" color="#f59e0b" />
        <ReportCard title="مستخدمون جدد" value={report.new_users} icon="users" color="#8b5cf6" />
        <ReportCard title="نشطون" value={report.active_users} icon="activity" color="#ec4899" />
        <ReportCard title="متوسط المعاملة" value={`${report.avg_transaction} SYP`} icon="calculator" color="#14b8a6" />
      </div>

      <div className="report-charts">
        <TransactionBreakdownChart data={report.transaction_breakdown} />
      </div>

      {report.growth_percent !== null && (
        <div className={`growth-badge ${report.growth_percent >= 0 ? 'positive' : 'negative'}`}>
          {report.growth_percent >= 0 ? '📈' : '📉'} {Math.abs(report.growth_percent)}% عن أمس
        </div>
      )}
    </div>
  );
}
```

## API Service

```javascript
export const adminApi = {
  getDailyReport: (date) => api.get('/admin/reports/daily', { params: { date } }),
  getMonthlyReport: (year, month) => api.get('/admin/reports/monthly', { params: { year, month } }),
  getFinancialReport: (from, to) => api.get('/admin/reports/financial', { params: { from, to } }),
};
```
