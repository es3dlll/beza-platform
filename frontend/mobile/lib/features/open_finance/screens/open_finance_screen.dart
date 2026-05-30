import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/theme/app_theme.dart';
import '../providers/open_finance_provider.dart';

class OpenFinanceScreen extends ConsumerStatefulWidget {
  const OpenFinanceScreen({super.key});
  @override
  ConsumerState<OpenFinanceScreen> createState() => _OpenFinanceScreenState();
}

class _OpenFinanceScreenState extends ConsumerState<OpenFinanceScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(openFinanceProvider.notifier).loadApps();
      ref.read(openFinanceProvider.notifier).loadConsents();
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(openFinanceProvider);
    return Scaffold(
      appBar: AppBar(
        title: const Text('الخدمات المصرفية المفتوحة'),
        actions: [
          IconButton(
            icon: const Icon(Icons.add),
            onPressed: () => _showRegisterAppSheet(context),
          ),
        ],
      ),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: SegmentedButton<int>(
              segments: const [
                ButtonSegment(value: 0, label: Text('تطبيقاتي')),
                ButtonSegment(value: 1, label: Text('التصاريح')),
              ],
              selected: {state.selectedTab},
              onSelectionChanged: (v) {
                ref.read(openFinanceProvider.notifier).setTab(v.first);
              },
              style: SegmentedButton.styleFrom(
                selectedBackgroundColor: AppTheme.primary,
                selectedForegroundColor: Colors.white,
              ),
            ),
          ),
          Expanded(
            child: state.selectedTab == 0
                ? _buildApps(state)
                : _buildConsents(state),
          ),
        ],
      ),
    );
  }

  Widget _buildApps(OpenFinanceState state) {
    if (state.isLoading && state.apps.isEmpty) {
      return _shimmerList();
    }
    if (state.error != null && state.apps.isEmpty) {
      return _errorView(state.error!, () => ref.read(openFinanceProvider.notifier).loadApps());
    }
    if (state.apps.isEmpty) {
      return _emptyView('لم تسجل أي تطبيقات بعد', Icons.apps);
    }
    return RefreshIndicator(
      onRefresh: () => ref.read(openFinanceProvider.notifier).loadApps(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: state.apps.length,
        itemBuilder: (_, i) => _appCard(state.apps[i]),
      ),
    );
  }

  Widget _buildConsents(OpenFinanceState state) {
    if (state.isLoading && state.consents.isEmpty) {
      return _shimmerList();
    }
    if (state.error != null && state.consents.isEmpty) {
      return _errorView(state.error!, () => ref.read(openFinanceProvider.notifier).loadConsents());
    }
    if (state.consents.isEmpty) {
      return _emptyView('لا توجد تصاريح نشطة', Icons.shield_outlined);
    }
    return RefreshIndicator(
      onRefresh: () => ref.read(openFinanceProvider.notifier).loadConsents(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: state.consents.length,
        itemBuilder: (_, i) => _consentCard(state.consents[i]),
      ),
    );
  }

  Widget _shimmerList() {
    return Shimmer.fromColors(
      baseColor: AppTheme.shimmer,
      highlightColor: Colors.grey[100]!,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: 5,
        itemBuilder: (_, _) => Container(
          margin: const EdgeInsets.only(bottom: 12),
          height: 100,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(12),
          ),
        ),
      ),
    );
  }

  Widget _emptyView(String message, IconData icon) {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
            child: Icon(icon, size: 36, color: AppTheme.textSecondary.withValues(alpha: 0.5)),
          ),
          const SizedBox(height: 14),
          Text(message, style: const TextStyle(fontFamily: 'Cairo', fontSize: 15, color: AppTheme.textSecondary)),
        ],
      ),
    );
  }

  Widget _errorView(String message, VoidCallback onRetry) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(color: AppTheme.errorLight, shape: BoxShape.circle),
              child: const Icon(Icons.error_outline, size: 40, color: AppTheme.error),
            ),
            const SizedBox(height: 20),
            Text(message, style: const TextStyle(fontFamily: 'Cairo', fontSize: 16, color: AppTheme.textSecondary), textAlign: TextAlign.center),
            const SizedBox(height: 24),
            ElevatedButton.icon(onPressed: onRetry, icon: const Icon(Icons.refresh, size: 18), label: const Text('إعادة المحاولة')),
          ],
        ),
      ),
    );
  }

  Widget _appCard(Map<String, dynamic> app) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: AppTheme.cardDecoration,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: AppTheme.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.apps, size: 22, color: AppTheme.primary),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      app['name'] ?? '',
                      style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w600, fontSize: 15),
                    ),
                    if (app['description'] != null) ...[
                      const SizedBox(height: 2),
                      Text(
                        app['description'].toString(),
                        style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 13),
                      ),
                    ],
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: AppTheme.success.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  app['status'] == 'active' ? 'نشط' : app['status'] ?? '',
                  style: const TextStyle(
                    fontFamily: 'Cairo',
                    color: AppTheme.success,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
          if (app['scopes'] != null) ...[
            const SizedBox(height: 12),
            Wrap(
              spacing: 6,
              runSpacing: 6,
              children: (app['scopes'] as List).map((s) => Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: AppTheme.surfaceContainerLow,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  s.toString(),
                  style: const TextStyle(fontFamily: 'Cairo', fontSize: 11, color: AppTheme.textSecondary),
                ),
              )).toList(),
            ),
          ],
        ],
      ),
    );
  }

  Widget _consentCard(Map<String, dynamic> consent) {
    final isActive = consent['status'] == 'active';
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: AppTheme.cardDecoration,
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              Container(
                padding: const EdgeInsets.all(10),
                decoration: BoxDecoration(
                  color: (isActive ? AppTheme.success : AppTheme.textSecondary).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: Icon(
                  Icons.shield_outlined,
                  size: 22,
                  color: isActive ? AppTheme.success : AppTheme.textSecondary,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Text(
                  consent['app_name'] ?? consent['app_id'] ?? '',
                  style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w600, fontSize: 15),
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: (isActive ? AppTheme.success : AppTheme.error).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  isActive ? 'نشط' : 'ملغي',
                  style: TextStyle(
                    fontFamily: 'Cairo',
                    color: isActive ? AppTheme.success : AppTheme.error,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ],
          ),
          if (consent['scopes'] != null) ...[
            const SizedBox(height: 12),
            Wrap(
              spacing: 6,
              runSpacing: 6,
              children: (consent['scopes'] as List).map((s) => Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: AppTheme.surfaceContainerLow,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  s.toString(),
                  style: const TextStyle(fontFamily: 'Cairo', fontSize: 11, color: AppTheme.textSecondary),
                ),
              )).toList(),
            ),
          ],
          const SizedBox(height: 12),
          if (isActive)
            SizedBox(
              width: double.infinity,
              child: OutlinedButton.icon(
                onPressed: () => _confirmRevoke(consent['id']),
                icon: const Icon(Icons.block, size: 16),
                label: const Text('إلغاء التصريح'),
                style: OutlinedButton.styleFrom(
                  foregroundColor: AppTheme.error,
                  side: const BorderSide(color: AppTheme.error),
                ),
              ),
            ),
        ],
      ),
    );
  }

  void _confirmRevoke(String id) {
    showDialog(
      context: context,
      builder: (_) => AlertDialog(
        title: const Text('إلغاء التصريح'),
        content: const Text('هل أنت متأكد من إلغاء هذا التصريح؟'),
        actions: [
          TextButton(onPressed: () => Navigator.pop(context), child: const Text('إلغاء')),
          TextButton(
            onPressed: () {
              Navigator.pop(context);
              ref.read(openFinanceProvider.notifier).revokeConsent(id);
            },
            child: const Text('تأكيد', style: TextStyle(color: AppTheme.error)),
          ),
        ],
      ),
    );
  }

  void _showRegisterAppSheet(BuildContext context) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => const _RegisterAppSheet(),
    );
  }
}

class _RegisterAppSheet extends ConsumerStatefulWidget {
  const _RegisterAppSheet();
  @override
  ConsumerState<_RegisterAppSheet> createState() => _RegisterAppSheetState();
}

class _RegisterAppSheetState extends ConsumerState<_RegisterAppSheet> {
  final _nameController = TextEditingController();
  final _descriptionController = TextEditingController();
  final _redirectUriController = TextEditingController();
  final _scopeController = TextEditingController();
  final _redirectUris = <String>[];
  final _scopes = <String>[];
  bool _isSubmitting = false;

  @override
  void dispose() {
    _nameController.dispose();
    _descriptionController.dispose();
    _redirectUriController.dispose();
    _scopeController.dispose();
    super.dispose();
  }

  Future<void> _register() async {
    final name = _nameController.text.trim();
    if (name.isEmpty) return;
    if (_redirectUris.isEmpty) return;
    if (_scopes.isEmpty) return;

    setState(() => _isSubmitting = true);
    final result = await ref.read(openFinanceProvider.notifier).registerApp(
      name: name,
      description: _descriptionController.text.trim().isEmpty ? null : _descriptionController.text.trim(),
      redirectUris: List.from(_redirectUris),
      scopes: List.from(_scopes),
    );
    if (!mounted) return;
    setState(() => _isSubmitting = false);
    if (result != null) {
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تم تسجيل التطبيق بنجاح')),
      );
    }
  }

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: EdgeInsets.only(
        left: 24,
        right: 24,
        top: 24,
        bottom: MediaQuery.of(context).viewInsets.bottom + 24,
      ),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Text(
            'تسجيل تطبيق جديد',
            style: TextStyle(fontFamily: 'Cairo', fontSize: 20, fontWeight: FontWeight.bold, color: AppTheme.textPrimary),
          ),
          const SizedBox(height: 20),
          TextField(
            controller: _nameController,
            decoration: const InputDecoration(labelText: 'اسم التطبيق'),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: _descriptionController,
            decoration: const InputDecoration(labelText: 'الوصف (اختياري)'),
            maxLines: 2,
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _redirectUriController,
                  decoration: const InputDecoration(labelText: 'رابط إعادة التوجيه'),
                ),
              ),
              const SizedBox(width: 8),
              IconButton(
                icon: const Icon(Icons.add_circle, color: AppTheme.primary),
                onPressed: () {
                  final uri = _redirectUriController.text.trim();
                  if (uri.isNotEmpty && !_redirectUris.contains(uri)) {
                    setState(() => _redirectUris.add(uri));
                    _redirectUriController.clear();
                  }
                },
              ),
            ],
          ),
          if (_redirectUris.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Wrap(
                spacing: 6,
                runSpacing: 6,
                children: _redirectUris.map((u) => Chip(
                  label: Text(u, style: const TextStyle(fontFamily: 'Cairo', fontSize: 12)),
                  deleteIcon: const Icon(Icons.close, size: 16),
                  onDeleted: () => setState(() => _redirectUris.remove(u)),
                )).toList(),
              ),
            ),
          const SizedBox(height: 12),
          Row(
            children: [
              Expanded(
                child: TextField(
                  controller: _scopeController,
                  decoration: const InputDecoration(labelText: 'الصلاحية'),
                ),
              ),
              const SizedBox(width: 8),
              IconButton(
                icon: const Icon(Icons.add_circle, color: AppTheme.primary),
                onPressed: () {
                  final scope = _scopeController.text.trim();
                  if (scope.isNotEmpty && !_scopes.contains(scope)) {
                    setState(() => _scopes.add(scope));
                    _scopeController.clear();
                  }
                },
              ),
            ],
          ),
          if (_scopes.isNotEmpty)
            Padding(
              padding: const EdgeInsets.only(top: 8),
              child: Wrap(
                spacing: 6,
                runSpacing: 6,
                children: _scopes.map((s) => Chip(
                  label: Text(s, style: const TextStyle(fontFamily: 'Cairo', fontSize: 12)),
                  deleteIcon: const Icon(Icons.close, size: 16),
                  onDeleted: () => setState(() => _scopes.remove(s)),
                )).toList(),
              ),
            ),
          const SizedBox(height: 20),
          ElevatedButton(
            onPressed: _isSubmitting ? null : _register,
            child: _isSubmitting
                ? const SizedBox(width: 20, height: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                : const Text('تسجيل'),
          ),
        ],
      ),
    );
  }
}
