# 16 - تطبيق Flutter (Flutter Implementation) - إنشاء المحفظة المزدوجة (W1 Create Wallet)

## هيكل الملفات

```
lib/features/auth/
├── data/
│   ├── models/
│   │   ├── register_request_model.dart
│   │   └── register_response_model.dart
│   ├── repositories/
│   │   └── auth_repository.dart
│   └── datasources/
│       └── auth_remote_datasource.dart
├── domain/
│   ├── entities/
│   │   └── user_entity.dart
│   └── repositories/
│       └── i_auth_repository.dart
└── presentation/
    ├── bloc/
    │   ├── auth_bloc.dart
    │   ├── auth_event.dart
    │   └── auth_state.dart
    └── screens/
        └── register_screen.dart
```

## طبقة المجال (Domain Layer)

```dart
// domain/entities/user_entity.dart
class UserEntity {
  final int id;
  final String name;
  final String phone;
  final String token;
  final WalletInfo sypWallet;
  final WalletInfo usdWallet;

  UserEntity({
    required this.id,
    required this.name,
    required this.phone,
    required this.token,
    required this.sypWallet,
    required this.usdWallet,
  });
}

class WalletInfo {
  final String walletNumber;
  final double balance;
  final String currency;

  WalletInfo({
    required this.walletNumber,
    required this.balance,
    required this.currency,
  });
}
```

## طبقة البيانات (Data Layer)

```dart
// data/models/register_response_model.dart
class RegisterResponseModel {
  final bool success;
  final String message;
  final UserData? user;
  final String? token;
  final WalletData? sypWallet;
  final WalletData? usdWallet;

  RegisterResponseModel.fromJson(Map<String, dynamic> json)
      : success = json['success'] as bool,
        message = json['message'] as String,
        user = json['data']?['user'] != null
            ? UserData.fromJson(json['data']['user'])
            : null,
        token = json['data']?['token'] as String?,
        sypWallet = json['data']?['wallets']?['syp'] != null
            ? WalletData.fromJson(json['data']['wallets']['syp'])
            : null,
        usdWallet = json['data']?['wallets']?['usd'] != null
            ? WalletData.fromJson(json['data']['wallets']['usd'])
            : null;

  UserEntity toEntity() => UserEntity(
    id: user!.id,
    name: user!.name,
    phone: user!.phone,
    token: token!,
    sypWallet: WalletInfo(
      walletNumber: sypWallet!.walletNumber,
      balance: sypWallet!.balance,
      currency: 'SYP',
    ),
    usdWallet: WalletInfo(
      walletNumber: usdWallet!.walletNumber,
      balance: usdWallet!.balance,
      currency: 'USD',
    ),
  );
}

// تخزين التوكن بعد التسجيل
final tokenService = TokenService(FlutterSecureStorage());
await tokenService.saveTokens(
  accessToken: token!,
  expiresIn: json['data']['expires_in'] ?? 3600,
);

class UserData {
  final int id;
  final String name;
  final String phone;
  final String status;

  UserData.fromJson(Map<String, dynamic> json)
      : id = json['id'] as int,
        name = json['name'] as String,
        phone = json['phone'] as String,
        status = json['status'] as String;
}

class WalletData {
  final String walletNumber;
  final double balance;

  WalletData.fromJson(Map<String, dynamic> json)
      : walletNumber = json['wallet_number'] as String,
        balance = (json['balance'] as num).toDouble();
}
```

## طبقة العرض (Presentation Layer)

```dart
// presentation/bloc/auth_state.dart
abstract class AuthState {}

class AuthInitial extends AuthState {}

class AuthLoading extends AuthState {}

class AuthSuccess extends AuthState {
  final UserEntity user;
  AuthSuccess(this.user);
}

class AuthFailure extends AuthState {
  final String error;
  AuthFailure(this.error);
}
```

```dart
// presentation/screens/register_screen.dart
class RegisterScreen extends StatelessWidget {
  const RegisterScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('إنشاء حساب')),
      body: Padding(
        padding: const EdgeInsets.all(24),
        child: Column(
          children: [
            const Text(
              'سجل الآن واحصل على 5$ هدية!',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 8),
            const Text(
              'عند التسجيل، تحصل على محفظتين (SYP + USD) تلقائياً',
              style: TextStyle(color: Colors.grey),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            _buildRegisterForm(context),
          ],
        ),
      ),
    );
  }

  Widget _buildRegisterForm(BuildContext context) {
    return Form(
      child: Column(
        children: [
          TextFormField(
            decoration: const InputDecoration(labelText: 'الاسم'),
            validator: (v) => v?.isEmpty ?? true ? 'الاسم مطلوب' : null,
          ),
          const SizedBox(height: 16),
          TextFormField(
            decoration: const InputDecoration(labelText: 'رقم الهاتف', prefixText: '+963 '),
            keyboardType: TextInputType.phone,
          ),
          const SizedBox(height: 16),
          TextFormField(
            decoration: const InputDecoration(labelText: 'كلمة المرور'),
            obscureText: true,
          ),
          const SizedBox(height: 16),
          TextFormField(
            decoration: const InputDecoration(labelText: 'PIN (4 أرقام)'),
            obscureText: true,
            maxLength: 4,
            keyboardType: TextInputType.number,
          ),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: () {
              // بعد التسجيل الناجح → توجيه للرئيسية
              // Navigator.pushReplacementNamed(context, '/home');
            },
            child: const Text('تسجيل'),
          ),
        ],
      ),
    );
  }
}
```
