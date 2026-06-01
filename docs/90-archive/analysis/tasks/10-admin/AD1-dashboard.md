# AD1 - لوحة تحكم المشرف - الإحصائيات

## الوصف
عرض مؤشرات الأداء الرئيسية للمنصة.

## المخرجات
| الحقل | المصدر |
|-------|--------|
| total_users | COUNT(users) |
| daily_active_users | users.last_login_at = today |
| daily_volume | SUM(transactions.amount) WHERE today |
| total_assets | SUM(wallets.balance) |
| user_growth | مقارنة بالشهر الماضي |
| revenue_chart | إيرادات آخر 30 يوماً |
| volume_chart | حجم المعاملات آخر 30 يوماً |
| user_growth_chart | نمو المستخدمين آخر 30 يوماً |
| top_merchants | أعلى 5 تجار حسب حجم المعاملات |

## API Endpoint
`GET /api/v1/admin/dashboard/stats`

## واجهات المستخدم
- React Admin: AdminDashboard

## مكونات الواجهة
- StatCard × 4
- RevenueChart (Line Chart)
- TransactionVolume (Bar Chart)
- UserGrowth (Area Chart)
- Top Merchants Table

## التحديث
- Auto-refresh كل 30 ثانية (refetchInterval: 30000)

## اختبارات
- عرض الإحصائيات ← 200
- عرض بدون صلاحيات ← 403
