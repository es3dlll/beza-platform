# 16 - تطبيق Flutter (Flutter Implementation) - تقديم ومراجعة وثائق KYC

## KycScreen

```dart
class KycScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('التحقق من الهوية')),
      body: BlocProvider(
        create: (_) => KycBloc(
          repository: KycRepository(dataSource: KycRemoteDataSource()),
        ),
        child: KycView(),
      ),
    );
  }
}

class KycView extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocBuilder<KycBloc, KycState>(
      builder: (context, state) {
        if (state is KycStatusLoaded) {
          if (state.status == 'verified') {
            return _buildVerified();
          }
          if (state.status == 'pending') {
            return _buildPending();
          }
          if (state.status == 'rejected') {
            return _buildRejected(state.reason);
          }
          return _buildSubmitForm();
        }
        return Center(child: CircularProgressIndicator());
      },
    );
  }

  Widget _buildSubmitForm() {
    return Padding(
      padding: EdgeInsets.all(16),
      child: Column(
        children: [
          _buildFilePicker('صورة الهوية الأمامية'),
          _buildFilePicker('صورة الهوية الخلفية'),
          _buildFilePicker('صورة شخصية'),
          _buildFilePicker('إثبات العنوان'),
          SizedBox(height: 24),
          ElevatedButton(
            onPressed: () {},
            child: Text('تقديم الطلب'),
          ),
        ],
      ),
    );
  }

  Widget _buildVerified() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.verified, size: 80, color: Colors.green),
          Text('تم التحقق من هويتك', style: TextStyle(fontSize: 20)),
        ],
      ),
    );
  }

  Widget _buildPending() {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          CircularProgressIndicator(),
          SizedBox(height: 16),
          Text('المستندات قيد المراجعة'),
        ],
      ),
    );
  }

  Widget _buildRejected(String? reason) {
    return Center(
      child: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        children: [
          Icon(Icons.cancel, size: 80, color: Colors.red),
          Text('لم يتم التحقق', style: TextStyle(fontSize: 20)),
          if (reason != null) Text('السبب: $reason'),
          ElevatedButton(
            onPressed: () {},
            child: Text('إعادة المحاولة'),
          ),
        ],
      ),
    );
  }
}
```
