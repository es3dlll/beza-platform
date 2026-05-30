import '../../../core/api/api_client.dart';

class AppNotification {
  final int id;
  final String type;
  final String? title;
  final String? titleAr;
  final String? body;
  final String? bodyAr;
  final bool isRead;
  final Map<String, dynamic>? data;
  final DateTime createdAt;
  final DateTime? readAt;

  AppNotification({
    required this.id,
    required this.type,
    this.title,
    this.titleAr,
    this.body,
    this.bodyAr,
    required this.isRead,
    this.data,
    required this.createdAt,
    this.readAt,
  });

  factory AppNotification.fromJson(Map<String, dynamic> json) {
    return AppNotification(
      id: json['id'] as int,
      type: json['type'] as String? ?? '',
      title: json['title'] as String?,
      titleAr: json['title_ar'] as String?,
      body: json['body'] as String?,
      bodyAr: json['body_ar'] as String?,
      isRead: json['is_read'] as bool? ?? false,
      data: json['data'] as Map<String, dynamic>?,
      createdAt: DateTime.parse(json['created_at'] as String).toLocal(),
      readAt: json['read_at'] != null
          ? DateTime.parse(json['read_at'] as String).toLocal()
          : null,
    );
  }
}

class NotificationService {
  final ApiClient _client;

  NotificationService(this._client);

  Future<Map<String, dynamic>> getNotifications({
    int page = 1,
    int perPage = 20,
  }) async {
    final response = await _client.get(
      '/notifications',
      queryParameters: {'page': page, 'per_page': perPage},
    );
    return response.data;
  }

  Future<void> markAsRead(int id) async {
    await _client.post('/notifications/$id/read');
  }

  Future<void> markAllAsRead() async {
    await _client.post('/notifications/mark-all-read');
  }
}
