# 16 - تطبيق Flutter (Flutter Implementation) — رمز التحقق (OTP)

## طبقة البيانات (Data Layer)

```dart
// domain/repositories/i_otp_repository.dart
abstract class IOtpRepository {
  Future<void> requestOtp({required String phone});
  Future<void> verifyOtp({required String phone, required String code});
}
```

```dart
// data/repositories/otp_repository.dart
class OtpRepository implements IOtpRepository {
  final http.Client client;
  final String baseUrl;

  @override
  Future<void> requestOtp({required String phone}) async {
    final response = await client.post(
      Uri.parse('$baseUrl/api/v1/auth/request-otp'),
      body: jsonEncode({'phone': phone}),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
    );
    if (response.statusCode != 200) {
      throw Exception(jsonDecode(response.body)['message']);
    }
  }

  @override
  Future<void> verifyOtp({required String phone, required String code}) async {
    final response = await client.post(
      Uri.parse('$baseUrl/api/v1/auth/verify-otp'),
      body: jsonEncode({'phone': phone, 'otp': code}),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
    );
    if (response.statusCode != 200) {
      final body = jsonDecode(response.body);
      throw OtpException(body['message']);
    }
  }
}
```

## طبقة العرض (Presentation Layer)

```dart
// presentation/bloc/otp_bloc.dart
class OtpBloc extends Bloc<OtpEvent, OtpState> {
  final IOtpRepository repository;

  Future<void> _onRequestOtp(RequestOtp event, Emitter emit) async {
    emit(OtpLoading());
    try {
      await repository.requestOtp(phone: event.phone);
      emit(OtpCodeSent());
    } catch (e) {
      emit(OtpError(e.toString()));
    }
  }

  Future<void> _onVerifyOtp(VerifyOtp event, Emitter emit) async {
    emit(OtpLoading());
    try {
      await repository.verifyOtp(phone: event.phone, code: event.code);
      emit(OtpVerified());
    } on OtpException catch (e) {
      emit(OtpError(e.message));
    }
  }
}
```

## UI

```dart
class OtpVerificationScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('تأكيد رقم الهاتف')),
      body: BlocConsumer<OtpBloc, OtpState>(
        listener: (context, state) {
          if (state is OtpVerified) Navigator.pushReplacementNamed(context, '/home');
        },
        builder: (context, state) {
          return Padding(
            padding: EdgeInsets.all(24),
            child: Column(children: [
              Text('أدخل رمز التحقق المرسل إلى'),
              Text('0999123456', style: TextStyle(fontWeight: FontWeight.bold)),
              SizedBox(height: 32),
              PinField(
                length: 6,
                onCompleted: (code) => context.read<OtpBloc>().add(VerifyOtp(phone: '0999123456', code: code)),
              ),
              SizedBox(height: 24),
              TextButton(
                onPressed: () => context.read<OtpBloc>().add(RequestOtp('0999123456')),
                child: Text('إعادة إرسال الرمز'),
              ),
            ]),
          );
        },
      ),
    );
  }
}
```
