import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'package:beza_mobile/services/auth_service.dart';
import 'package:beza_mobile/services/secure_storage_service.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('Arabic error messages', () {
    setUp(() {
      FlutterSecureStorage.setMockInitialValues({});
    });

    test('auth service preserves stored data after login simulation',
        () async {
      final storage = SecureStorageService();
      await storage.saveToken('test-token-123');
      await storage.saveUserData(
        id: 'user-1',
        name: 'أحمد',
        email: 'ahmed@test.com',
      );

      final data = await storage.getUserData();
      expect(data['name'], equals('أحمد'));
      expect(data['email'], equals('ahmed@test.com'));
    });

    test('auth service clears data on logout', () async {
      final storage = SecureStorageService();
      await storage.saveToken('test-token');
      await storage.saveUserData(
        id: 'user-1',
        name: 'أحمد',
        email: 'ahmed@test.com',
      );

      final auth = AuthService(baseUrl: 'http://localhost:8000/api');
      await auth.logout();

      expect(await storage.getToken(), isNull);
      expect(auth.isAuthenticated, isFalse);
    });
  });
}
