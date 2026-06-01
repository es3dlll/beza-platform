# 16 - تطبيق Flutter (Flutter Implementation) - فهرس - كشف الاحتيال (Fraud Detection)

## FraudAlert Widget

```dart
class FraudAlertDialog extends StatelessWidget {
  final String message;
  final String riskLevel;
  final VoidCallback onConfirm;
  final VoidCallback onCancel;

  @override
  Widget build(BuildContext context) {
    return AlertDialog(
      title: Row(
        children: [
          Icon(Icons.warning_amber_rounded, color: Colors.red, size: 28),
          SizedBox(width: 8),
          Text('تنبيه أمني'),
        ],
      ),
      content: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text(message),
          SizedBox(height: 12),
          _RiskBadge(riskLevel),
        ],
      ),
      actions: [
        TextButton(onPressed: onCancel, child: Text('إلغاء')),
        ElevatedButton(onPressed: onConfirm, child: Text('متابعة')),
      ],
    );
  }
}
```

## Device Fingerprint

```dart
import 'dart:io';
import 'package:device_info_plus/device_info_plus.dart';

class DeviceFingerprintService {
  static Future<String> getFingerprint() async {
    final deviceInfo = DeviceInfoPlugin();
    String fingerprint;

    if (Platform.isAndroid) {
      final info = await deviceInfo.androidInfo;
      fingerprint = '${info.id}-${info.brand}-${info.model}';
    } else {
      final info = await deviceInfo.iosInfo;
      fingerprint = '${info.identifierForVendor}-${info.model}';
    }

    return fingerprint;
  }

  static Map<String, String> getDeviceHeaders() {
    return {
      'X-Device-ID': fingerprint,
      'X-Device-Name': deviceName,
      'X-OS': Platform.operatingSystem,
    };
  }
}
```

## التعامل مع قفل PIN

```dart
class PinLockoutHandler {
  static void handlePinLockout(int remainingMinutes) {
    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: Text('PIN مقفول'),
        content: Text('تم قفل PIN لمدة $remainingMinutes دقيقة بسبب المحاولات الخاطئة.'),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: Text('حسناً'),
          ),
        ],
      ),
    );
  }
}
```
