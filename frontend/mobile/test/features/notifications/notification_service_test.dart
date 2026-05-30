import 'package:flutter_test/flutter_test.dart';
import 'package:mockito/mockito.dart';
import 'package:dio/dio.dart';
import 'package:beza_platform/features/notifications/services/notification_service.dart';

import '../../helpers/test_helpers.dart';
import '../../helpers/mocks.mocks.dart';

void main() {
  late MockApiClient mockClient;
  late NotificationService service;

  setUp(() {
    mockClient = MockApiClient();
    service = NotificationService(mockClient);
  });

  group('getNotifications', () {
    test('returns data from API', () async {
      final responseData = {
        'data': [
          {
            'id': 1,
            'type': 'info',
            'title': 'Welcome',
            'title_ar': 'مرحباً',
            'body': 'Welcome to Beza',
            'body_ar': 'مرحباً بكم في بزة',
            'is_read': false,
            'created_at': '2025-01-15T10:00:00.000Z',
          },
        ],
        'meta': {'current_page': 1, 'last_page': 1},
      };

      when(mockClient.get('/notifications',
              queryParameters: argThat(isNotNull, named: 'queryParameters')))
          .thenAnswer((_) async => Response(
                requestOptions: RequestOptions(path: '/notifications'),
                data: responseData,
                statusCode: 200,
              ));

      final result = await service.getNotifications(page: 1, perPage: 20);

      expect(result, responseData);
      expect((result['data'] as List).length, 1);
      verify(mockClient.get('/notifications',
              queryParameters: {'page': 1, 'per_page': 20}))
          .called(1);
    });

    test('uses default pagination values', () async {
      when(mockClient.get('/notifications',
              queryParameters: argThat(isNotNull, named: 'queryParameters')))
          .thenAnswer((_) async => Response(
                requestOptions: RequestOptions(path: '/notifications'),
                data: {'data': []},
                statusCode: 200,
              ));

      await service.getNotifications();

      verify(mockClient.get('/notifications',
              queryParameters: {'page': 1, 'per_page': 20}))
          .called(1);
    });

    test('AppNotification.fromJson parses correctly', () {
      final json = {
        'id': 42,
        'type': 'transaction',
        'title': 'Deposit',
        'title_ar': 'إيداع',
        'body': 'You received 1000 SYP',
        'body_ar': 'لقد استلمت ١٠٠٠ ل.س',
        'is_read': false,
        'data': {'amount': 100000},
        'created_at': '2025-06-15T14:30:00.000Z',
        'read_at': null,
      };

      final notification = AppNotification.fromJson(json);

      expect(notification.id, 42);
      expect(notification.type, 'transaction');
      expect(notification.title, 'Deposit');
      expect(notification.titleAr, 'إيداع');
      expect(notification.isRead, false);
      expect(notification.data, {'amount': 100000});
      expect(notification.readAt, isNull);
    });

    test('AppNotification.fromJson handles read notification', () {
      final json = {
        'id': 7,
        'type': 'alert',
        'title': null,
        'title_ar': 'تنبيه',
        'body': null,
        'body_ar': 'تمت قراءة هذا الإشعار',
        'is_read': true,
        'data': null,
        'created_at': '2025-01-01T00:00:00.000Z',
        'read_at': '2025-01-01T01:00:00.000Z',
      };

      final notification = AppNotification.fromJson(json);

      expect(notification.id, 7);
      expect(notification.isRead, true);
      expect(notification.title, isNull);
      expect(notification.readAt, isNotNull);
    });
  });

  group('markAsRead', () {
    test('posts to notification read endpoint', () async {
      when(mockClient.post('/notifications/5/read'))
          .thenAnswer((_) async => Response(
                requestOptions: RequestOptions(path: '/notifications/5/read'),
                statusCode: 200,
              ));

      await service.markAsRead(5);

      verify(mockClient.post('/notifications/5/read')).called(1);
    });
  });

  group('markAllAsRead', () {
    test('posts to mark-all-read endpoint', () async {
      when(mockClient.post('/notifications/mark-all-read'))
          .thenAnswer((_) async => Response(
                requestOptions: RequestOptions(path: '/notifications/mark-all-read'),
                statusCode: 200,
              ));

      await service.markAllAsRead();

      verify(mockClient.post('/notifications/mark-all-read')).called(1);
    });
  });

  group('error handling', () {
    test('throws when getNotifications fails', () async {
      when(mockClient.get('/notifications',
              queryParameters: argThat(isNotNull, named: 'queryParameters')))
          .thenThrow(DioException(
        requestOptions: RequestOptions(path: '/notifications'),
        type: DioExceptionType.connectionTimeout,
      ));

      expect(
        () => service.getNotifications(),
        throwsA(isA<DioException>()),
      );
    });
  });
}
