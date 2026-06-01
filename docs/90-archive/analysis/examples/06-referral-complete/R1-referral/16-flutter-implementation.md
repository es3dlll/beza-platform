# 16 - تطبيق Flutter (Flutter Implementation) - دعوة صديق + برنامج ولاء

## ReferralScreen

```dart
class ReferralScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('دعوة صديق')),
      body: BlocProvider(
        create: (_) => ReferralBloc(
          repository: ReferralRepository(dataSource: ReferralRemoteDataSource()),
        ),
        child: ReferralView(),
      ),
    );
  }
}

class ReferralView extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocBuilder<ReferralBloc, ReferralState>(
      builder: (context, state) {
        if (state is ReferralLoaded) {
          return Padding(
            padding: EdgeInsets.all(24),
            child: Column(
              children: [
                Icon(Icons.share, size: 64, color: Colors.blue),
                SizedBox(height: 16),
                Text('شارك كود الإحالة', style: TextStyle(fontSize: 20)),
                SizedBox(height: 8),
                Container(
                  padding: EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: Colors.grey[200],
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: SelectableText(
                    state.code,
                    style: TextStyle(fontSize: 24, fontWeight: FontWeight.bold),
                  ),
                ),
                SizedBox(height: 24),
                ElevatedButton.icon(
                  icon: Icon(Icons.share),
                  label: Text('مشاركة الرابط'),
                  onPressed: () => _shareCode(state.code),
                ),
                SizedBox(height: 24),
                Text('مكافآتي: \$${state.totalRewards}'),
              ],
            ),
          );
        }
        return Center(child: CircularProgressIndicator());
      },
    );
  }

  void _shareCode(String code) {
    Share.share('انضم إلى Beza باستخدام كود الإحالة الخاص بي: $code');
  }
}
```
