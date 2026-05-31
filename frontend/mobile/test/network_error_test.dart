import 'package:flutter_test/flutter_test.dart';

import 'package:beza_mobile/services/network_service.dart';
import 'package:beza_mobile/services/secure_storage_service.dart';

void main() {
  TestWidgetsFlutterBinding.ensureInitialized();

  group('NetworkService - error handling', () {
    test('handles invalid URL gracefully', () async {
      final mockChecker = _MockConnectivityChecker(online: true);
      final mockStorage = _MockSecureStorage();
      final service = NetworkService(
        baseUrl: 'http://invalid-url-that-does-not-exist.xyz',
        connectivityChecker: mockChecker,
        storage: mockStorage,
      );

      final result = await service.get('/v1/wallet/balance');

      expect(result.success, isFalse);
      expect(result.errorMessage, isNotNull);
    });
  });
}

class _MockConnectivityChecker extends ConnectivityChecker {
  final bool _online;

  _MockConnectivityChecker({required bool online}) : _online = online;

  @override
  bool get isOnline => _online;

  @override
  Future<bool> check() async => _online;
}

class _MockSecureStorage extends SecureStorageService {
  @override
  Future<String?> getToken() async => null;

  @override
  Future<void> deleteToken() async {}
}
