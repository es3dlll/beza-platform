# 16 - تطبيق Flutter (Flutter Implementation) - التقارير (Admin Reports)

## Report Card Widget

```dart
class ReportCard extends StatelessWidget {
  final String title;
  final String value;
  final IconData icon;
  final Color color;

  const ReportCard({
    super.key, required this.title, required this.value,
    required this.icon, required this.color,
  });

  @override
  Widget build(BuildContext context) {
    return Card(
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Container(
              padding: const EdgeInsets.all(12),
              decoration: BoxDecoration(
                color: color.withOpacity(0.1),
                borderRadius: BorderRadius.circular(12),
              ),
              child: Icon(icon, color: color),
            ),
            const SizedBox(width: 16),
            Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(title, style: const TextStyle(color: Colors.grey, fontSize: 14)),
                Text(value, style: TextStyle(
                  fontSize: 20, fontWeight: FontWeight.bold, color: color,
                )),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class DailyReportScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('التقرير اليومي')),
      body: FutureBuilder(
        future: context.read<ReportRepository>().getDailyReport(),
        builder: (context, snapshot) {
          if (!snapshot.hasData) {
            return const Center(child: CircularProgressIndicator());
          }
          final report = snapshot.data as DailyReportData;
          return ListView(
            padding: const EdgeInsets.all(16),
            children: [
              ReportCard(
                title: 'المعاملات',
                value: '${report.totalTransactions}',
                icon: Icons.swap_horiz,
                color: Colors.blue,
              ),
              ReportCard(
                title: 'الحجم',
                value: '${report.totalVolume} SYP',
                icon: Icons.account_balance,
                color: Colors.green,
              ),
              ReportCard(
                title: 'الإيرادات',
                value: '${report.totalFees} SYP',
                icon: Icons.trending_up,
                color: Colors.orange,
              ),
              ReportCard(
                title: 'مستخدمون جدد',
                value: '${report.newUsers}',
                icon: Icons.person_add,
                color: Colors.purple,
              ),
            ],
          );
        },
      ),
    );
  }
}
```
