import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/theme/app_theme.dart';
import '../providers/agent_provider.dart';

const _governorates = [
  'دمشق',
  'حلب',
  'حمص',
  'حماة',
  'اللاذقية',
  'طرطوس',
  'إدلب',
  'درعا',
  'السويداء',
  'القنيطرة',
  'دير الزور',
  'الرقة',
  'الحسكة',
];

const _syrianCities = [
  'المركز',
  'المزة',
  'ركن الدين',
  'الصالحية',
  'الميدان',
  'القصاع',
  'باب توما',
  'باب شرقي',
  'القدم',
  'كفر سوسة',
  'دمر',
  'برزة',
  'قابون',
  'جرمانا',
  'داريا',
  'حرستا',
  'دوما',
  'عدرا',
];

const _serviceTypes = [
  'دفع فواتير',
  'تعمير رصيد',
  'تحويل نقدي',
  'دفع حكومي',
  'سحب نقدي',
  'إيداع نقدي',
];

String _formatSyp(int amount) => '${amount ~/ 100} ل.س';

class AgentScreen extends ConsumerStatefulWidget {
  const AgentScreen({super.key});
  @override
  ConsumerState<AgentScreen> createState() => _AgentScreenState();
}

class _AgentScreenState extends ConsumerState<AgentScreen> {
  final _phoneController = TextEditingController();
  String? _selectedGovernorate;
  String? _selectedCity;
  final Set<String> _selectedServices = {};

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(agentProvider.notifier).loadProfile();
    });
  }

  @override
  void dispose() {
    _phoneController.dispose();
    super.dispose();
  }

  Color _statusColor(String? status) {
    switch (status) {
      case 'active':
        return Colors.green;
      case 'pending':
        return Colors.orange;
      case 'suspended':
        return Colors.red;
      default:
        return AppTheme.textSecondary;
    }
  }

  String _statusLabel(String? status) {
    switch (status) {
      case 'active':
        return 'نشط';
      case 'pending':
        return 'قيد المراجعة';
      case 'suspended':
        return 'موقوف';
      default:
        return status ?? 'غير معروف';
    }
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(agentProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('الوكيل')),
      body: state.isLoading
          ? _shimmerLoading()
          : state.error != null && !state.isRegistered
              ? _errorView(state.error!, () => ref.read(agentProvider.notifier).loadProfile())
              : state.isRegistered && state.profile != null
                  ? _dashboard(state)
                  : _registrationForm(state),
    );
  }

  Widget _shimmerLoading() {
    return Shimmer.fromColors(
      baseColor: AppTheme.shimmer,
      highlightColor: Colors.grey[100]!,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: List.generate(4, (_) => Container(
            margin: const EdgeInsets.only(bottom: 12),
            height: 80,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(12),
            ),
          )),
        ),
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

  Widget _dashboard(AgentState state) {
    final profile = state.profile!;
    return RefreshIndicator(
      onRefresh: () async {
        await ref.read(agentProvider.notifier).loadProfile();
        await ref.read(agentProvider.notifier).loadTransactions();
      },
      child: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          Center(
            child: Container(
              padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
              decoration: BoxDecoration(
                color: _statusColor(profile['status'] as String?).withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(20),
              ),
              child: Text(
                _statusLabel(profile['status'] as String?),
                style: TextStyle(
                  fontFamily: 'Cairo',
                  color: _statusColor(profile['status'] as String?),
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ),
          const SizedBox(height: 20),
          Row(
            children: [
              Expanded(
                child: _summaryCard(
                  'العمولة الإجمالية',
                  _formatSyp(profile['total_commission'] as int? ?? 0),
                  Icons.monetization_on,
                  AppTheme.primary,
                ),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: _summaryCard(
                  'الحجم الشهري',
                  _formatSyp(profile['monthly_volume'] as int? ?? 0),
                  Icons.trending_up,
                  AppTheme.accent,
                ),
              ),
            ],
          ),
          const SizedBox(height: 12),
          Row(
            children: [
              _infoChip(Icons.phone, profile['phone'] as String? ?? ''),
              const SizedBox(width: 8),
              _infoChip(Icons.location_on, profile['governorate'] as String? ?? ''),
              const SizedBox(width: 8),
              _infoChip(Icons.location_city, profile['city'] as String? ?? ''),
            ],
          ),
          if (profile['service_types'] != null) ...[
            const SizedBox(height: 16),
            Wrap(
              spacing: 8,
              runSpacing: 8,
              children: (profile['service_types'] as List)
                  .map((s) => Chip(
                        label: Text(s.toString(), style: const TextStyle(fontFamily: 'Cairo', fontSize: 12)),
                        backgroundColor: AppTheme.primaryLight.withValues(alpha: 0.1),
                        side: BorderSide.none,
                      ))
                  .toList(),
            ),
          ],
          const SizedBox(height: 24),
          Row(
            children: [
              Container(
                width: 4,
                height: 20,
                decoration: BoxDecoration(
                  gradient: AppTheme.primaryGradient,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(width: 10),
              const Text(
                'آخر المعاملات',
                style: TextStyle(fontFamily: 'Cairo', fontSize: 18, fontWeight: FontWeight.bold, color: AppTheme.textPrimary),
              ),
            ],
          ),
          const SizedBox(height: 12),
          if (state.transactions.isEmpty)
            Center(
              child: Padding(
                padding: const EdgeInsets.all(32),
                child: Column(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(16),
                      decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
                      child: Icon(Icons.receipt_long_outlined, size: 36, color: AppTheme.textSecondary.withValues(alpha: 0.5)),
                    ),
                    const SizedBox(height: 14),
                    const Text('لا توجد معاملات', style: TextStyle(fontFamily: 'Cairo', fontSize: 15, color: AppTheme.textSecondary)),
                  ],
                ),
              ),
            )
          else
            ...state.transactions.take(10).map((t) => _transactionTile(t)),
        ],
      ),
    );
  }

  Widget _summaryCard(String label, String value, IconData icon, Color color) {
    return Container(
      padding: const EdgeInsets.all(16),
      decoration: AppTheme.cardDecoration,
      child: Column(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: color.withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(icon, color: color, size: 22),
          ),
          const SizedBox(height: 8),
          Text(value, style: TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold, fontSize: 18, color: color)),
          const SizedBox(height: 4),
          Text(label, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 12)),
        ],
      ),
    );
  }

  Widget _infoChip(IconData icon, String text) {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
      decoration: BoxDecoration(
        color: AppTheme.primary.withValues(alpha: 0.08),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Row(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(icon, size: 14, color: AppTheme.textSecondary),
          const SizedBox(width: 6),
          Text(text, style: const TextStyle(fontFamily: 'Cairo', fontSize: 12, color: AppTheme.textSecondary)),
        ],
      ),
    );
  }

  Widget _transactionTile(Map<String, dynamic> txn) {
    final amount = txn['amount'] as int? ?? 0;
    final isCredit = amount >= 0;
    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(
        color: Colors.white,
        borderRadius: BorderRadius.circular(16),
        boxShadow: [
          BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 8, offset: const Offset(0, 2)),
        ],
      ),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(
              color: (isCredit ? AppTheme.success : AppTheme.error).withValues(alpha: 0.1),
              borderRadius: BorderRadius.circular(12),
            ),
            child: Icon(isCredit ? Icons.arrow_circle_up : Icons.arrow_circle_down, color: isCredit ? AppTheme.success : AppTheme.error, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(txn['description'] as String? ?? 'معاملة', style: const TextStyle(fontFamily: 'Cairo', fontSize: 14, fontWeight: FontWeight.w500, color: AppTheme.textPrimary)),
                const SizedBox(height: 4),
                Text(txn['created_at'] as String? ?? '', style: const TextStyle(fontFamily: 'Cairo', fontSize: 12, color: AppTheme.textTertiary)),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Text(
            _formatSyp(amount),
            style: TextStyle(
              fontFamily: 'Cairo',
              fontSize: 15,
              fontWeight: FontWeight.bold,
              color: isCredit ? AppTheme.success : AppTheme.error,
            ),
          ),
        ],
      ),
    );
  }

  Widget _registrationForm(AgentState state) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Icon(Icons.person_pin, size: 72, color: AppTheme.primary),
          const SizedBox(height: 16),
          Text(
            'سجل كوكيل معتمد',
            style: const TextStyle(fontFamily: 'Cairo', fontSize: 20, fontWeight: FontWeight.bold, color: AppTheme.textPrimary),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
          const Text(
            'انضم إلى شبكة وكلائنا واحصل على عمولات',
            style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 32),
          TextField(
            controller: _phoneController,
            decoration: const InputDecoration(
              labelText: 'رقم الهاتف',
              hintText: '09xxxxxxxx',
            ),
            keyboardType: TextInputType.phone,
            textDirection: TextDirection.ltr,
          ),
          const SizedBox(height: 16),
          DropdownButtonFormField<String>(
            initialValue: _selectedGovernorate,
            decoration: const InputDecoration(labelText: 'المحافظة'),
            items: _governorates.map((g) => DropdownMenuItem(value: g, child: Text(g))).toList(),
            onChanged: (v) => setState(() => _selectedGovernorate = v),
          ),
          const SizedBox(height: 16),
          DropdownButtonFormField<String>(
            initialValue: _selectedCity,
            decoration: const InputDecoration(labelText: 'المدينة'),
            items: _syrianCities.map((c) => DropdownMenuItem(value: c, child: Text(c))).toList(),
            onChanged: (v) => setState(() => _selectedCity = v),
          ),
          const SizedBox(height: 16),
          const Text('نوع الخدمة', style: TextStyle(fontFamily: 'Cairo', fontSize: 16, fontWeight: FontWeight.w500, color: AppTheme.textPrimary)),
          const SizedBox(height: 8),
          Wrap(
            spacing: 8,
            runSpacing: 8,
            children: _serviceTypes.map((st) {
              final selected = _selectedServices.contains(st);
              return FilterChip(
                label: Text(st, style: const TextStyle(fontFamily: 'Cairo')),
                selected: selected,
                onSelected: (v) {
                  setState(() {
                    if (v) {
                      _selectedServices.add(st);
                    } else {
                      _selectedServices.remove(st);
                    }
                  });
                },
                selectedColor: AppTheme.primaryLight.withValues(alpha: 0.3),
              );
            }).toList(),
          ),
          const SizedBox(height: 32),
          ElevatedButton(
            onPressed: state.isSubmitting ? null : _submitRegistration,
            child: state.isSubmitting
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                  )
                : const Text('تسجيل'),
          ),
          if (state.error != null) ...[
            const SizedBox(height: 12),
            Text(
              state.error!,
              style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.error),
              textAlign: TextAlign.center,
            ),
          ],
        ],
      ),
    );
  }

  Future<void> _submitRegistration() async {
    if (_phoneController.text.trim().isEmpty ||
        _selectedGovernorate == null ||
        _selectedCity == null ||
        _selectedServices.isEmpty) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يرجى تعبئة جميع الحقول')),
      );
      return;
    }
    final success = await ref.read(agentProvider.notifier).register({
      'phone': _phoneController.text.trim(),
      'governorate': _selectedGovernorate,
      'city': _selectedCity,
      'service_types': _selectedServices.toList(),
    });
    if (success && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تم تقديم الطلب بنجاح')),
      );
    }
  }
}
