# 16 - تطبيق Flutter (Flutter Implementation) — تسجيل الدخول (Login)

## طبقة المجال (Domain Layer)

```dart
// domain/repositories/i_auth_repository.dart
abstract class IAuthRepository {
  Future<LoginResult> login({
    required String phone,
    required String password,
    String? deviceId,
  });
}

class LoginResult {
  final UserEntity user;
  final String token;
  final int expiresIn;
  final bool requires2fa;

  LoginResult({required this.user, required this.token, this.expiresIn = 3600, this.requires2fa = false});
}
```

## طبقة البيانات (Data Layer)

```dart
// data/repositories/auth_repository.dart
class AuthRepository implements IAuthRepository {
  @override
  Future<LoginResult> login({required String phone, required String password, String? deviceId}) async {
    final response = await client.post(
      Uri.parse('$baseUrl/api/v1/auth/login'),
      body: jsonEncode({'phone': phone, 'password': password, 'device_id': deviceId}),
      headers: {'Accept': 'application/json', 'Content-Type': 'application/json'},
    );

    final body = jsonDecode(response.body);

    if (response.statusCode == 200) {
      final requires2fa = body['data']['requires_2fa'] ?? false;
      return LoginResult(
        user: UserEntity.fromJson(body['data']['user']),
        token: body['data']['token'],
        expiresIn: body['data']['expires_in'] ?? 3600,
        requires2fa: requires2fa,
      );
    }

    if (response.statusCode == 429 && body['data']?['locked_remaining_minutes'] != null) {
      throw AccountLockedException(body['data']['locked_remaining_minutes']);
    }

    throw AuthException(body['message']);
  }
}
```

## طبقة العرض (Presentation Layer) (BLoC)

```dart
// presentation/bloc/login_event.dart
class LoginSubmitted extends LoginEvent {
  final String phone, password;
}

// presentation/bloc/login_bloc.dart
class LoginBloc extends Bloc<LoginEvent, LoginState> {
  final IAuthRepository repository;

  Future<void> _onLogin(LoginSubmitted event, Emitter emit) async {
    emit(LoginLoading());
    try {
      final result = await repository.login(phone: event.phone, password: event.password);
      await TokenService(FlutterSecureStorage()).saveTokens(
        accessToken: result.token,
        expiresIn: result.expiresIn,
      );

      if (result.requires2fa) {
        emit(LoginRequires2fa(result.token));
      } else {
        emit(LoginSuccess(result.user));
      }
    } on AccountLockedException catch (e) {
      emit(LoginFailure('تم قفل الحساب. حاول بعد ${e.minutes} دقيقة'));
    } on AuthException catch (e) {
      emit(LoginFailure(e.message));
    }
  }
}
```

## UI

```dart
class LoginScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: Text('تسجيل الدخول')),
      body: BlocConsumer<LoginBloc, LoginState>(
        listener: (context, state) {
          if (state is LoginSuccess) Navigator.pushReplacementNamed(context, '/home');
          if (state is LoginRequires2fa) Navigator.pushNamed(context, '/2fa', arguments: state.token);
        },
        builder: (context, state) {
          return Form(
            child: Padding(
              padding: EdgeInsets.all(16),
              child: Column(children: [
                TextFormField(decoration: InputDecoration(labelText: 'رقم الهاتف'), keyboardType: TextInputType.phone),
                TextFormField(decoration: InputDecoration(labelText: 'كلمة المرور'), obscureText: true),
                SizedBox(height: 24),
                ElevatedButton(
                  onPressed: state is LoginLoading ? null : () => context.read<LoginBloc>().add(LoginSubmitted(...)),
                  child: state is LoginLoading ? CircularProgressIndicator() : Text('دخول'),
                ),
              ]),
            ),
          );
        },
      ),
    );
  }
}
```
