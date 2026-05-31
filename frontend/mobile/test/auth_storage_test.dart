import 'package:flutter_test/flutter_test.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';

import 'package:beza_mobile/services/secure_storage_service.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('SecureStorageService - token storage', () {
    late SecureStorageService service;

    setUp(() {
      service = SecureStorageService();
      FlutterSecureStorage.setMockInitialValues({});
    });

    test('save and retrieve token', () async {
      await service.saveToken('test-token-123');
      final token = await service.getToken();
      expect(token, equals('test-token-123'));
    });

    test('delete token', () async {
      await service.saveToken('test-token-123');
      await service.deleteToken();
      final token = await service.getToken();
      expect(token, isNull);
    });

    test('clearAll removes all data', () async {
      await service.saveToken('test-token');
      await service.saveUserData(
        id: 'user-1',
        name: 'أحمد',
        email: 'ahmed@test.com',
      );
      await service.clearAll();
      expect(await service.getToken(), isNull);
      final userData = await service.getUserData();
      expect(userData['id'], isNull);
      expect(userData['name'], isNull);
      expect(userData['email'], isNull);
    });
  });
}
