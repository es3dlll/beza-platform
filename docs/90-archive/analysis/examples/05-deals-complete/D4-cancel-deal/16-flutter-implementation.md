# 16 - تطبيق Flutter (Flutter Implementation) - إلغاء صفقة + استرجاع

_(شاشة Admin — عرض إشعارات الاسترجاع)_

## RefundNotification في Flutter

```dart
// عرض إشعار الاسترجاع
class RefundNotificationHandler {
  static void handle(Map<String, dynamic> data) {
    final amount = data['body'] ?? '';
    showDialog(
      context: navigatorKey.currentContext!,
      builder: (_) => AlertDialog(
        title: Text('استرجاع مبلغ'),
        content: Text(amount),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: Text('حسناً')),
        ],
      ),
    );
  }
}
```
