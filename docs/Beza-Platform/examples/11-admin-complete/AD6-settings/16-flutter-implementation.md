# 16 - تطبيق Flutter (Flutter Implementation) - إعدادات النظام (Admin Settings)

## Admin Settings Screen

```dart
class AdminSettingsScreen extends StatelessWidget {
  const AdminSettingsScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('إعدادات المنصة')),
      body: BlocProvider(
        create: (_) => SettingsBloc(
          repository: SettingsRepository(
            dataSource: AdminRemoteDataSource(
              baseUrl: 'http://localhost:8000',
              client: http.Client(),
              tokenService: TokenService(FlutterSecureStorage()),
            ),
          ),
        )..load(),
        child: BlocBuilder<SettingsBloc, SettingsState>(
          builder: (context, state) {
            if (state is SettingsLoading) {
              return const Center(child: CircularProgressIndicator());
            }
            if (state is SettingsError) {
              return Center(child: Text(state.message));
            }
            if (state is SettingsLoaded) {
              return ListView(
                padding: const EdgeInsets.all(16),
                children: [
                  _SectionHeader('الإعدادات العامة'),
                  SwitchListTile(
                    title: const Text('وضع الصيانة'),
                    subtitle: const Text('تفعيل تعطيل المنصة للمستخدمين'),
                    value: state.general.maintenanceMode,
                    onChanged: (v) => context.read<SettingsBloc>().updateGeneral(
                      maintenanceMode: v,
                    ),
                  ),
                  SwitchListTile(
                    title: const Text('طلب KYC'),
                    subtitle: const Text('اشتراط التحقق من الهوية'),
                    value: state.general.kycRequired,
                    onChanged: (v) => context.read<SettingsBloc>().updateGeneral(
                      kycRequired: v,
                    ),
                  ),

                  _SectionHeader('الرسوم'),
                  _FeeTile(label: 'تحويل', value: state.fees.transfer.toString()),
                  _FeeTile(label: 'صرافة', value: state.fees.exchange.toString()),
                  _FeeTile(label: 'بطاقة', value: state.fees.cardLoad.toString()),

                  _SectionHeader('أسعار الصرف'),
                  _InfoTile(label: 'سعر SYP/USD', value: state.exchange.rate.toString()),
                  _InfoTile(label: 'الهامش', value: '${state.exchange.margin}%'),
                ],
              );
            }
            return const SizedBox();
          },
        ),
      ),
    );
  }
}

class _SectionHeader extends StatelessWidget {
  final String title;
  const _SectionHeader(this.title);

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Text(title, style: const TextStyle(
        fontSize: 18, fontWeight: FontWeight.bold, color: Colors.blue,
      )),
    );
  }
}

class _FeeTile extends StatelessWidget {
  final String label;
  final String value;
  const _FeeTile({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return ListTile(
      title: Text(label),
      trailing: Text('$value%', style: const TextStyle(fontWeight: FontWeight.bold)),
    );
  }
}
```
