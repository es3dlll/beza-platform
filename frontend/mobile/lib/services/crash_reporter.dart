import 'dart:async';
import 'dart:convert';
import 'dart:io';

import 'package:flutter/foundation.dart';

class CrashReport {
  final String id;
  final String message;
  final String stackTrace;
  final String platform;
  final String version;
  final double? memoryUsageMb;
  final DateTime timestamp;

  CrashReport({
    required this.id,
    required this.message,
    required this.stackTrace,
    required this.platform,
    required this.version,
    this.memoryUsageMb,
    DateTime? timestamp,
  }) : timestamp = timestamp ?? DateTime.now();

  Map<String, dynamic> toJson() => {
        'id': id,
        'message': message,
        'stack_trace': stackTrace,
        'platform': platform,
        'version': version,
        'memory_usage_mb': memoryUsageMb,
        'timestamp': timestamp.toIso8601String(),
      };

  String toJsonString() => json.encode(toJson());
}

class CrashReporter {
  final List<CrashReport> _reports = [];
  bool _initialized = false;
  Timer? _memoryTimer;
  double _currentMemoryMb = 0;

  bool get isInitialized => _initialized;
  int get reportCount => _reports.length;
  List<CrashReport> get reports => List.unmodifiable(_reports);
  double get currentMemoryMb => _currentMemoryMb;

  Future<void> initialize() async {
    if (_initialized) return;

    _initialized = true;

    if (!kReleaseMode) {
      _memoryTimer = Timer.periodic(const Duration(seconds: 30), (_) {
        _sampleMemory();
      });
    }

    PlatformDispatcher.instance.onError = (Object error, StackTrace stack) {
      captureException(error, stack);
      return true;
    };

    FlutterError.onError = (FlutterErrorDetails details) {
      captureException(
        details.exception,
        details.stack ?? StackTrace.current,
        context: details.context?.toDescription(),
      );
    };
  }

  void captureException(Object error, StackTrace stackTrace,
      {String? context}) {
    if (!_initialized) return;

    final report = CrashReport(
      id: DateTime.now().microsecondsSinceEpoch.toString(),
      message: '${error.runtimeType}: ${error.toString()}',
      stackTrace: stackTrace.toString(),
      platform: Platform.operatingSystem,
      version: Platform.operatingSystemVersion,
      memoryUsageMb: _currentMemoryMb,
    );

    _reports.add(report);

    debugPrint('[CrashReporter] تم التقاط خطأ: ${report.message}');
  }

  Future<void> sendPendingReports() async {
    final pending = List<CrashReport>.from(_reports);
    _reports.clear();

    for (final report in pending) {
      debugPrint('[CrashReporter] إرسال تقرير: ${report.id}');
    }
  }

  void dispose() {
    _memoryTimer?.cancel();
    _memoryTimer = null;
    _initialized = false;
  }

  void _sampleMemory() {
    // Simulated memory sampling — real implementation would use platform channels
    _currentMemoryMb = (ProcessInfo.currentRss / 1024 / 1024);
  }
}

// ignore: non_constant_identifier_names
final CrashReporter crashReporter = CrashReporter();
