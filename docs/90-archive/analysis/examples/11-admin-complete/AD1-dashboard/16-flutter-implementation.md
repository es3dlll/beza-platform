# 16 - تطبيق Flutter (Flutter Implementation) - لوحة تحكم المشرف (Admin Dashboard)

ملاحظة: لوحة التحكم مخصصة للمشرف وتُبنى في React Admin. لكن يمكن عرض بعض الإحصائيات في Flutter:

## Dashboard Stats Widget (لمحة سريعة)

```dart
// lib/features/admin/presentation/widgets/admin_stats_card.dart
class AdminStatsCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;

  const AdminStatsCard({
    super.key,
    required this.title,
    required this.value,
    required this.icon,
    required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      elevation: 2,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Icon(icon, color: color, size: 32),
            const SizedBox(height: 8),
            Text(value, style: TextStyle(
              fontSize: 24, fontWeight: FontWeight.bold, color: color,
            )),
            Text(title, style: const TextStyle(
              color: Colors.grey, fontSize: 14,
            )),
          ],
        ),
      ),
    );
  }
}
```

```dart
// lib/features/admin/presentation/widgets/stats_grid.dart
class StatsGrid extends StatelessWidget {
  final DashboardStats stats;

  const StatsGrid({super.key, required this.stats});

  @override
  Widget build(BuildContext context) {
    return GridView.count(
      crossAxisCount: 2,
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      children: [
        AdminStatsCard(
          title: 'المستخدمين',
          value: stats.totalUsers.toString(),
          icon: Icons.people,
          color: Colors.blue,
        ),
        AdminStatsCard(
          title: 'النشطون اليوم',
          value: stats.activeUsers.toString(),
          icon: Icons.person_pin,
          color: Colors.green,
        ),
        AdminStatsCard(
          title: 'المعاملات',
          value: stats.totalTransactions.toString(),
          icon: Icons.swap_horiz,
          color: Colors.orange,
        ),
        AdminStatsCard(
          title: 'الحجم',
          value: '${stats.transactionVolume} SYP',
          icon: Icons.account_balance,
          color: Colors.purple,
        ),
      ],
    );
  }
}
```

## Data Source

```dart
// lib/features/admin/data/datasources/dashboard_remote_datasource.dart
class DashboardRemoteDataSource {
  final http.Client client;
  final String baseUrl;
  final TokenService _tokenService;

  DashboardRemoteDataSource({
    required this.baseUrl,
    required this.client,
    required TokenService tokenService,
  }) : _tokenService = tokenService;

  Future<String> _getToken() async => (await _tokenService.getValidToken()) ?? '';

  Future<Map<String, dynamic>> getStats({String period = '30d'}) async {
    final token = await _getToken();
    final response = await client.get(
      Uri.parse('$baseUrl/api/v1/admin/dashboard/stats?period=$period'),
      headers: {
        'Accept': 'application/json',
        'Authorization': 'Bearer $token',
      },
    );

    if (response.statusCode == 200) {
      return jsonDecode(response.body)['data'] as Map<String, dynamic>;
    }
    throw Exception('Failed to load dashboard stats');
  }
}
```

## Auto Refresh

```dart
// استخدام Timer.periodic لإعادة التحميل كل 30 ثانية
Timer.periodic(const Duration(seconds: 30), (_) {
  context.read<DashboardBloc>().add(RefreshDashboard());
});
```
