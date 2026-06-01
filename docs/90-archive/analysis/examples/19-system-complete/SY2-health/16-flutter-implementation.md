# 16 - تطبيق Flutter (Flutter Implementation)

**الرمز التشغيلي:** SY2-health  
**النوع:** كود Flutter (Flutter Code)

---

## نظرة عامة (Overview)

يتم عرض المؤشرات الصحية في تطبيق Flutter عبر ويدجت مخصص يعرض حالة كل خدمة بلون مناسب (أخضر/أصفر/أحمر). يستخدم الـ widget التصميم المادي (Material Design) مع دعم الوضع الليلي.

---

## موديل البيانات (Data Model)

```dart
/// موديل نتيجة فحص خدمة واحدة
class ServiceHealth {
  final String name;
  final String status; // up, down, degraded
  final double latencyMs;
  final Map<String, dynamic>? details;
  final String? error;

  ServiceHealth({
    required this.name,
    required this.status,
    this.latencyMs = 0.0,
    this.details,
    this.error,
  });

  factory ServiceHealth.fromJson(Map<String, dynamic> json) {
    return ServiceHealth(
      name: json['name'] as String,
      status: json['status'] as String,
      latencyMs: (json['latency_ms'] as num?)?.toDouble() ?? 0.0,
      details: json['details'] as Map<String, dynamic>?,
      error: json['error'] as String?,
    );
  }

  /// هل الخدمة تعمل بشكل طبيعي
  bool get isUp => status == 'up';

  /// هل الخدمة متدهورة
  bool get isDegraded => status == 'degraded';

  /// هل الخدمة معطلة
  bool get isDown => status == 'down';

  /// اللون المناسب لحالة الخدمة
  Color get statusColor {
    if (isUp) return Colors.green;
    if (isDegraded) return Colors.orange;
    return Colors.red;
  }

  /// الأيقونة المناسبة لحالة الخدمة
  IconData get statusIcon {
    if (isUp) return Icons.check_circle;
    if (isDegraded) return Icons.warning_amber;
    return Icons.error;
  }

  /// اسم الخدمة بالعربية
  String get arabicName {
    switch (name) {
      case 'database':
        return 'قاعدة البيانات';
      case 'redis':
        return 'Redis';
      case 'cache':
        return 'الذاكرة المؤقتة';
      case 'queue':
        return 'قائمة الانتظار';
      case 'storage':
        return 'التخزين';
      case 'php_requirements':
        return 'متطلبات PHP';
      default:
        return name;
    }
  }
}

/// موديل التقرير الصحي الكامل
class HealthReport {
  final String status; // ok, degraded, down
  final List<ServiceHealth> services;
  final String timestamp;
  final bool cached;

  HealthReport({
    required this.status,
    required this.services,
    required this.timestamp,
    this.cached = false,
  });

  factory HealthReport.fromJson(Map<String, dynamic> json) {
    return HealthReport(
      status: json['status'] as String,
      services: (json['services'] as List)
          .map((s) => ServiceHealth.fromJson(s as Map<String, dynamic>))
          .toList(),
      timestamp: json['timestamp'] as String,
      cached: json['cached'] as bool? ?? false,
    );
  }

  Color get overallColor {
    if (status == 'ok') return Colors.green;
    if (status == 'degraded') return Colors.orange;
    return Colors.red;
  }
}
```

---

## خدمة API (API Service)

```dart
import 'dart:convert';
import 'package:http/http.dart' as http;

/// خدمة جلب بيانات التحقق الصحي من API
class HealthApiService {
  static const String _baseUrl = 'https://api.beza.com';
  final http.Client _client;

  HealthApiService({http.Client? client})
      : _client = client ?? http.Client();

  /// جلب التقرير الصحي العام
  Future<HealthReport> getGeneralHealth() async {
    try {
      final response = await _client.get(
        Uri.parse('$_baseUrl/system/health'),
        headers: {'Accept': 'application/json'},
      );

      if (response.statusCode == 200) {
        return HealthReport.fromJson(
          json.decode(response.body) as Map<String, dynamic>,
        );
      }

      throw HealthApiException(
        'فشل جلب التقرير الصحي: ${response.statusCode}',
      );
    } catch (e) {
      if (e is HealthApiException) rethrow;
      throw HealthApiException('خطأ في الاتصال: $e');
    }
  }

  /// جلب التقرير المفصل (للمشرفين)
  Future<HealthReport> getAdminHealth(String token) async {
    try {
      final response = await _client.get(
        Uri.parse('$_baseUrl/admin/system/health'),
        headers: {
          'Accept': 'application/json',
          'Authorization': 'Bearer $token',
        },
      );

      if (response.statusCode == 200) {
        return HealthReport.fromJson(
          json.decode(response.body) as Map<String, dynamic>,
        );
      }

      if (response.statusCode == 401) {
        throw HealthApiException('التوكن غير صالح، يرجى تسجيل الدخول مجدداً');
      }

      if (response.statusCode == 403) {
        throw HealthApiException('ليس لديك صلاحية المشرف');
      }

      throw HealthApiException(
        'فشل جلب التقرير: ${response.statusCode}',
      );
    } catch (e) {
      if (e is HealthApiException) rethrow;
      throw HealthApiException('خطأ في الاتصال: $e');
    }
  }

  void dispose() {
    _client.close();
  }
}

class HealthApiException implements Exception {
  final String message;
  HealthApiException(this.message);

  @override
  String toString() => message;
}
```

---

## ويدجت عرض المؤشرات الصحية (Health Indicator Widget)

```dart
import 'package:flutter/material.dart';

/// ويدجت عرض مؤشر صحي لخدمة واحدة
class HealthIndicatorWidget extends StatelessWidget {
  final ServiceHealth service;

  const HealthIndicatorWidget({super.key, required this.service});

  @override
  Widget build(BuildContext context) {
    return Card(
      margin: const EdgeInsets.symmetric(horizontal: 16, vertical: 4),
      child: ListTile(
        leading: Icon(
          service.statusIcon,
          color: service.statusColor,
          size: 36,
        ),
        title: Text(
          service.arabicName,
          style: const TextStyle(fontWeight: FontWeight.bold),
        ),
        subtitle: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Text(
              service.isUp
                  ? 'يعمل بشكل طبيعي'
                  : service.isDegraded
                      ? 'أداء متدهور'
                      : 'معطل',
              style: TextStyle(color: service.statusColor),
            ),
            if (service.error != null)
              Text(
                service.error!,
                style: const TextStyle(
                  color: Colors.red,
                  fontSize: 12,
                ),
              ),
            if (service.latencyMs > 0)
              Text(
                'زمن الاستجابة: ${service.latencyMs.toStringAsFixed(2)} مللي ثانية',
                style: const TextStyle(fontSize: 12, color: Colors.grey),
              ),
          ],
        ),
        trailing: Container(
          width: 12,
          height: 12,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: service.statusColor,
          ),
        ),
      ),
    );
  }
}

/// ويدجت عرض التقرير الصحي الكامل
class HealthDashboardWidget extends StatefulWidget {
  final String? adminToken;

  const HealthDashboardWidget({super.key, this.adminToken});

  @override
  State<HealthDashboardWidget> createState() => _HealthDashboardWidgetState();
}

class _HealthDashboardWidgetState extends State<HealthDashboardWidget> {
  final HealthApiService _apiService = HealthApiService();
  late Future<HealthReport> _healthFuture;

  @override
  void initState() {
    super.initState();
    _healthFuture = _loadHealthReport();
  }

  Future<HealthReport> _loadHealthReport() async {
    if (widget.adminToken != null) {
      return _apiService.getAdminHealth(widget.adminToken!);
    }
    return _apiService.getGeneralHealth();
  }

  Future<void> _refresh() async {
    setState(() {
      _healthFuture = _loadHealthReport();
    });
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('التحقق الصحي'),
        centerTitle: true,
        actions: [
          IconButton(
            icon: const Icon(Icons.refresh),
            onPressed: _refresh,
          ),
        ],
      ),
      body: FutureBuilder<HealthReport>(
        future: _healthFuture,
        builder: (context, snapshot) {
          if (snapshot.connectionState == ConnectionState.waiting) {
            return const Center(child: CircularProgressIndicator());
          }

          if (snapshot.hasError) {
            return Center(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  const Icon(Icons.cloud_off, size: 64, color: Colors.red),
                  const SizedBox(height: 16),
                  Text(
                    'فشل الاتصال بالخادم',
                    style: Theme.of(context).textTheme.headlineSmall,
                  ),
                  const SizedBox(height: 8),
                  Text(snapshot.error.toString()),
                  const SizedBox(height: 16),
                  ElevatedButton.icon(
                    onPressed: _refresh,
                    icon: const Icon(Icons.refresh),
                    label: const Text('إعادة المحاولة'),
                  ),
                ],
              ),
            );
          }

          final report = snapshot.data!;
          return RefreshIndicator(
            onRefresh: _refresh,
            child: ListView(
              padding: const EdgeInsets.all(16),
              children: [
                // ترجمة: بطاقة الحالة العامة
                Card(
                  color: report.overallColor.withOpacity(0.1),
                  child: Padding(
                    padding: const EdgeInsets.all(20),
                    child: Column(
                      children: [
                        Icon(
                          report.status == 'ok'
                              ? Icons.check_circle
                              : report.status == 'degraded'
                                  ? Icons.warning
                                  : Icons.error,
                          size: 64,
                          color: report.overallColor,
                        ),
                        const SizedBox(height: 8),
                        Text(
                          report.status == 'ok'
                              ? 'النظام يعمل بشكل طبيعي'
                              : report.status == 'degraded'
                                  ? 'النظام بحاجة إلى انتباه'
                                  : 'النظام معطل',
                          style: Theme.of(context)
                              .textTheme
                              .headlineSmall
                              ?.copyWith(color: report.overallColor),
                        ),
                        Text(
                          'آخر فحص: ${_formatTimestamp(report.timestamp)}',
                          style: const TextStyle(color: Colors.grey),
                        ),
                        if (report.cached)
                          const Chip(
                            label: Text('مخزن مؤقتاً'),
                            avatar: Icon(Icons.cached, size: 16),
                          ),
                      ],
                    ),
                  ),
                ),
                const SizedBox(height: 16),

                // ترجمة: بطاقات الخدمات
                ...report.services.map(
                  (service) => HealthIndicatorWidget(service: service),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  String _formatTimestamp(String timestamp) {
    try {
      final date = DateTime.parse(timestamp);
      return '${date.hour}:${date.minute.toString().padLeft(2, '0')}';
    } catch (_) {
      return timestamp;
    }
  }

  @override
  void dispose() {
    _apiService.dispose();
    super.dispose();
  }
}
```

---

## مثال استخدام (Usage Example)

```dart
// ترجمة: استخدام ويدجت التحقق الصحي في التطبيق

import 'package:flutter/material.dart';
import 'widgets/health_dashboard_widget.dart';

class SettingsPage extends StatelessWidget {
  const SettingsPage({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('الإعدادات'),
      ),
      body: ListView(
        children: [
          // ترجمة: قسم التحقق الصحي
          const Padding(
            padding: EdgeInsets.all(16),
            child: Text(
              'حالة النظام',
              style: TextStyle(
                fontSize: 20,
                fontWeight: FontWeight.bold,
              ),
            ),
          ),

          // ترجمة: تضمين ويدجت التحقق الصحي
          SizedBox(
            height: 400,
            child: HealthDashboardWidget(
              adminToken: _getToken(), // null للمستخدم العادي
            ),
          ),

          // ترجمة: أزرار إضافية
          ListTile(
            leading: const Icon(Icons.refresh),
            title: const Text('فحص الآن'),
            onTap: () {
              // ترجمة: تنفيذ فحص فوري
            },
          ),
        ],
      ),
    );
  }

  String? _getToken() {
    // ترجمة: جلب التوكن من shared_preferences أو secure storage
    return null; // مستخدم عادي
  }
}
```

---

## ملخص Flutter Widgets

| الـ Widget | الوظيفة |
|-----------|--------|
| `HealthApiService` | خدمة لجلب البيانات من API |
| `ServiceHealth` | موديل بيانات لنتيجة خدمة |
| `HealthReport` | موديل بيانات للتقرير الكامل |
| `HealthIndicatorWidget` | عرض حالة خدمة واحدة مع لون وأيقونة |
| `HealthDashboardWidget` | لوحة كاملة لعرض جميع الخدمات |
