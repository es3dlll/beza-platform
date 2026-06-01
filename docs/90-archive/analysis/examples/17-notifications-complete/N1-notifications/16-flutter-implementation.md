# 16 - تطبيق Flutter (Flutter Implementation) - فهرس - نظام الإشعارات (Notifications System)

## FCM Setup

```dart
// lib/services/fcm_service.dart
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';

class FcmService {
  final FirebaseMessaging _fcm = FirebaseMessaging.instance;
  final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();

  Future<void> initialize() async {
    await _requestPermission();

    // تهيئة الإشعارات المحلية
    const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
    const iosSettings = DarwinInitializationSettings(
      requestAlertPermission: true,
      requestBadgePermission: true,
      requestSoundPermission: true,
    );
    await _localNotifications.initialize(
      const InitializationSettings(
        android: androidSettings,
        iOS: iosSettings,
      ),
      onDidReceiveNotificationResponse: _handleNotificationTap,
    );

    // الحصول على التوكن
    final token = await _fcm.getToken();
    await _saveToken(token!);

    // معالجة الإشعارات في الخلفية
    FirebaseMessaging.onBackgroundMessage(_backgroundHandler);

    // معالجة الإشعارات عند فتح التطبيق
    FirebaseMessaging.onMessage.listen(_handleForegroundMessage);
    FirebaseMessaging.onMessageOpenedApp.listen(_handleNotificationTap);
  }

  Future<void> _requestPermission() async {
    final settings = await _fcm.requestPermission(
      alert: true,
      badge: true,
      sound: true,
    });
  }

  Future<void> _saveToken(String token) async {
    final tokenService = TokenService(FlutterSecureStorage());
    final authToken = await tokenService.getValidToken();
    await ApiService.post('/api/v1/device-tokens', {
      'token': token,
      'platform': Platform.isAndroid ? 'android' : 'ios',
    }, headers: {
      'Authorization': 'Bearer ${authToken ?? ''}',
    });
  }

  Future<void> _handleForegroundMessage(RemoteMessage message) async {
    final notification = message.notification!;
    await _localNotifications.show(
      notification.hashCode,
      notification.title,
      notification.body,
      const NotificationDetails(
        android: AndroidNotificationDetails(
          'default',
          'Beza',
          channelDescription: 'إشعارات Beza',
          importance: Importance.high,
          priority: Priority.high,
        ),
        iOS: DarwinNotificationDetails(),
      ),
    );
  }

  void _handleNotificationTap(NotificationResponse? response) {
    // التنقل إلى الشاشة المناسبة
  }
}

Future<void> _backgroundHandler(RemoteMessage message) async {
  // معالجة الخلفية
}
```

## Notification List Screen

```dart
// lib/screens/notifications/notification_list_screen.dart
import 'package:flutter/material.dart';
import 'package:flutter_bloc/flutter_bloc.dart';

class NotificationListScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: Text('الإشعارات'),
        actions: [
          IconButton(
            icon: Icon(Icons.checklist_rtl),
            onPressed: () => context.read<NotificationCubit>().markAllAsRead(),
          ),
        ],
      ),
      body: BlocBuilder<NotificationCubit, NotificationState>(
        builder: (context, state) {
          if (state is NotificationLoading) {
            return Center(child: CircularProgressIndicator());
          }
          if (state is NotificationError) {
            return Center(child: Text('خطأ: ${state.message}'));
          }
          if (state is NotificationLoaded) {
            return ListView.builder(
              itemCount: state.notifications.length,
              itemBuilder: (context, index) {
                final notification = state.notifications[index];
                return ListTile(
                  leading: Icon(
                    _getIcon(notification.type),
                    color: notification.readAt == null
                        ? Theme.of(context).primaryColor
                        : Colors.grey,
                  ),
                  title: Text(
                    notification.title,
                    style: TextStyle(
                      fontWeight: notification.readAt == null
                          ? FontWeight.bold
                          : FontWeight.normal,
                    ),
                  ),
                  subtitle: Text(notification.body),
                  trailing: Text(
                    _formatDate(notification.createdAt),
                    style: TextStyle(fontSize: 12, color: Colors.grey),
                  ),
                  onTap: () => context
                      .read<NotificationCubit>()
                      .markAsRead(notification.id),
                );
              },
            );
          }
          return SizedBox.shrink();
        },
      ),
    );
  }

  IconData _getIcon(String type) {
    switch (type) {
      case 'transfer_in':
      case 'transfer_out':
        return Icons.swap_horiz;
      case 'kyc':
        return Icons.verified_user;
      case 'deal':
        return Icons.trending_up;
      default:
        return Icons.notifications;
    }
  }

  String _formatDate(DateTime date) {
    final now = DateTime.now();
    final diff = now.difference(date);
    if (diff.inMinutes < 60) return 'منذ ${diff.inMinutes} دقيقة';
    if (diff.inHours < 24) return 'منذ ${diff.inHours} ساعة';
    return '${date.day}/${date.month}/${date.year}';
  }
}
```
