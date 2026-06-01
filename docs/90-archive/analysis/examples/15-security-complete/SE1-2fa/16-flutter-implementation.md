# 16 - تطبيق Flutter (Flutter Implementation) - فهرس - المصادقة الثنائية (2FA)

## TwoFactorScreen

```dart
class TwoFactorScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return BlocProvider(
      create: (_) => TwoFactorBloc(
        twoFactorRepository: context.read<TwoFactorRepository>(),
      ),
      child: Scaffold(
        appBar: AppBar(title: Text('المصادقة الثنائية')),
        body: Padding(
          padding: EdgeInsets.all(16),
          child: BlocConsumer<TwoFactorBloc, TwoFactorState>(
            listener: (context, state) {
              if (state is TwoFactorEnabled) {
                _showRecoveryCodes(context, state.recoveryCodes);
              }
            },
            builder: (context, state) {
              if (state is TwoFactorInitial) {
                return _EnableForm();
              }
              if (state is TwoFactorLoading) {
                return Center(child: CircularProgressIndicator());
              }
              if (state is QrCodeReady) {
                return _QrCodeDisplay(
                  secret: state.secret,
                  qrCodeSvg: state.qrCodeSvg,
                );
              }
              if (state is TwoFactorEnabled) {
                return _EnabledStatus();
              }
              if (state is TwoFactorError) {
                return _ErrorWidget(state.message);
              }
              return SizedBox();
            },
          ),
        ),
      ),
    );
  }
}
```

## DTO لتفعيل 2FA

```dart
class EnableTwoFactorResponse {
  final String secret;
  final String qrCodeSvg;

  EnableTwoFactorResponse({required this.secret, required this.qrCodeSvg});

  factory EnableTwoFactorResponse.fromJson(Map<String, dynamic> json) {
    return EnableTwoFactorResponse(
      secret: json['data']['secret'],
      qrCodeSvg: json['data']['qr_code_svg'],
    );
  }
}
```

## إدخال رمز 2FA في المعاملات

```dart
class TransferForm extends StatefulWidget {
  @override
  _TransferFormState createState() => _TransferFormState();
}

class _TransferFormState extends State<TransferForm> {
  final _twoFactorController = TextEditingController();

  void _submitTransfer() async {
    try {
      final response = await apiClient.post('/transfer', {
        'to_phone': _phoneController.text,
        'amount': double.parse(_amountController.text),
        'currency': _currency,
        'pin': _pinController.text,
        'two_factor_code': _twoFactorController.text,
      });
      // نجاح
    } on ApiException catch (e) {
      if (e.statusCode == 402 && e.data['requires_2fa'] == true) {
        // طلب 2FA
        _showTwoFactorDialog();
      }
    }
  }
}
```
