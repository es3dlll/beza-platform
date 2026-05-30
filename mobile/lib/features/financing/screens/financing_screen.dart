import 'dart:ui' as ui show TextDirection;
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/api/api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../services/financing_service.dart';

class FinancingState {
  final List<Map<String, dynamic>> products;
  final List<Map<String, dynamic>> myLoans;
  final bool isLoading;
  final String? error;
  final int tabIndex;

  const FinancingState({
    this.products = const [],
    this.myLoans = const [],
    this.isLoading = false,
    this.error,
    this.tabIndex = 0,
  });

  FinancingState copyWith({
    List<Map<String, dynamic>>? products,
    List<Map<String, dynamic>>? myLoans,
    bool? isLoading,
    String? error,
    int? tabIndex,
  }) {
    return FinancingState(
      products: products ?? this.products,
      myLoans: myLoans ?? this.myLoans,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      tabIndex: tabIndex ?? this.tabIndex,
    );
  }
}

class FinancingNotifier extends StateNotifier<FinancingState> {
  final FinancingService _service;

  FinancingNotifier(this._service) : super(const FinancingState());

  Future<void> loadProducts() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getProducts();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(products: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل منتجات التمويل');
    }
  }

  Future<void> loadMyLoans() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getMyLoans();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(myLoans: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل قروضي');
    }
  }

  Future<bool> apply({
    required String productId,
    required int amount,
    required int termDays,
    String? purpose,
  }) async {
    try {
      await _service.apply(
        productId: productId,
        amount: amount,
        termDays: termDays,
        purpose: purpose,
      );
      return true;
    } catch (e) {
      state = state.copyWith(error: 'فشل تقديم الطلب');
      return false;
    }
  }

  Future<bool> repay(String id, {required int amount}) async {
    try {
      final result = await _service.repay(id, amount: amount);
      final updated = result['data'] as Map<String, dynamic>? ?? {};
      final loans = state.myLoans.map((l) {
        return l['id'] == id ? updated : l;
      }).toList();
      state = state.copyWith(myLoans: loans);
      return true;
    } catch (e) {
      state = state.copyWith(error: 'فشل عملية السداد');
      return false;
    }
  }

  void setTab(int index) => state = state.copyWith(tabIndex: index);

  void clearError() => state = state.copyWith(error: null);
}

final financingProvider =
    StateNotifierProvider<FinancingNotifier, FinancingState>((ref) {
  final api = ApiClient();
  final service = FinancingService(api);
  return FinancingNotifier(service);
});

class FinancingScreen extends ConsumerStatefulWidget {
  const FinancingScreen({super.key});
  @override
  ConsumerState<FinancingScreen> createState() => _FinancingScreenState();
}

class _FinancingScreenState extends ConsumerState<FinancingScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(() {
      final index = _tabController.index;
      ref.read(financingProvider.notifier).setTab(index);
      if (index == 1) {
        ref.read(financingProvider.notifier).loadMyLoans();
      }
    });
    Future.microtask(() {
      ref.read(financingProvider.notifier).loadProducts();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(financingProvider);
    return Scaffold(
      appBar: AppBar(
        title: const Text('التمويل'),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'المنتجات'),
            Tab(text: 'قروضي'),
          ],
        ),
      ),
      body: state.error != null
          ? Center(
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
                    Text(state.error!, style: const TextStyle(fontFamily: 'Cairo', fontSize: 16, color: AppTheme.textSecondary), textAlign: TextAlign.center),
                    const SizedBox(height: 24),
                    ElevatedButton.icon(
                      onPressed: () {
                        ref.read(financingProvider.notifier).clearError();
                        ref.read(financingProvider.notifier).loadProducts();
                      },
                      icon: const Icon(Icons.refresh, size: 18),
                      label: const Text('إعادة المحاولة'),
                    ),
                  ],
                ),
              ),
            )
          : TabBarView(
              controller: _tabController,
              children: [
                _ProductsTab(state: state, ref: ref),
                _MyLoansTab(state: state, ref: ref),
              ],
            ),
    );
  }
}

class _ProductsTab extends StatelessWidget {
  final FinancingState state;
  final WidgetRef ref;
  const _ProductsTab({required this.state, required this.ref});

  Widget _shimmerList() {
    return Shimmer.fromColors(
      baseColor: AppTheme.shimmer,
      highlightColor: Colors.grey[100]!,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: 4,
        itemBuilder: (_, _) => Container(
          margin: const EdgeInsets.only(bottom: 12),
          height: 200,
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
          ),
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    if (state.isLoading && state.products.isEmpty) {
      return _shimmerList();
    }
    if (state.products.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
              child: Icon(Icons.credit_card_outlined, size: 36, color: AppTheme.textSecondary.withValues(alpha: 0.5)),
            ),
            const SizedBox(height: 14),
            const Text('لا توجد منتجات تمويل', style: TextStyle(fontFamily: 'Cairo', fontSize: 15, color: AppTheme.textSecondary)),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: () => ref.read(financingProvider.notifier).loadProducts(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: state.products.length,
        itemBuilder: (context, index) {
          final product = state.products[index];
          return Container(
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(16),
            decoration: AppTheme.cardDecoration,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Expanded(
                      child: Text(
                        product['name_ar'] ?? product['name'] ?? '',
                        style: const TextStyle(fontFamily: 'Cairo', fontSize: 18, fontWeight: FontWeight.bold),
                      ),
                    ),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: AppTheme.primaryLight.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(8),
                      ),
                      child: Text(
                        '${product['interest_rate'] ?? 0}%',
                        style: const TextStyle(
                          fontFamily: 'Cairo',
                          color: AppTheme.primary,
                          fontWeight: FontWeight.bold,
                          fontSize: 13,
                        ),
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 12),
                Row(
                  children: [
                    Expanded(
                      child: _InfoRow(
                        label: 'الحد الأدنى',
                        value: '${NumberFormat('#,###').format(product['min_amount'])} ل.س',
                      ),
                    ),
                    Expanded(
                      child: _InfoRow(
                        label: 'الحد الأقصى',
                        value: '${NumberFormat('#,###').format(product['max_amount'])} ل.س',
                      ),
                    ),
                  ],
                ),
                const SizedBox(height: 8),
                Row(
                  children: [
                    Expanded(
                      child: _InfoRow(
                        label: 'أقل مدة',
                        value: '${product['min_term_days'] ?? 0} يوم',
                      ),
                    ),
                    Expanded(
                      child: _InfoRow(
                        label: 'أقصى مدة',
                        value: '${product['max_term_days'] ?? 0} يوم',
                      ),
                    ),
                  ],
                ),
                if (product['required_documents'] != null && (product['required_documents'] as List).isNotEmpty) ...[
                  const SizedBox(height: 8),
                  Wrap(
                    spacing: 4,
                    children: (product['required_documents'] as List).map((doc) {
                      return Chip(
                        label: Text(doc.toString(), style: const TextStyle(fontFamily: 'Cairo', fontSize: 11)),
                        materialTapTargetSize: MaterialTapTargetSize.shrinkWrap,
                        visualDensity: VisualDensity.compact,
                      );
                    }).toList(),
                  ),
                ],
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: () => _showApplyDialog(context, product),
                    child: const Text('تقديم طلب'),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }

  void _showApplyDialog(BuildContext context, Map<String, dynamic> product) {
    final amountCtrl = TextEditingController();
    final termCtrl = TextEditingController();
    final purposeCtrl = TextEditingController();

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: ui.TextDirection.rtl,
        child: AlertDialog(
          title: Text('طلب تمويل - ${product['name_ar'] ?? product['name'] ?? ''}'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: amountCtrl,
                  decoration: const InputDecoration(
                    labelText: 'المبلغ (ل.س)',
                    prefixIcon: Icon(Icons.monetization_on_outlined),
                  ),
                  keyboardType: TextInputType.number,
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: termCtrl,
                  decoration: const InputDecoration(
                    labelText: 'المدة (أيام)',
                    prefixIcon: Icon(Icons.calendar_today),
                  ),
                  keyboardType: TextInputType.number,
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: purposeCtrl,
                  decoration: const InputDecoration(
                    labelText: 'الغرض (اختياري)',
                    prefixIcon: Icon(Icons.description_outlined),
                  ),
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('إلغاء'),
            ),
            ElevatedButton(
              onPressed: () async {
                final amount = int.tryParse(amountCtrl.text) ?? 0;
                final term = int.tryParse(termCtrl.text) ?? 0;
                if (amount <= 0 || term <= 0) return;
                final success = await ref.read(financingProvider.notifier).apply(
                  productId: product['id'],
                  amount: amount,
                  termDays: term,
                  purpose: purposeCtrl.text.isEmpty ? null : purposeCtrl.text,
                );
                if (ctx.mounted) Navigator.pop(ctx);
                if (success && context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('تم تقديم الطلب بنجاح')),
                  );
                }
              },
              child: const Text('تقديم'),
            ),
          ],
        ),
      ),
    );
  }
}

class _InfoRow extends StatelessWidget {
  final String label;
  final String value;
  const _InfoRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(label, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 12)),
        const SizedBox(height: 2),
        Text(value, style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w500, fontSize: 14)),
      ],
    );
  }
}

class _MyLoansTab extends StatelessWidget {
  final FinancingState state;
  final WidgetRef ref;
  const _MyLoansTab({required this.state, required this.ref});

  @override
  Widget build(BuildContext context) {
    if (state.isLoading && state.myLoans.isEmpty) {
      return Shimmer.fromColors(
        baseColor: AppTheme.shimmer,
        highlightColor: Colors.grey[100]!,
        child: ListView.builder(
          padding: const EdgeInsets.all(16),
          itemCount: 3,
          itemBuilder: (_, _) => Container(
            margin: const EdgeInsets.only(bottom: 12),
            height: 160,
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: BorderRadius.circular(16),
            ),
          ),
        ),
      );
    }
    if (state.myLoans.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
              child: Icon(Icons.credit_card_off_outlined, size: 36, color: AppTheme.textSecondary.withValues(alpha: 0.5)),
            ),
            const SizedBox(height: 14),
            const Text('لا توجد قروض', style: TextStyle(fontFamily: 'Cairo', fontSize: 15, color: AppTheme.textSecondary)),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: () => ref.read(financingProvider.notifier).loadMyLoans(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: state.myLoans.length,
        itemBuilder: (context, index) {
          final loan = state.myLoans[index];
          final principal = loan['principal'] as int? ?? 0;
          final outstanding = loan['outstanding_balance'] as int? ?? 0;
          final totalRepayable = loan['total_repayable'] as int? ?? 0;
          final progress = totalRepayable > 0
              ? (totalRepayable - outstanding) / totalRepayable
              : 0.0;
          final status = loan['status'] as String? ?? '';
          return Container(
            margin: const EdgeInsets.only(bottom: 12),
            padding: const EdgeInsets.all(16),
            decoration: AppTheme.cardDecoration,
            child: InkWell(
              borderRadius: AppTheme.radiusLg,
              onTap: () => _showLoanDetail(context, loan),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          '${NumberFormat('#,###').format(principal)} ل.س',
                          style: const TextStyle(fontFamily: 'Cairo', fontSize: 18, fontWeight: FontWeight.bold),
                        ),
                      ),
                      _StatusBadge(status: status),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    children: [
                      _InfoRow(label: 'المتبقي', value: '${NumberFormat('#,###').format(outstanding)} ل.س'),
                      const SizedBox(width: 24),
                      _InfoRow(label: 'المدة', value: '${loan['term_days'] ?? 0} يوم'),
                    ],
                  ),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      const Text('نسبة السداد', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 12)),
                      Text('${(progress * 100).toInt()}%', style: const TextStyle(fontFamily: 'Cairo', fontSize: 12)),
                    ],
                  ),
                  const SizedBox(height: 4),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(4),
                    child: LinearProgressIndicator(
                      value: progress,
                      backgroundColor: AppTheme.divider,
                      valueColor: AlwaysStoppedAnimation<Color>(
                        progress >= 1.0 ? AppTheme.success : AppTheme.primary,
                      ),
                      minHeight: 6,
                    ),
                  ),
                ],
              ),
            ),
          );
        },
      ),
    );
  }

  void _showLoanDetail(BuildContext context, Map<String, dynamic> loan) {
    final amountCtrl = TextEditingController();
    final outstanding = loan['outstanding_balance'] as int? ?? 0;
    final status = loan['status'] as String? ?? '';
    final isActive = status == 'active' || status == 'approved';

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: ui.TextDirection.rtl,
        child: StatefulBuilder(
          builder: (ctx, setDialogState) {
            return AlertDialog(
              title: const Text('تفاصيل القرض'),
              content: SizedBox(
                width: double.maxFinite,
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _DetailRow(label: 'المبلغ الأساسي', value: '${NumberFormat('#,###').format(loan['principal'])} ل.س'),
                      _DetailRow(label: 'الإجمالي المستحق', value: '${NumberFormat('#,###').format(loan['total_repayable'])} ل.س'),
                      _DetailRow(label: 'المتبقي', value: '${NumberFormat('#,###').format(outstanding)} ل.س'),
                      _DetailRow(label: 'نسبة الفائدة', value: '${loan['interest_rate'] ?? 0}%'),
                      _DetailRow(label: 'المدة', value: '${loan['term_days'] ?? 0} يوم'),
                      if (loan['purpose'] != null)
                        _DetailRow(label: 'الغرض', value: loan['purpose']),
                      _DetailRow(label: 'الحالة', value: loan['status'] ?? ''),
                      const SizedBox(height: 16),
                      if (isActive) ...[
                        const Text('سداد:', style: TextStyle(fontWeight: FontWeight.bold)),
                        const SizedBox(height: 8),
                        TextField(
                          controller: amountCtrl,
                          decoration: InputDecoration(
                            labelText: 'المبلغ (ل.س)',
                            prefixIcon: const Icon(Icons.monetization_on_outlined),
                          ),
                          keyboardType: TextInputType.number,
                        ),
                        const SizedBox(height: 12),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: () async {
                              final amount = int.tryParse(amountCtrl.text) ?? 0;
                              if (amount <= 0 || amount > outstanding) return;
                              final success = await ref.read(financingProvider.notifier).repay(
                                loan['id'],
                                amount: amount,
                              );
                              if (ctx.mounted) Navigator.pop(ctx);
                              if (success && context.mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('تم السداد بنجاح')),
                                );
                              }
                            },
                            child: const Text('دفع'),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () => Navigator.pop(ctx),
                  child: const Text('إغلاق'),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _DetailRow extends StatelessWidget {
  final String label;
  final String value;
  const _DetailRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
          Text(value, style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w500)),
        ],
      ),
    );
  }
}

class _StatusBadge extends StatelessWidget {
  final String status;
  const _StatusBadge({required this.status});

  @override
  Widget build(BuildContext context) {
    Color color;
    String text;
    switch (status) {
      case 'approved':
        color = Colors.green;
        text = 'معتمد';
        break;
      case 'active':
        color = Colors.blue;
        text = 'نشط';
        break;
      case 'pending':
        color = Colors.orange;
        text = 'قيد المراجعة';
        break;
      case 'rejected':
        color = Colors.red;
        text = 'مرفوض';
        break;
      case 'paid':
      case 'completed':
        color = Colors.grey;
        text = 'مسدد';
        break;
      default:
        color = AppTheme.textSecondary;
        text = status;
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
        border: Border.all(color: color.withValues(alpha: 0.3)),
      ),
      child: Text(text, style: TextStyle(fontFamily: 'Cairo', color: color, fontSize: 12, fontWeight: FontWeight.w600)),
    );
  }
}
