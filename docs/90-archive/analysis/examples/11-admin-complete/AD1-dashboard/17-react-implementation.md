# 17 - تطبيق React (React Implementation) - لوحة تحكم المشرف (Admin Dashboard)

## AdminDashboard Page

```jsx
// src/pages/AdminDashboard.jsx
import React from 'react';
import { useQuery } from '@tanstack/react-query';
import { adminApi } from '../services/api';
import StatCard from '../components/AdminDashboard/StatCard';
import RevenueChart from '../components/AdminDashboard/RevenueChart';
import TransactionVolumeChart from '../components/AdminDashboard/TransactionVolumeChart';
import UserGrowthChart from '../components/AdminDashboard/UserGrowthChart';
import TopMerchantsTable from '../components/AdminDashboard/TopMerchantsTable';

export default function AdminDashboard() {
  const { data, isLoading, error } = useQuery({
    queryKey: ['dashboard-stats'],
    queryFn: () => adminApi.getDashboardStats(),
    refetchInterval: 30000,
  });

  if (isLoading) return <div className="loading">جاري التحميل...</div>;
  if (error) return <div className="error">فشل تحميل البيانات</div>;

  const { summary, charts, top_merchants } = data.data;

  return (
    <div className="admin-dashboard">
      <header className="dashboard-header">
        <h1>لوحة التحكم</h1>
        <span className="cache-info">
          آخر تحديث: {data.meta?.cached_at ? new Date(data.meta.cached_at).toLocaleTimeString('ar') : '—'}
        </span>
      </header>

      <section className="stats-grid">
        <StatCard title="إجمالي المستخدمين" value={summary.total_users} icon="users" color="#3b82f6" />
        <StatCard title="النشطون اليوم" value={summary.active_users} icon="activity" color="#10b981" />
        <StatCard title="المعاملات" value={summary.total_transactions} icon="exchange" color="#f59e0b" />
        <StatCard title="حجم المعاملات" value={`${(summary.transaction_volume / 1000000).toFixed(1)}M SYP`} icon="dollar" color="#8b5cf6" />
        <StatCard title="التجار" value={summary.merchants_count} icon="store" color="#ec4899" />
        <StatCard title="الوكلاء" value={summary.agents_count} icon="users" color="#14b8a6" />
        <StatCard title="أرصدة المحافظ" value={`${(summary.total_wallets_balance / 1000000).toFixed(1)}M SYP`} icon="wallet" color="#6366f1" />
        <StatCard title="الإيرادات" value={`${(summary.total_fees / 1000).toFixed(1)}K SYP`} icon="trending-up" color="#ef4444" />
      </section>

      <section className="charts-grid">
        <RevenueChart data={charts.revenue} />
        <TransactionVolumeChart data={charts.volume} />
        <UserGrowthChart data={charts.user_growth} />
      </section>

      <section className="top-merchants">
        <h2>أفضل 5 تجار</h2>
        <TopMerchantsTable merchants={top_merchants} />
      </section>
    </div>
  );
}
```

## API Service

```javascript
// src/services/api.js
import axios from 'axios';

const adminApi = {
  getDashboardStats: async (period = '30d') => {
    const response = await api.get('/admin/dashboard/stats', {
      params: { period },
    });
    return response.data;
  },
  refreshDashboard: async () => {
    const response = await api.post('/admin/dashboard/refresh');
    return response.data;
  },
};
```

## هوك مخصص (Custom Hook)

```javascript
// src/hooks/useDashboardStats.js
import { useQuery, useMutation } from '@tanstack/react-query';
import { adminApi } from '../services/api';

export function useDashboardStats(period = '30d') {
  const statsQuery = useQuery({
    queryKey: ['dashboard-stats', period],
    queryFn: () => adminApi.getDashboardStats(period),
    refetchInterval: 30000,
    staleTime: 25000,
  });

  const refreshMutation = useMutation({
    mutationFn: adminApi.refreshDashboard,
    onSuccess: () => statsQuery.refetch(),
  });

  return {
    stats: statsQuery.data,
    isLoading: statsQuery.isLoading,
    error: statsQuery.error,
    refresh: refreshMutation.mutate,
    isRefreshing: refreshMutation.isPending,
    lastUpdated: statsQuery.data?.meta?.cached_at,
  };
}
```
