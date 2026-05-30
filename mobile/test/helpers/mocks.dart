import 'package:mockito/annotations.dart';
import 'package:flutter_secure_storage/flutter_secure_storage.dart';
import 'package:beza_platform/core/api/api_client.dart';
import 'package:beza_platform/features/auth/services/auth_service.dart';
import 'package:beza_platform/features/home/services/home_service.dart';
import 'package:beza_platform/features/notifications/services/notification_service.dart';

@GenerateNiceMocks([
  MockSpec<FlutterSecureStorage>(),
  MockSpec<ApiClient>(),
  MockSpec<AuthService>(),
  MockSpec<HomeService>(),
  MockSpec<NotificationService>(),
])
void main() {}
