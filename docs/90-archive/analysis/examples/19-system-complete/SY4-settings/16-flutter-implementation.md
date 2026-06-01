# 16 - تنفيذ Flutter: مزود إعدادات النظام من API (Flutter Implementation)

## نظرة عامة (Overview)

تطبيق Flutter (Mobile App) يقرأ إعدادات النظام من API لاستخدامها في التطبيق. لا يمكن للمستخدم العادي تعديل الإعدادات من التطبيق — فقط المسؤول من لوحة الإدارة.

```php
// // Flutter يقرأ الإعدادات فقط (read-only)
// // الإعدادات تستخدم لضبط سلوك التطبيق
// // مثلاً: تفعيل/تعطيل ميزات بناءً على feature flags
```

## موديل الإعدادات (Settings Model)

```dart
// // ملف: lib/models/system_settings.dart
// // موديل إعدادات النظام لتحليل JSON من API

class SystemSettings {
  final GeneralSettings general;
  final FeatureFlags features;
  final FeeSettings fees;
  final LimitSettings limits;
  final ExchangeSettings exchange;
  final SecuritySettings security;
  final NotificationSettings notifications;
  final MaintenanceSettings maintenance;

  SystemSettings({
    required this.general,
    required this.features,
    required this.fees,
    required this.limits,
    required this.exchange,
    required this.security,
    required this.notifications,
    required this.maintenance,
  });

  /// تحليل JSON إلى كائن SystemSettings
  factory SystemSettings.fromJson(Map<String, dynamic> json) {
    return SystemSettings(
      general: GeneralSettings.fromJson(json['general'] ?? {}),
      features: FeatureFlags.fromJson(json['features'] ?? {}),
      fees: FeeSettings.fromJson(json['fees'] ?? {}),
      limits: LimitSettings.fromJson(json['limits'] ?? {}),
      exchange: ExchangeSettings.fromJson(json['exchange'] ?? {}),
      security: SecuritySettings.fromJson(json['security'] ?? {}),
      notifications: NotificationSettings.fromJson(json['notifications'] ?? {}),
      maintenance: MaintenanceSettings.fromJson(json['maintenance'] ?? {}),
    );
  }

  Map<String, dynamic> toJson() => {
    'general': general.toJson(),
    'features': features.toJson(),
    'fees': fees.toJson(),
    'limits': limits.toJson(),
    'exchange': exchange.toJson(),
    'security': security.toJson(),
    'notifications': notifications.toJson(),
    'maintenance': maintenance.toJson(),
  };
}

// // الإعدادات العامة
class GeneralSettings {
  final String appName;
  final String appDescription;
  final String? logo;
  final String? favicon;
  final String timezone;
  final String locale;

  GeneralSettings({
    this.appName = 'Beza',
    this.appDescription = '',
    this.logo,
    this.favicon,
    this.timezone = 'Asia/Riyadh',
    this.locale = 'ar',
  });

  factory GeneralSettings.fromJson(Map<String, dynamic> json) {
    return GeneralSettings(
      appName: json['app_name'] ?? 'Beza',
      appDescription: json['app_description'] ?? '',
      logo: json['app_logo'],
      favicon: json['app_favicon'],
      timezone: json['timezone'] ?? 'Asia/Riyadh',
      locale: json['locale'] ?? 'ar',
    );
  }

  Map<String, dynamic> toJson() => {};
}

// // خصائص المنصة (Feature Flags)
class FeatureFlags {
  final bool goldEnabled;
  final bool dealsEnabled;
  final bool cardsEnabled;
  final bool agentsEnabled;
  final bool loansEnabled;

  FeatureFlags({
    this.goldEnabled = true,
    this.dealsEnabled = true,
    this.cardsEnabled = true,
    this.agentsEnabled = true,
    this.loansEnabled = false,
  });

  factory FeatureFlags.fromJson(Map<String, dynamic> json) {
    return FeatureFlags(
      goldEnabled: json['gold'] ?? true,
      dealsEnabled: json['deals'] ?? true,
      cardsEnabled: json['cards'] ?? true,
      agentsEnabled: json['agents'] ?? true,
      loansEnabled: json['loans'] ?? false,
    );
  }

  /// التحقق من تفعيل ميزة معينة (للقراءة من أي مكان)
  bool isEnabled(String feature) {
    switch (feature) {
      case 'gold': return goldEnabled;
      case 'deals': return dealsEnabled;
      case 'cards': return cardsEnabled;
      case 'agents': return agentsEnabled;
      case 'loans': return loansEnabled;
      default: return false;
    }
  }

  Map<String, dynamic> toJson() => {};
}
```

## مزود الإعدادات (Settings Provider)

```dart
// // ملف: lib/providers/system_settings_provider.dart
// // مزود حالة إعدادات النظام للتطبيق

import 'package:flutter/foundation.dart';
import '../models/system_settings.dart';
import '../services/api_service.dart';
import '../services/cache_service.dart';

class SystemSettingsProvider extends ChangeNotifier {
  final ApiService _api;
  final CacheService _cache;

  SystemSettings? _settings;
  bool _isLoading = false;
  String? _error;
  DateTime? _lastSynced;

  SystemSettingsProvider({
    required ApiService api,
    required CacheService cache,
  })  : _api = api,
        _cache = cache;

  SystemSettings? get settings => _settings;
  bool get isLoading => _isLoading;
  String? get error => _error;
  bool get hasSettings => _settings != null;
  DateTime? get lastSynced => _lastSynced;

  /// تحميل الإعدادات من الكاش المحلي أولاً ثم من API
  Future<void> loadSettings() async {
    _isLoading = true;
    notifyListeners();

    try {
      // // المحاولة من الكاش المحلي أولاً
      final cached = await _cache.getSystemSettings();
      if (cached != null) {
        _settings = cached;
        _error = null;
      }

      // // تحديث من API
      final response = await _api.get('/admin/system/settings');
      if (response.statusCode == 200) {
        final data = response.data['data'];
        _settings = SystemSettings.fromJson(data);
        await _cache.setSystemSettings(_settings!);
        _error = null;
        _lastSynced = DateTime.now();
      }
    } catch (e) {
      // // إذا فشل API والكاش متاح -> استمر بالكاش
      if (_settings == null) {
        _error = 'فشل تحميل إعدادات النظام';
      }
    } finally {
      _isLoading = false;
      notifyListeners();
    }
  }

  /// إعادة تحميل الإعدادات من API (تحديث قسري)
  Future<void> refreshSettings() async {
    _cache.clearSystemSettings();
    await loadSettings();
  }

  /// التحقق من تفعيل ميزة
  bool isFeatureEnabled(String feature) {
    return _settings?.features.isEnabled(feature) ?? false;
  }

  /// الحصول على نسبة رسوم محددة
  double getFee(String feeType) {
    if (_settings == null) return 0.0;
    switch (feeType) {
      case 'p2p': return _settings!.fees.p2p;
      case 'exchange': return _settings!.fees.exchange;
      case 'card_deposit': return _settings!.fees.cardDeposit;
      case 'withdrawal': return _settings!.fees.withdrawal;
      default: return 0.0;
    }
  }

  /// هل التطبيق في وضع الصيانة؟
  bool get isUnderMaintenance {
    return _settings?.maintenance.mode ?? false;
  }

  /// رسالة الصيانة
  String? get maintenanceMessage {
    return _settings?.maintenance.message;
  }

  /// قائمة IPs المسموح لها في الصيانة
  List<String> get allowedIps {
    return _settings?.maintenance.allowedIps ?? [];
  }
}
```

## استخدام المزود في التطبيق

```dart
// // في main.dart
void main() async {
  WidgetsFlutterBinding.ensureInitialized();

  final api = ApiService();
  final cache = CacheService();
  final settingsProvider = SystemSettingsProvider(api: api, cache: cache);

  await settingsProvider.loadSettings();

  runApp(
    ChangeNotifierProvider.value(
      value: settingsProvider,
      child: const BezaApp(),
    ),
  );
}

// // استخدام feature flags في واجهة المستخدم
class HomeScreen extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final settings = context.watch<SystemSettingsProvider>();

    return Scaffold(
      appBar: AppBar(title: Text(settings.settings?.general.appName ?? 'بيزا')),
      body: ListView(
        children: [
          if (settings.isFeatureEnabled('gold'))
            ListTile(
              leading: Icon(Icons.monetization_on),
              title: Text('تداول الذهب'),
              subtitle: Text('شراء وبيع الذهب'),
            ),
          if (settings.isFeatureEnabled('deals'))
            ListTile(
              leading: Icon(Icons.local_offer),
              title: Text('العروض'),
              subtitle: Text('خصومات وعروض حصرية'),
            ),
          if (settings.isFeatureEnabled('cards'))
            ListTile(
              leading: Icon(Icons.credit_card),
              title: Text('البطاقات'),
              subtitle: Text('بطاقات افتراضية وفيزيائية'),
            ),
        ],
      ),
    );
  }
}

// // عرض رسالة الصيانة
class MaintenanceBanner extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    final settings = context.watch<SystemSettingsProvider>();

    if (!settings.isUnderMaintenance) return SizedBox.shrink();

    return MaterialBanner(
      backgroundColor: Colors.orange.shade100,
      content: Text(
        settings.maintenanceMessage ?? 'نظام بيزا تحت الصيانة',
        style: TextStyle(fontWeight: FontWeight.bold),
      ),
      actions: [
        TextButton(
          onPressed: () {},
          child: Text('حسناً'),
        ),
      ],
    );
  }
}
```

## خدمة الكاش المحلي (Local Cache Service)

```dart
// // ملف: lib/services/cache_service.dart
// // تخزين مؤقت محلي للإعدادات في SharedPreferences

import 'dart:convert';
import 'package:shared_preferences/shared_preferences.dart';
import '../models/system_settings.dart';

class CacheService {
  static const String _settingsKey = 'system_settings';
  static const String _timestampKey = 'system_settings_timestamp';

  /// حفظ الإعدادات في الكاش المحلي
  Future<void> setSystemSettings(SystemSettings settings) async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.setString(_settingsKey, jsonEncode(settings.toJson()));
    await prefs.setInt(_timestampKey, DateTime.now().millisecondsSinceEpoch);
  }

  /// قراءة الإعدادات من الكاش المحلي
  Future<SystemSettings?> getSystemSettings() async {
    final prefs = await SharedPreferences.getInstance();
    final json = prefs.getString(_settingsKey);
    if (json == null) return null;

    // // التحقق من صلاحية الكاش (أقل من 24 ساعة)
    final timestamp = prefs.getInt(_timestampKey) ?? 0;
    final age = DateTime.now().millisecondsSinceEpoch - timestamp;
    if (age > 86400000) return null; // // أكثر من 24 ساعة

    return SystemSettings.fromJson(jsonDecode(json));
  }

  /// مسح كاش الإعدادات
  Future<void> clearSystemSettings() async {
    final prefs = await SharedPreferences.getInstance();
    await prefs.remove(_settingsKey);
    await prefs.remove(_timestampKey);
  }
}
```
