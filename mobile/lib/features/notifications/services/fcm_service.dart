import 'package:firebase_core/firebase_core.dart';
import 'package:firebase_messaging/firebase_messaging.dart';
import 'package:flutter_local_notifications/flutter_local_notifications.dart';
import 'package:go_router/go_router.dart';
import 'package:shared_preferences/shared_preferences.dart';
import '../../../core/api/api_client.dart';

@pragma('vm:entry-point')
Future<void> firebaseMessagingBackgroundHandler(RemoteMessage message) async {
  await Firebase.initializeApp();
}

class FcmService {
  static final FlutterLocalNotificationsPlugin _localNotifications =
      FlutterLocalNotificationsPlugin();
  static GoRouter? _router;
  static RemoteMessage? _initialMessage;
  static const String _tokenKey = 'fcm_token';

  static Future<void> initialize() async {
    try {
      await Firebase.initializeApp();
    } catch (e) {
      return;
    }

    try {
      final messaging = FirebaseMessaging.instance;

      await messaging.requestPermission(
        alert: true,
        badge: true,
        sound: true,
        provisional: false,
      );

      FirebaseMessaging.onBackgroundMessage(firebaseMessagingBackgroundHandler);

      final token = await messaging.getToken();
      if (token != null) {
        await _saveToken(token);
        await sendTokenToBackend(token);
      }

      _initialMessage = await messaging.getInitialMessage();

      FirebaseMessaging.onMessageOpenedApp.listen(_handleMessage);

      FirebaseMessaging.onMessage.listen(_showLocalNotification);

      messaging.onTokenRefresh.listen((token) async {
        await _saveToken(token);
        await sendTokenToBackend(token);
      });
    } catch (_) {}

    try {
      const androidSettings = AndroidInitializationSettings('@mipmap/ic_launcher');
      const iosSettings = DarwinInitializationSettings();
      const initSettings = InitializationSettings(
        android: androidSettings,
        iOS: iosSettings,
      );

      await _localNotifications.initialize(
        initSettings,
        onDidReceiveNotificationResponse: (response) {
          if (response.payload != null && _router != null) {
            _router!.go(response.payload!);
          }
        },
      );
    } catch (_) {}
  }

  static void setRouter(GoRouter router) {
    _router = router;
    if (_initialMessage != null) {
      _handleMessage(_initialMessage!);
      _initialMessage = null;
    }
  }

  static Future<String?> getToken() async {
    final prefs = await SharedPreferences.getInstance();
    return prefs.getString(_tokenKey);
  }

  static Future<void> sendTokenToBackend(String token) async {
    try {
      final client = ApiClient();
      await client.post('/notifications/fcm-token', data: {
        'token': token,
        'platform': 'android',
      });
    } catch (_) {}
  }

  static Future<void> _handleMessage(RemoteMessage message) async {
    final route = message.data['route'] ?? '/notifications';
    if (_router != null) {
      _router!.go(route as String);
    }
  }

  static Future<void> _showLocalNotification(RemoteMessage message) async {
    final notification = message.notification;
    if (notification == null) return;

    const androidDetails = AndroidNotificationDetails(
      'beza_notifications',
      'Beza Notifications',
      channelDescription: 'Beza Financial OS notifications',
      importance: Importance.high,
      priority: Priority.high,
    );
    const iosDetails = DarwinNotificationDetails();
    const details = NotificationDetails(
      android: androidDetails,
      iOS: iosDetails,
    );

    await _localNotifications.show(
      notification.hashCode,
      notification.title,
      notification.body,
      details,
      payload: message.data['route'] ?? '/notifications',
    );
  }

  static Future<void> _saveToken(String token) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_tokenKey, token);
  }
}
