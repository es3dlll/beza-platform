# 6. لوحة تحكم المشرف العام (Super Admin)

## 6.1 هيكل React Admin

```
admin-dashboard/
├── src/
│   ├── components/
│   │   ├── Layout/
│   │   │   ├── AdminLayout.jsx
│   │   │   ├── Sidebar.jsx
│   │   │   └── Header.jsx
│   │   ├── Charts/
│   │   │   ├── RevenueChart.jsx
│   │   │   ├── TransactionVolume.jsx
│   │   │   └── UserGrowth.jsx
│   │   ├── Tables/
│   │   │   ├── DataTable.jsx
│   │   │   ├── UserTable.jsx
│   │   │   └── TransactionTable.jsx
│   │   └── Common/
│   │       ├── StatCard.jsx
│   │       └── StatusBadge.jsx
│   ├── pages/
│   │   ├── Dashboard.jsx
│   │   ├── Users/
│   │   │   ├── UserList.jsx
│   │   │   ├── UserDetails.jsx
│   │   │   └── KycReview.jsx
│   │   ├── Merchants/
│   │   │   ├── MerchantList.jsx
│   │   │   ├── MerchantDetails.jsx
│   │   │   └── MerchantApplications.jsx
│   │   ├── Agents/
│   │   │   ├── AgentList.jsx
│   │   │   ├── AgentDetails.jsx
│   │   │   └── AgentApplications.jsx
│   │   ├── Transactions/
│   │   │   ├── TransactionList.jsx
│   │   │   ├── TransactionDetails.jsx
│   │   │   └── Disputes.jsx
│   │   ├── Reports/
│   │   │   ├── DailyReport.jsx
│   │   │   ├── MonthlyReport.jsx
│   │   │   └── FinancialReport.jsx
│   │   ├── Settings/
│   │   │   ├── GeneralSettings.jsx
│   │   │   ├── FeeSettings.jsx
│   │   │   └── RateSettings.jsx
│   │   └── Support/
│   │       ├── TicketList.jsx
│   │       └── TicketDetails.jsx
│   ├── services/
│   │   └── api.js
│   ├── store/
│   │   └── index.js (Zustand)
│   └── App.jsx
```

## 6.2 صفحة لوحة المعلومات (Dashboard)

```jsx
import { useQuery } from '@tanstack/react-query';
import { StatCard, RevenueChart, TransactionVolume, UserGrowth } from '../components';
import api from '../services/api';

export default function AdminDashboard() {
  const { data: stats, isLoading } = useQuery({
    queryKey: ['admin-stats'],
    queryFn: () => api.get('/admin/dashboard/stats').then(res => res.data),
    refetchInterval: 30000,
  });

  if (isLoading) return <div className="flex justify-center p-10"><div className="spinner"></div></div>;

  return (
    <div className="p-6 space-y-6">
      <h1 className="text-3xl font-bold">لوحة التحكم</h1>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-4 gap-6">
        <StatCard title="إجمالي المستخدمين" value={stats.total_users} change={stats.user_growth} icon="👥" />
        <StatCard title="المستخدمين النشطين اليوم" value={stats.daily_active_users} change={stats.dau_change} icon="📱" />
        <StatCard title="حجم المعاملات (اليوم)" value={`$${stats.daily_volume.toLocaleString()}`} change={stats.volume_change} icon="💰" />
        <StatCard title="إجمالي الأصول المدارة" value={`$${stats.total_assets.toLocaleString()}`} change={stats.assets_change} icon="🏦" />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <RevenueChart data={stats.revenue_chart} />
        <TransactionVolume data={stats.volume_chart} />
      </div>

      <div className="grid grid-cols-1 lg:grid-cols-2 gap-6">
        <UserGrowth data={stats.user_growth_chart} />
        <div className="bg-white rounded-2xl shadow p-6">
          <h3 className="text-xl font-semibold mb-4">أعلى 5 تجار</h3>
          <table className="min-w-full">
            <thead>
              <tr className="border-b">
                <th className="text-right py-2">التاجر</th>
                <th className="text-right py-2">حجم المعاملات</th>
                <th className="text-right py-2">العمولات</th>
              </tr>
            </thead>
            <tbody>
              {stats.top_merchants?.map(merchant => (
                <tr key={merchant.id} className="border-b">
                  <td className="py-2">{merchant.business_name}</td>
                  <td className="py-2">${merchant.volume.toLocaleString()}</td>
                  <td className="py-2">${merchant.fees.toLocaleString()}</td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
}
```
