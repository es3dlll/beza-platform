import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/theme/app_theme.dart';
import '../providers/gov_collections_provider.dart';

String _formatSyp(int amount) => '${amount ~/ 100} ل.س';

class GovCollectionsScreen extends ConsumerStatefulWidget {
  const GovCollectionsScreen({super.key});
  @override
  ConsumerState<GovCollectionsScreen> createState() => _GovCollectionsScreenState();
}

class _GovCollectionsScreenState extends ConsumerState<GovCollectionsScreen> {
  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(govCollectionsProvider.notifier).loadProviders();
      ref.read(govCollectionsProvider.notifier).loadHistory();
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(govCollectionsProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('التحصيلات الحكومية')),
      body: Column(
        children: [
          Padding(
            padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
            child: SegmentedButton<int>(
              segments: const [
                ButtonSegment(value: 0, label: Text('سجل المدفوعات')),
                ButtonSegment(value: 1, label: Text('الخدمات')),
              ],
              selected: {state.selectedTab},
              onSelectionChanged: (v) {
                ref.read(govCollectionsProvider.notifier).setTab(v.first);
              },
              style: SegmentedButton.styleFrom(
                selectedBackgroundColor: AppTheme.primary,
                selectedForegroundColor: Colors.white,
              ),
            ),
          ),
          Expanded(
            child: state.selectedTab == 0
                ? _buildHistory(state)
                : _buildProviders(state),
          ),
        ],
      ),
    );
  }

  Widget _buildHistory(GovCollectionsState state) {
    if (state.isLoading && state.payments.isEmpty) {
      return _shimmerList();
    }
    if (state.error != null && state.payments.isEmpty) {
      return _errorView(state.error!, () => ref.read(govCollectionsProvider.notifier).loadHistory());
    }
    if (state.payments.isEmpty) {
      return _emptyView('لا توجد مدفوعات حكومية', Icons.account_balance);
    }
    return RefreshIndicator(
      onRefresh: () => ref.read(govCollectionsProvider.notifier).loadHistory(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: state.payments.length,
        itemBuilder: (_, i) => _paymentCard(state.payments[i]),
      ),
    );
  }

  Widget _buildProviders(GovCollectionsState state) {
    if (state.isLoading && state.providers.isEmpty) {
      return _shimmerGrid();
    }
    if (state.error != null && state.providers.isEmpty) {
      return _errorView(state.error!, () => ref.read(govCollectionsProvider.notifier).loadProviders());
    }
    if (state.providers.isEmpty) {
      return _emptyView('لا توجد خدمات حكومية متاحة', Icons.miscellaneous_services);
    }
    return RefreshIndicator(
      onRefresh: () => ref.read(govCollectionsProvider.notifier).loadProviders(),
      child: GridView.builder(
        padding: const EdgeInsets.all(16),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 1.1,
        ),
        itemCount: state.providers.length,
        itemBuilder: (_, i) => _providerCard(state.providers[i]),
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

  Widget _shimmerGrid() {
    return Shimmer.fromColors(
      baseColor: AppTheme.shimmer,
      highlightColor: Colors.grey[100]!,
      child: GridView.builder(
        padding: const EdgeInsets.all(16),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          mainAxisSpacing: 12,
          crossAxisSpacing: 12,
          childAspectRatio: 1.1,
        ),
        itemCount: 4,
        itemBuilder: (_, _) => Container(
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

  Widget _paymentCard(Map<String, dynamic> payment) {
    final status = payment['status'] as String? ?? '';
    final isPaid = status == 'paid' || status == 'completed';
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      padding: const EdgeInsets.all(16),
      decoration: AppTheme.cardDecoration,
      child: Row(
        children: [
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Text(
                  payment['provider_name'] as String? ?? payment['provider_code'] as String? ?? '',
                  style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w600, fontSize: 15),
                ),
                const SizedBox(height: 4),
                Text(
                  'المرجع: ${payment['reference_number'] ?? ''}',
                  style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 13),
                ),
                const SizedBox(height: 4),
                Text(
                  'المبلغ: ${_formatSyp(payment['amount'] as int? ?? 0)}',
                  style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w500, fontSize: 14),
                ),
              ],
            ),
          ),
          Column(
            crossAxisAlignment: CrossAxisAlignment.end,
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: (isPaid ? AppTheme.success : AppTheme.warning).withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  isPaid ? 'مدفوع' : 'قيد الانتظار',
                  style: TextStyle(
                    fontFamily: 'Cairo',
                    color: isPaid ? AppTheme.success : AppTheme.warning,
                    fontSize: 12,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
              const SizedBox(height: 8),
              Text(
                payment['paid_at'] as String? ?? payment['created_at'] as String? ?? '',
                style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 11),
              ),
            ],
          ),
        ],
      ),
    );
  }

  Widget _providerCard(Map<String, dynamic> provider) {
    return Container(
      decoration: AppTheme.cardDecoration,
      child: InkWell(
        borderRadius: AppTheme.radiusLg,
        onTap: () => _showInquirySheet(context, provider),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(
                  color: AppTheme.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.account_balance, size: 28, color: AppTheme.primary),
              ),
              const SizedBox(height: 12),
              Text(
                provider['name_ar'] as String? ?? provider['name'] as String? ?? '',
                textAlign: TextAlign.center,
                style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w600, fontSize: 14),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _showInquirySheet(BuildContext context, Map<String, dynamic> provider) {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => _InquirySheet(provider: provider),
    );
  }
}

class _InquirySheet extends ConsumerStatefulWidget {
  final Map<String, dynamic> provider;
  const _InquirySheet({required this.provider});

  @override
  ConsumerState<_InquirySheet> createState() => _InquirySheetState();
}

class _InquirySheetState extends ConsumerState<_InquirySheet> {
  final _referenceController = TextEditingController();
  bool _isInquiring = false;
  bool _isPaying = false;
  Map<String, dynamic>? _inquiryResult;
  String? _error;

  @override
  void dispose() {
    _referenceController.dispose();
    super.dispose();
  }

  Future<void> _inquire() async {
    final reference = _referenceController.text.trim();
    if (reference.isEmpty) {
      setState(() => _error = 'يرجى إدخال الرقم المرجعي');
      return;
    }
    setState(() {
      _isInquiring = true;
      _error = null;
      _inquiryResult = null;
    });
    final result = await ref.read(govCollectionsProvider.notifier).inquire(
      providerCode: widget.provider['code'] ?? widget.provider['id'],
      referenceNumber: reference,
    );
    if (!mounted) return;
    setState(() {
      _isInquiring = false;
      if (result != null && result['id'] != null) {
        _inquiryResult = result;
      } else {
        _error = 'فشل الاستعلام، تأكد من الرقم المرجعي';
      }
    });
  }

  Future<void> _pay() async {
    if (_inquiryResult == null) return;
    setState(() {
      _isPaying = true;
      _error = null;
    });
    final result = await ref.read(govCollectionsProvider.notifier).pay(
      paymentId: _inquiryResult!['id'],
    );
    if (!mounted) return;
    setState(() => _isPaying = false);
    if (result != null) {
      Navigator.pop(context);
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تم الدفع بنجاح')),
      );
    } else {
      setState(() => _error = 'فشلت عملية الدفع');
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
          Text(
            widget.provider['name_ar'] as String? ?? widget.provider['name'] as String? ?? '',
            style: const TextStyle(fontFamily: 'Cairo', fontSize: 20, fontWeight: FontWeight.bold, color: AppTheme.textPrimary),
          ),
          const SizedBox(height: 8),
          Text(
            widget.provider['reference_label'] as String? ?? 'الرقم المرجعي',
            style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary),
          ),
          const SizedBox(height: 16),
          if (_inquiryResult == null) ...[
            TextField(
              controller: _referenceController,
              decoration: const InputDecoration(
                labelText: 'الرقم المرجعي',
                hintText: 'أدخل الرقم المرجعي للمعاملة',
              ),
              textDirection: TextDirection.ltr,
              keyboardType: TextInputType.text,
            ),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: _isInquiring ? null : _inquire,
              child: _isInquiring
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Text('استعلام'),
            ),
          ] else ...[
            _resultRow('اسم المكلف', _inquiryResult!['taxpayer_name']?.toString() ?? ''),
            const Divider(height: 20),
            _resultRow(
              'المبلغ المستحق',
              _formatSyp(_inquiryResult!['amount_due'] as int? ?? 0),
            ),
            const Divider(height: 20),
            _resultRow('المرجع', _inquiryResult!['reference_number']?.toString() ?? ''),
            if (_inquiryResult!['due_date'] != null) ...[
              const Divider(height: 20),
              _resultRow('تاريخ الاستحقاق', _inquiryResult!['due_date'].toString()),
            ],
            const SizedBox(height: 20),
            ElevatedButton(
              onPressed: _isPaying ? null : _pay,
              style: ElevatedButton.styleFrom(backgroundColor: AppTheme.primary),
              child: _isPaying
                  ? const SizedBox(
                      width: 20,
                      height: 20,
                      child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white),
                    )
                  : const Text('دفع'),
            ),
          ],
          if (_error != null) ...[
            const SizedBox(height: 12),
            Text(
              _error!,
              style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.error, fontSize: 14),
              textAlign: TextAlign.center,
            ),
          ],
        ],
      ),
    );
  }

  Widget _resultRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
        Text(value, style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w600)),
      ],
    );
  }
}
