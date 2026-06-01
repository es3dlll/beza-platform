# 16 - تطبيق Flutter: شاشة إدارة النظام للمشرف (Flutter Admin Management Screen)

<div dir="rtl">

## شاشة إدارة النظام في Flutter

```dart
// lib/screens/admin/system_management_screen.dart

import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import 'package:provider/provider.dart';
import '../../providers/auth_provider.dart';
import '../../services/api_service.dart';
import '../../widgets/loading_overlay.dart';
import '../../widgets/confirmation_dialog.dart';
import '../../models/system_info.dart';
import '../../models/backup.dart';
import '../../models/log_file.dart';
import '../../models/scheduled_task.dart';

/**
 * شاشة إدارة النظام في تطبيق Flutter
 * توفر واجهة للمشرف لإدارة جميع جوانب النظام
 */
class SystemManagementScreen extends StatefulWidget {
  const SystemManagementScreen({super.key});

  @override
  State<SystemManagementScreen> createState() => _SystemManagementScreenState();
}

class _SystemManagementScreenState extends State<SystemManagementScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;
  final ApiService _api = ApiService();
  bool _isLoading = false;
  String? _errorMessage;
  String? _successMessage;

  // بيانات كل تبويب
  SystemInfo? _systemInfo;
  List<Backup> _backups = [];
  List<LogFile> _logFiles = [];
  Map<String, dynamic>? _queueStatus;
  List<ScheduledTask> _scheduledTasks = [];

  @override
  void initState() {
    super.initState();
    // أربعة تبويبات: النظام، النسخ الاحتياطي، السجلات، قائمة الانتظار
    _tabController = TabController(length: 4, vsync: this, initialIndex: 0);
    _loadInitialData();
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  /**
   * تحميل البيانات الأولية
   */
  Future<void> _loadInitialData() async {
    setState(() => _isLoading = true);
    try {
      await Future.wait([
        _loadSystemInfo(),
        _loadBackups(),
        _loadLogFiles(),
        _loadQueueStatus(),
        _loadScheduledTasks(),
      ]);
    } catch (e) {
      setState(() => _errorMessage = 'فشل تحميل البيانات: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  /**
   * دالة مساعدة لإظهار رسالة نجاح
   */
  void _showSuccess(String message) {
    setState(() {
      _successMessage = message;
      _errorMessage = null;
    });
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message, textAlign: TextAlign.right),
        backgroundColor: Colors.green,
        behavior: SnackBarBehavior.floating,
      ),
    );
    Future.delayed(const Duration(seconds: 3), () {
      if (mounted) setState(() => _successMessage = null);
    });
  }

  /**
   * دالة مساعدة لإظهار رسالة خطأ
   */
  void _showError(String message) {
    setState(() {
      _errorMessage = message;
      _successMessage = null;
    });
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Text(message, textAlign: TextAlign.right),
        backgroundColor: Colors.red,
        behavior: SnackBarBehavior.floating,
      ),
    );
  }

  /**
   * تحميل معلومات النظام
   */
  Future<void> _loadSystemInfo() async {
    final response = await _api.get('/admin/system/info');
    _systemInfo = SystemInfo.fromJson(response['data']);
  }

  /**
   * تحميل قائمة النسخ الاحتياطية
   */
  Future<void> _loadBackups() async {
    final response = await _api.get('/admin/system/backup/list');
    _backups = (response['data'] as List)
        .map((e) => Backup.fromJson(e))
        .toList();
  }

  /**
   * تحميل ملفات السجل
   */
  Future<void> _loadLogFiles() async {
    final response = await _api.get('/admin/system/logs');
    _logFiles = (response['data'] as List)
        .map((e) => LogFile.fromJson(e))
        .toList();
  }

  /**
   * تحميل حالة قائمة الانتظار
   */
  Future<void> _loadQueueStatus() async {
    final response = await _api.get('/admin/system/queue/status');
    _queueStatus = response['data'];
  }

  /**
   * تحميل المهام المجدولة
   */
  Future<void> _loadScheduledTasks() async {
    final response = await _api.get('/admin/system/schedule');
    _scheduledTasks = (response['data'] as List)
        .map((e) => ScheduledTask.fromJson(e))
        .toList();
  }

  // ========== عمليات الإدارة ==========

  /**
   * مسح الكاش
   */
  Future<void> _clearCache() async {
    setState(() => _isLoading = true);
    try {
      final response = await _api.post('/admin/system/cache/clear');
      _showSuccess(response['message'] ?? 'تم مسح الكاش بنجاح');
    } catch (e) {
      _showError('فشل مسح الكاش: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  /**
   * تحسين الكاش
   */
  Future<void> _optimizeCache() async {
    setState(() => _isLoading = true);
    try {
      final response = await _api.post('/admin/system/cache/optimize');
      _showSuccess(response['message'] ?? 'تم تحسين الكاش بنجاح');
    } catch (e) {
      _showError('فشل تحسين الكاش: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  /**
   * إنشاء نسخة احتياطية
   */
  Future<void> _createBackup() async {
    setState(() => _isLoading = true);
    try {
      final response = await _api.post('/admin/system/backup');
      _showSuccess(response['message'] ?? 'تم إنشاء النسخة الاحتياطية');
      await _loadBackups();
    } catch (e) {
      _showError('فشل إنشاء النسخة الاحتياطية: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  /**
   * استعادة نسخة احتياطية (تتطلب تأكيد)
   */
  Future<void> _restoreBackup(Backup backup) async {
    // حوار تأكيد خطير
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => ConfirmationDialog(
        title: 'تأكيد استعادة النسخة الاحتياطية',
        message: 'هل أنت متأكد من استعادة قاعدة البيانات من النسخة '
            '${backup.filename}؟\n\n'
            '⚠️ سيتم فقدان جميع التغييرات منذ آخر نسخة احتياطية.',
        confirmText: 'نعم، استعادة',
        cancelText: 'إلغاء',
        isDangerous: true,
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() => _isLoading = true);
    try {
      final response = await _api.post(
        '/admin/system/backup/${backup.filename}/restore',
        body: {'confirm': true},
      );
      _showSuccess(response['message'] ?? 'تم استعادة قاعدة البيانات');
    } catch (e) {
      _showError('فشل استعادة النسخة: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  /**
   * حذف نسخة احتياطية
   */
  Future<void> _deleteBackup(Backup backup) async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => ConfirmationDialog(
        title: 'تأكيد الحذف',
        message: 'هل أنت متأكد من حذف النسخة الاحتياطية ${backup.filename}؟',
        confirmText: 'نعم، حذف',
        cancelText: 'إلغاء',
        isDangerous: true,
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() => _isLoading = true);
    try {
      await _api.delete('/admin/system/backup/${backup.filename}');
      _showSuccess('تم حذف النسخة الاحتياطية');
      await _loadBackups();
    } catch (e) {
      _showError('فشل حذف النسخة: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  /**
   * مسح السجلات
   */
  Future<void> _clearLogs() async {
    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => ConfirmationDialog(
        title: 'تأكيد مسح السجلات',
        message: 'هل أنت متأكد من مسح جميع ملفات السجل؟',
        confirmText: 'نعم، مسح',
        cancelText: 'إلغاء',
        isDangerous: false,
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() => _isLoading = true);
    try {
      await _api.post('/admin/system/log/clear');
      _showSuccess('تم مسح ملفات السجل');
      await _loadLogFiles();
    } catch (e) {
      _showError('فشل مسح السجلات: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  /**
   * إعادة تشغيل قائمة الانتظار
   */
  Future<void> _restartQueue() async {
    setState(() => _isLoading = true);
    try {
      await _api.post('/admin/system/queue/restart');
      _showSuccess('تم إعادة تشغيل عمال قائمة الانتظار');
      await _loadQueueStatus();
    } catch (e) {
      _showError('فشل إعادة التشغيل: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  /**
   * تبديل وضع الصيانة
   */
  Future<void> _toggleMaintenance(bool currentlyEnabled) async {
    final enabled = !currentlyEnabled;
    final action = enabled ? 'تفعيل' : 'تعطيل';

    final confirmed = await showDialog<bool>(
      context: context,
      builder: (ctx) => ConfirmationDialog(
        title: 'تأكيد $action وضع الصيانة',
        message: 'هل أنت متأكد من $action وضع الصيانة؟',
        confirmText: action,
        cancelText: 'إلغاء',
        isDangerous: enabled,
      ),
    );

    if (confirmed != true || !mounted) return;

    setState(() => _isLoading = true);
    try {
      final response = await _api.post(
        '/admin/system/maintenance',
        body: {'enabled': enabled},
      );
      _showSuccess(response['message'] ?? 'تم التبديل بنجاح');
      await _loadSystemInfo();
    } catch (e) {
      _showError('فشل التبديل: $e');
    } finally {
      setState(() => _isLoading = false);
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('إدارة النظام'),
        backgroundColor: Colors.indigo,
        foregroundColor: Colors.white,
        bottom: TabBar(
          controller: _tabController,
          isScrollable: false,
          indicatorColor: Colors.amber,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white60,
          tabs: const [
            Tab(icon: Icon(Icons.info_outline), text: 'النظام'),
            Tab(icon: Icon(Icons.backup), text: 'النسخ الاحتياطي'),
            Tab(icon: Icon(Icons.article), text: 'السجلات'),
            Tab(icon: Icon(Icons.queue), text: 'قائمة الانتظار'),
          ],
        ),
      ),
      body: LoadingOverlay(
        isLoading: _isLoading,
        child: TabBarView(
          controller: _tabController,
          children: [
            _buildSystemTab(),
            _buildBackupTab(),
            _buildLogsTab(),
            _buildQueueTab(),
          ],
        ),
      ),
    );
  }

  // ========== بناء التبويبات ==========

  Widget _buildSystemTab() {
    return RefreshIndicator(
      onRefresh: _loadSystemInfo,
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // بطاقة معلومات النظام
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('معلومات النظام',
                      style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  const Divider(),
                  _infoRow('PHP', _systemInfo?.php?.version ?? '---'),
                  _infoRow('Laravel', _systemInfo?.laravel?.version ?? '---'),
                  _infoRow('البيئة', _systemInfo?.laravel?.environment ?? '---'),
                  _infoRow('Debug', _systemInfo?.laravel?.debugMode ?? '---'),
                  _infoRow('Cache Driver', _systemInfo?.laravel?.cacheDriver ?? '---'),
                  _infoRow('Queue Driver', _systemInfo?.laravel?.queueDriver ?? '---'),
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          // أزرار الإجراءات
          _actionButton(
            icon: Icons.cleaning_services,
            label: 'مسح الكاش',
            color: Colors.orange,
            onPressed: _clearCache,
          ),
          const SizedBox(height: 8),
          _actionButton(
            icon: Icons.speed,
            label: 'تحسين الكاش',
            color: Colors.blue,
            onPressed: _optimizeCache,
          ),
          const SizedBox(height: 8),

          // وضع الصيانة
          Card(
            child: SwitchListTile(
              title: const Text('وضع الصيانة'),
              subtitle: Text(_systemInfo?.laravel?.environment == 'maintenance'
                  ? 'التطبيق في وضع الصيانة'
                  : 'التطبيق يعمل بشكل طبيعي'),
              value: _systemInfo?.laravel?.environment == 'maintenance',
              onChanged: (_) => _toggleMaintenance(
                  _systemInfo?.laravel?.environment == 'maintenance'),
              secondary: Icon(
                _systemInfo?.laravel?.environment == 'maintenance'
                    ? Icons.shield
                    : Icons.shield_outlined,
                color: _systemInfo?.laravel?.environment == 'maintenance'
                    ? Colors.red
                    : Colors.green,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildBackupTab() {
    return RefreshIndicator(
      onRefresh: () async {
        await _loadBackups();
      },
      child: Column(
        children: [
          // زر إنشاء نسخة احتياطية جديدة
          Padding(
            padding: const EdgeInsets.all(16),
            child: SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: _createBackup,
                icon: const Icon(Icons.add),
                label: const Text('إنشاء نسخة احتياطية جديدة'),
                style: ElevatedButton.styleFrom(
                  backgroundColor: Colors.indigo,
                  foregroundColor: Colors.white,
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
              ),
            ),
          ),

          // قائمة النسخ الاحتياطية
          Expanded(
            child: _backups.isEmpty
                ? const Center(child: Text('لا توجد نسخ احتياطية'))
                : ListView.builder(
                    itemCount: _backups.length,
                    itemBuilder: (ctx, i) {
                      final backup = _backups[i];
                      return Card(
                        margin: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 4),
                        child: ListTile(
                          leading: const Icon(Icons.storage, color: Colors.indigo),
                          title: Text(backup.filename,
                              style: const TextStyle(fontSize: 13)),
                          subtitle: Text(
                            '${backup.sizeFormatted} • ${backup.createdAt}',
                          ),
                          trailing: PopupMenuButton(
                            itemBuilder: (ctx) => [
                              const PopupMenuItem(
                                value: 'restore',
                                child: ListTile(
                                  leading: Icon(Icons.restore, color: Colors.orange),
                                  title: Text('استعادة'),
                                ),
                              ),
                              const PopupMenuItem(
                                value: 'delete',
                                child: ListTile(
                                  leading: Icon(Icons.delete, color: Colors.red),
                                  title: Text('حذف'),
                                ),
                              ),
                            ],
                            onSelected: (value) {
                              if (value == 'restore') _restoreBackup(backup);
                              if (value == 'delete') _deleteBackup(backup);
                            },
                          ),
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }

  Widget _buildLogsTab() {
    return RefreshIndicator(
      onRefresh: _loadLogFiles,
      child: Column(
        children: [
          // زر مسح السجلات
          Padding(
            padding: const EdgeInsets.all(16),
            child: SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: _clearLogs,
                icon: const Icon(Icons.delete_sweep),
                label: const Text('مسح جميع ملفات السجل'),
                style: OutlinedButton.styleFrom(
                  foregroundColor: Colors.red,
                  side: const BorderSide(color: Colors.red),
                  padding: const EdgeInsets.symmetric(vertical: 16),
                ),
              ),
            ),
          ),

          // قائمة ملفات السجل
          Expanded(
            child: _logFiles.isEmpty
                ? const Center(child: Text('لا توجد ملفات سجل'))
                : ListView.builder(
                    itemCount: _logFiles.length,
                    itemBuilder: (ctx, i) {
                      final log = _logFiles[i];
                      return Card(
                        margin: const EdgeInsets.symmetric(
                            horizontal: 16, vertical: 4),
                        child: ListTile(
                          leading: const Icon(Icons.article, color: Colors.grey),
                          title: Text(log.name),
                          subtitle: Text(
                            '${log.sizeFormatted} • آخر تعديل: ${log.modified}',
                          ),
                          trailing: const Icon(Icons.chevron_left),
                          onTap: () {
                            // عرض محتوى ملف السجل
                            _showLogContent(log.name);
                          },
                        ),
                      );
                    },
                  ),
          ),
        ],
      ),
    );
  }

  /**
   * عرض محتوى ملف سجل
   */
  void _showLogContent(String filename) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => DraggableScrollableSheet(
        initialChildSize: 0.9,
        builder: (ctx, scrollController) {
          return FutureBuilder(
            future: _api.get('/admin/system/logs/$filename'),
            builder: (ctx, snapshot) {
              if (snapshot.connectionState == ConnectionState.waiting) {
                return const Center(child: CircularProgressIndicator());
              }
              if (snapshot.hasError) {
                return Center(child: Text('خطأ: ${snapshot.error}'));
              }

              final data = snapshot.data;
              final content = data?['data']?['content'] ?? '';
              final lines = content.split('\n');

              return Column(
                children: [
                  AppBar(
                    title: Text(filename),
                    leading: IconButton(
                      icon: const Icon(Icons.close),
                      onPressed: () => Navigator.pop(ctx),
                    ),
                    actions: [
                      IconButton(
                        icon: const Icon(Icons.copy),
                        onPressed: () {
                          Clipboard.setData(ClipboardData(text: content));
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('تم النسخ')),
                          );
                        },
                      ),
                    ],
                  ),
                  Expanded(
                    child: ListView.builder(
                      controller: scrollController,
                      itemCount: lines.length,
                      itemBuilder: (ctx, i) {
                        return Padding(
                          padding: const EdgeInsets.symmetric(
                              horizontal: 8, vertical: 1),
                          child: Text(
                            '[${i + 1}] ${lines[i]}',
                            style: const TextStyle(
                              fontSize: 11,
                              fontFamily: 'monospace',
                            ),
                          ),
                        );
                      },
                    ),
                  ),
                ],
              );
            },
          );
        },
      ),
    );
  }

  Widget _buildQueueTab() {
    return RefreshIndicator(
      onRefresh: () async {
        await Future.wait([
          _loadQueueStatus(),
          _loadScheduledTasks(),
        ]);
      },
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          // حالة قائمة الانتظار
          Card(
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  const Text('حالة قائمة الانتظار',
                      style: TextStyle(fontSize: 18,
                          fontWeight: FontWeight.bold)),
                  const Divider(),
                  _infoRow('Driver', _queueStatus?['driver'] ?? '---'),
                  _infoRow('مهام معلقة',
                      '${_queueStatus?['pending'] ?? 0}'),
                  _infoRow('مهام فاشلة',
                      '${_queueStatus?['failed'] ?? 0}'),
                ],
              ),
            ),
          ),

          const SizedBox(height: 16),

          _actionButton(
            icon: Icons.restart_alt,
            label: 'إعادة تشغيل عمال قائمة الانتظار',
            color: Colors.orange,
            onPressed: _restartQueue,
          ),

          const SizedBox(height: 16),

          // المهام المجدولة
          const Text('المهام المجدولة',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
          const SizedBox(height: 8),
          if (_scheduledTasks.isEmpty)
            const Card(
              child: Padding(
                padding: EdgeInsets.all(16),
                child: Text('لا توجد مهام مجدولة'),
              ),
            )
          else
            ..._scheduledTasks.map((task) => Card(
                  margin: const EdgeInsets.only(bottom: 8),
                  child: ListTile(
                    leading: const Icon(Icons.schedule, color: Colors.teal),
                    title: Text(task.command),
                    subtitle: Text(task.readable),
                  ),
                )),
        ],
      ),
    );
  }

  // ========== دوال مساعدة لبناء الواجهة ==========

  Widget _infoRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }

  Widget _actionButton({
    required IconData icon,
    required String label,
    required Color color,
    required VoidCallback onPressed,
  }) {
    return SizedBox(
      width: double.infinity,
      child: ElevatedButton.icon(
        onPressed: onPressed,
        icon: Icon(icon),
        label: Text(label),
        style: ElevatedButton.styleFrom(
          backgroundColor: color,
          foregroundColor: Colors.white,
          padding: const EdgeInsets.symmetric(vertical: 16),
        ),
      ),
    );
  }
}
```

## نموذج البيانات

```dart
// lib/models/system_info.dart
class SystemInfo {
  final PhpInfo? php;
  final LaravelInfo? laravel;

  SystemInfo.fromJson(Map<String, dynamic> json)
      : php = json['php'] != null ? PhpInfo.fromJson(json['php']) : null,
        laravel = json['laravel'] != null
            ? LaravelInfo.fromJson(json['laravel']) : null;
}

class PhpInfo {
  final String version;
  PhpInfo.fromJson(Map<String, dynamic> json) : version = json['version'];
}

class LaravelInfo {
  final String version;
  final String environment;
  final bool debugMode;
  final String cacheDriver;
  final String queueDriver;

  LaravelInfo.fromJson(Map<String, dynamic> json)
      : version = json['version'],
        environment = json['environment'],
        debugMode = json['debug_mode'],
        cacheDriver = json['cache_driver'],
        queueDriver = json['queue_driver'];
}

// lib/models/backup.dart
class Backup {
  final String filename;
  final int size;
  final String sizeFormatted;
  final String createdAt;

  Backup.fromJson(Map<String, dynamic> json)
      : filename = json['filename'],
        size = json['size'],
        sizeFormatted = json['size_formatted'],
        createdAt = json['created_at'];
}

// lib/models/log_file.dart
class LogFile {
  final String name;
  final String sizeFormatted;
  final String modified;

  LogFile.fromJson(Map<String, dynamic> json)
      : name = json['name'],
        sizeFormatted = json['size_formatted'],
        modified = json['modified'];
}

// lib/models/scheduled_task.dart
class ScheduledTask {
  final String command;
  final String readable;

  ScheduledTask.fromJson(Map<String, dynamic> json)
      : command = json['command'],
        readable = json['readable'];
}
```

</div>
