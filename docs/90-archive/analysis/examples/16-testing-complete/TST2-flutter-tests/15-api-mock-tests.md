# 15 - اختبارات محاكاة API (API Mock Tests)

```dart
import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:beza_mobile/data/repositories/auth_repository.dart';
import 'package:beza_mobile/models/api_response.dart';

void main() {
  late AuthRepository authRepository;
  late MockApiClient mockApiClient;

  setUp(() {
    mockApiClient = MockApiClient();
    authRepository = AuthRepository(apiClient: mockApiClient);
  });

  group('Auth API Mock Tests', () {
    test('register returns token', () async {
      when(mockApiClient.post('/auth/register', any))
        .thenAnswer((_) async => {
          'success': true,
          'data': {
            'token': '1|abc123token',
            'user': UserModelFactory.validJson,
          },
        });

      final response = await authRepository.register(
        name: 'مستخدم جديد',
        phone: '963900000005',
        password: 'StrongPass123',
        pin: '1234',
      );

      expect(response.token, isNotEmpty);
      expect(response.user.name, 'أحمد');
    });

    test('login returns token', () async {
      when(mockApiClient.post('/auth/login', any))
        .thenAnswer((_) async => {
          'success': true,
          'data': {
            'token': '1|xyztoken456',
            'user': UserModelFactory.validJson,
          },
        });

      final response = await authRepository.login(
        phone: '963900000001',
        password: 'password',
      );

      expect(response.token, '1|xyztoken456');
    });

    test('login fails with wrong credentials', () async {
      when(mockApiClient.post('/auth/login', any))
        .thenThrow(ApiException(statusCode: 401, message: 'بيانات غير صحيحة'));

      expect(
        () => authRepository.login(
          phone: '963900000001',
          password: 'wrong',
        ),
        throwsA(isA<ApiException>()),
      );
    });
  });
}
```
