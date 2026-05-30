import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/api/api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../services/savings_service.dart';

class SavingsState {
  final List<Map<String, dynamic>> goals;
  final bool isLoading;
  final String? error;

  const SavingsState({
    this.goals = const [],
    this.isLoading = false,
    this.error,
  });

  SavingsState copyWith({
    List<Map<String, dynamic>>? goals,
    bool? isLoading,
    String? error,
  }) {
    return SavingsState(
      goals: goals ?? this.goals,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class SavingsNotifier extends StateNotifier<SavingsState> {
  final SavingsService _service;

  SavingsNotifier(this._service) : super(const SavingsState());

  Future<void> loadGoals() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getGoals();
      if (result['success'] == true) {
        final dataList = (result['data'] as List<dynamic>?)
                ?.cast<Map<String, dynamic>>() ??
            [];
        state = SavingsState(goals: dataList);
      } else {
        state = state.copyWith(
          isLoading: false,
          error: 'فشل تحميل أهداف الادخار',
        );
      }
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: 'فشل تحميل أهداف الادخار',
      );
    }
  }

  Future<void> createGoal({
    required String name,
    required String nameAr,
    required int targetAmount,
    required String targetDate,
    String? category,
    String? icon,
    String? color,
  }) async {
    try {
      final result = await _service.createGoal(
        name: name,
        nameAr: nameAr,
        targetAmount: targetAmount,
        targetDate: targetDate,
        category: category,
        icon: icon,
        color: color,
      );
      if (result['success'] == true) {
        await loadGoals();
      } else {
        state = state.copyWith(error: 'فشل إنشاء هدف الادخار');
      }
    } catch (e) {
      state = state.copyWith(error: 'فشل إنشاء هدف الادخار');
    }
  }

  Future<void> contribute(String id, int amount) async {
    try {
      final result = await _service.contribute(id, amount);
      if (result['success'] == true) {
        await loadGoals();
      } else {
        state = state.copyWith(error: 'فشل الإيداع');
      }
    } catch (e) {
      state = state.copyWith(error: 'فشل الإيداع');
    }
  }

  Future<void> withdraw(String id, int amount, {String? description}) async {
    try {
      final result = await _service.withdraw(id, amount, description);
      if (result['success'] == true) {
        await loadGoals();
      } else {
        state = state.copyWith(error: 'فشل السحب');
      }
    } catch (e) {
      state = state.copyWith(error: 'فشل السحب');
    }
  }
}

final savingsProvider =
    StateNotifierProvider<SavingsNotifier, SavingsState>((ref) {
  final api = ApiClient();
  final service = SavingsService(api);
  return SavingsNotifier(service);
});

class SavingsScreen extends ConsumerStatefulWidget {
  const SavingsScreen({super.key});

  @override
  ConsumerState<SavingsScreen> createState() => _SavingsScreenState();
}

class _SavingsScreenState extends ConsumerState<SavingsScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(savingsProvider.notifier).loadGoals());
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(savingsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('أهداف الادخار')),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showCreateGoalDialog(context),
        backgroundColor: AppTheme.primary,
        child: const Icon(Icons.add, color: Colors.white),
      ),
      body: _buildBody(state),
    );
  }

  Widget _buildBody(SavingsState state) {
    if (state.isLoading && state.goals.isEmpty) {
      return const _ShimmerList();
    }

    if (state.error != null && state.goals.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(20),
                decoration: const BoxDecoration(color: AppTheme.errorLight, shape: BoxShape.circle),
                child: const Icon(Icons.error_outline, size: 40, color: AppTheme.error),
              ),
              const SizedBox(height: 20),
              Text(
                state.error!,
                style: const TextStyle(fontFamily: 'Cairo', fontSize: 16, color: AppTheme.textSecondary),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              ElevatedButton.icon(
                onPressed: () => ref.read(savingsProvider.notifier).loadGoals(),
                icon: const Icon(Icons.refresh, size: 18),
                label: const Text('إعادة المحاولة'),
              ),
            ],
          ),
        ),
      );
    }

    if (state.goals.isEmpty) {
      return Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              Container(
                padding: const EdgeInsets.all(20),
                decoration: BoxDecoration(
                  color: AppTheme.surfaceContainerLow,
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Icon(Icons.savings_outlined, size: 48, color: AppTheme.textSecondary.withValues(alpha: 0.5)),
              ),
              const SizedBox(height: 16),
              const Text(
                'لا توجد أهداف ادخار',
                style: TextStyle(fontFamily: 'Cairo', fontSize: 18, fontWeight: FontWeight.bold, color: AppTheme.textPrimary),
              ),
              const SizedBox(height: 8),
              const Text(
                'اضغط على + لإنشاء هدف ادخار جديد',
                style: TextStyle(fontFamily: 'Cairo', fontSize: 14, color: AppTheme.textSecondary),
              ),
            ],
          ),
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => ref.read(savingsProvider.notifier).loadGoals(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: state.goals.length,
        itemBuilder: (context, index) =>
            _GoalItem(goal: state.goals[index]),
      ),
    );
  }

  void _showCreateGoalDialog(BuildContext context) {
    final nameController = TextEditingController();
    final nameArController = TextEditingController();
    final targetAmountController = TextEditingController();
    String? selectedCategory;
    DateTime? selectedDate;

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          title: const Text('هدف ادخار جديد', style: TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold)),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: nameArController,
                  decoration: const InputDecoration(
                    labelText: 'اسم الهدف (بالعربية)',
                    hintText: 'مثال: شراء سيارة',
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: nameController,
                  decoration: const InputDecoration(
                    labelText: 'اسم الهدف (بالإنجليزية)',
                    hintText: 'Goal name',
                  ),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: targetAmountController,
                  decoration: const InputDecoration(
                    labelText: 'المبلغ المستهدف',
                    hintText: 'قيمة الهدف',
                  ),
                  keyboardType: TextInputType.number,
                ),
                const SizedBox(height: 12),
                DropdownButtonFormField<String>(
                  decoration: const InputDecoration(labelText: 'التصنيف'),
                  items: const [
                    DropdownMenuItem(value: 'travel', child: Text('سفر')),
                    DropdownMenuItem(value: 'education', child: Text('تعليم')),
                    DropdownMenuItem(value: 'emergency', child: Text('طوارئ')),
                    DropdownMenuItem(value: 'housing', child: Text('سكن')),
                    DropdownMenuItem(value: 'vehicle', child: Text('سيارة')),
                    DropdownMenuItem(value: 'other', child: Text('أخرى')),
                  ],
                  onChanged: (v) => selectedCategory = v,
                ),
                const SizedBox(height: 12),
                InkWell(
                  borderRadius: AppTheme.radiusMd,
                  onTap: () async {
                    final picked = await showDatePicker(
                      context: context,
                      initialDate: DateTime.now().add(const Duration(days: 30)),
                      firstDate: DateTime.now(),
                      lastDate: DateTime.now().add(const Duration(days: 365 * 10)),
                    );
                    if (picked != null) {
                      selectedDate = picked;
                      (context as Element).markNeedsBuild();
                    }
                  },
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                    decoration: BoxDecoration(
                      color: AppTheme.surfaceVariant,
                      borderRadius: AppTheme.radiusMd,
                    ),
                    child: Row(
                      children: [
                        const Icon(Icons.calendar_today, color: AppTheme.textSecondary, size: 20),
                        const SizedBox(width: 12),
                        Text(
                          selectedDate != null
                              ? '${selectedDate!.day}/${selectedDate!.month}/${selectedDate!.year}'
                              : 'اختر تاريخ الهدف',
                          style: TextStyle(fontFamily: 'Cairo', color: selectedDate != null ? AppTheme.textPrimary : AppTheme.textSecondary),
                        ),
                      ],
                    ),
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
              onPressed: () {
                if (nameArController.text.trim().isEmpty ||
                    nameController.text.trim().isEmpty ||
                    targetAmountController.text.trim().isEmpty) {
                  return;
                }
                ref.read(savingsProvider.notifier).createGoal(
                      name: nameController.text.trim(),
                      nameAr: nameArController.text.trim(),
                      targetAmount: int.tryParse(targetAmountController.text.trim()) ?? 0,
                      targetDate: selectedDate?.toIso8601String() ?? DateTime.now().toIso8601String(),
                      category: selectedCategory,
                    );
                Navigator.pop(ctx);
              },
              child: const Text('إنشاء'),
            ),
          ],
        ),
      ),
    );
  }

  void _showContributeDialog(BuildContext context, String goalId) {
    final amountController = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          title: const Text('إيداع في الهدف', style: TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold)),
          content: TextField(
            controller: amountController,
            decoration: const InputDecoration(
              labelText: 'المبلغ',
              hintText: 'أدخل المبلغ',
            ),
            keyboardType: TextInputType.number,
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('إلغاء'),
            ),
            ElevatedButton(
              onPressed: () {
                final amount = int.tryParse(amountController.text.trim()) ?? 0;
                if (amount <= 0) return;
                ref.read(savingsProvider.notifier).contribute(goalId, amount);
                Navigator.pop(ctx);
              },
              child: const Text('إيداع'),
            ),
          ],
        ),
      ),
    );
  }

  void _showWithdrawDialog(BuildContext context, String goalId) {
    final amountController = TextEditingController();
    final descController = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          title: const Text('سحب من الهدف', style: TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold)),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: amountController,
                decoration: const InputDecoration(
                  labelText: 'المبلغ',
                  hintText: 'أدخل المبلغ',
                ),
                keyboardType: TextInputType.number,
              ),
              const SizedBox(height: 12),
              TextField(
                controller: descController,
                decoration: const InputDecoration(
                  labelText: 'الوصف (اختياري)',
                  hintText: 'سبب السحب',
                ),
                maxLines: 2,
              ),
            ],
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('إلغاء'),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.error,
              ),
              onPressed: () {
                final amount = int.tryParse(amountController.text.trim()) ?? 0;
                if (amount <= 0) return;
                ref.read(savingsProvider.notifier).withdraw(
                      goalId,
                      amount,
                      description: descController.text.isNotEmpty ? descController.text : null,
                    );
                Navigator.pop(ctx);
              },
              child: const Text('سحب'),
            ),
          ],
        ),
      ),
    );
  }
}

class _GoalItem extends ConsumerStatefulWidget {
  final Map<String, dynamic> goal;

  const _GoalItem({required this.goal});

  @override
  ConsumerState<_GoalItem> createState() => _GoalItemState();
}

class _GoalItemState extends ConsumerState<_GoalItem> {
  bool _expanded = false;

  @override
  Widget build(BuildContext context) {
    final goal = widget.goal;
    final name = goal['name_ar'] as String? ?? goal['name'] as String? ?? '';
    final targetAmount = goal['target_amount'] as int? ?? 0;
    final currentAmount = goal['current_amount'] as int? ?? 0;
    final targetDate = goal['target_date'] as String? ?? '';
    final progress = targetAmount > 0 ? currentAmount / targetAmount : 0.0;
    final percentage = (progress * 100).toStringAsFixed(0);
    final transactions = (goal['transactions'] as List<dynamic>?)
            ?.cast<Map<String, dynamic>>() ??
        [];

    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: AppTheme.cardDecoration,
      child: Column(
        children: [
          InkWell(
            onTap: () => setState(() => _expanded = !_expanded),
            borderRadius: AppTheme.radiusLg,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Row(
                    children: [
                      Container(
                        width: 48,
                        height: 48,
                        decoration: BoxDecoration(
                          color: AppTheme.primary.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(12),
                        ),
                        child: const Icon(Icons.savings, color: AppTheme.primary),
                      ),
                      const SizedBox(width: 12),
                      Expanded(
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Text(
                              name,
                              style: const TextStyle(fontFamily: 'Cairo', fontSize: 16, fontWeight: FontWeight.w600, color: AppTheme.textPrimary),
                            ),
                            const SizedBox(height: 4),
                            Text(
                              'المستهدف: ${_formatAmount(targetAmount)} ل.س',
                              style: const TextStyle(fontFamily: 'Cairo', fontSize: 13, color: AppTheme.textSecondary),
                            ),
                          ],
                        ),
                      ),
                      Container(
                        padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 6),
                        decoration: BoxDecoration(
                          color: AppTheme.primary.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: Text('%$percentage', style: const TextStyle(fontFamily: 'Cairo', fontSize: 14, fontWeight: FontWeight.bold, color: AppTheme.primary)),
                      ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  ClipRRect(
                    borderRadius: BorderRadius.circular(6),
                    child: LinearProgressIndicator(
                      value: progress.clamp(0.0, 1.0),
                      backgroundColor: AppTheme.divider,
                      valueColor: const AlwaysStoppedAnimation<Color>(AppTheme.primary),
                      minHeight: 8,
                    ),
                  ),
                  const SizedBox(height: 8),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: [
                      Text(
                        'تم جمع ${_formatAmount(currentAmount)} ل.س',
                        style: const TextStyle(fontFamily: 'Cairo', fontSize: 12, color: AppTheme.textSecondary),
                      ),
                      if (targetDate.isNotEmpty)
                        Text(
                          targetDate.length >= 10 ? targetDate.substring(0, 10) : targetDate,
                          style: const TextStyle(fontFamily: 'Cairo', fontSize: 12, color: AppTheme.textSecondary),
                        ),
                    ],
                  ),
                ],
              ),
            ),
          ),
          if (_expanded) ...[
            const Divider(height: 1, color: AppTheme.divider),
            Padding(
              padding: const EdgeInsets.all(12),
              child: Row(
                children: [
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {
                        final id = goal['id'] as String;
                        if (id.isNotEmpty) {
                          _showContributeDialog(context, id);
                        }
                      },
                      icon: const Icon(Icons.add_circle_outline, size: 18),
                      label: const Text('إيداع'),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: OutlinedButton.icon(
                      onPressed: () {
                        final id = goal['id'] as String;
                        if (id.isNotEmpty) {
                          _showWithdrawDialog(context, id);
                        }
                      },
                      icon: const Icon(Icons.remove_circle_outline, size: 18),
                      label: const Text('سحب'),
                      style: OutlinedButton.styleFrom(
                        foregroundColor: AppTheme.error,
                        side: const BorderSide(color: AppTheme.error),
                      ),
                    ),
                  ),
                ],
              ),
            ),
            if (transactions.isNotEmpty) ...[
              const Divider(height: 1, color: AppTheme.divider),
              Padding(
                padding: const EdgeInsets.fromLTRB(12, 8, 12, 8),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Row(
                      children: [
                        Container(
                          width: 3,
                          height: 16,
                          decoration: BoxDecoration(
                            gradient: AppTheme.primaryGradient,
                            borderRadius: BorderRadius.circular(2),
                          ),
                        ),
                        const SizedBox(width: 8),
                        const Text('آخر الحركات', style: TextStyle(fontFamily: 'Cairo', fontSize: 13, fontWeight: FontWeight.w600, color: AppTheme.textSecondary)),
                      ],
                    ),
                    const SizedBox(height: 8),
                    ...transactions.take(3).map((tx) => Padding(
                          padding: const EdgeInsets.symmetric(vertical: 4),
                          child: Row(
                            children: [
                              Container(
                                padding: const EdgeInsets.all(6),
                                decoration: BoxDecoration(
                                  color: (tx['type'] == 'contribute' ? Colors.green : Colors.red).withValues(alpha: 0.1),
                                  borderRadius: BorderRadius.circular(8),
                                ),
                                child: Icon(
                                  tx['type'] == 'contribute' ? Icons.arrow_circle_up : Icons.arrow_circle_down,
                                  size: 18,
                                  color: tx['type'] == 'contribute' ? Colors.green : Colors.red,
                                ),
                              ),
                              const SizedBox(width: 8),
                              Expanded(
                                child: Text(
                                  tx['description'] as String? ?? '',
                                  style: const TextStyle(fontFamily: 'Cairo', fontSize: 13, color: AppTheme.textPrimary),
                                  maxLines: 1,
                                  overflow: TextOverflow.ellipsis,
                                ),
                              ),
                              Text(
                                '${_formatAmount(tx['amount'] as int? ?? 0)} ل.س',
                                style: TextStyle(fontFamily: 'Cairo', fontSize: 13, fontWeight: FontWeight.w500, color: tx['type'] == 'contribute' ? Colors.green : Colors.red),
                              ),
                            ],
                          ),
                        )),
                  ],
                ),
              ),
            ],
          ],
        ],
      ),
    );
  }

  void _showContributeDialog(BuildContext context, String goalId) {
    final parent = context.findAncestorStateOfType<_SavingsScreenState>();
    parent?._showContributeDialog(context, goalId);
  }

  void _showWithdrawDialog(BuildContext context, String goalId) {
    final parent = context.findAncestorStateOfType<_SavingsScreenState>();
    parent?._showWithdrawDialog(context, goalId);
  }

  String _formatAmount(int amount) {
    return amount.toString().replaceAllMapped(
          RegExp(r'(\d)(?=(\d{3})+(?!\d))'),
          (match) => '${match[1]},',
        );
  }
}

class _ShimmerList extends StatelessWidget {
  const _ShimmerList();

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: AppTheme.shimmer,
      highlightColor: Colors.grey[100]!,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: 3,
        itemBuilder: (context, index) => Container(
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
          ),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Row(
                children: [
                  Container(
                    width: 48,
                    height: 48,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(12),
                    ),
                  ),
                  const SizedBox(width: 12),
                  Expanded(
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        Container(
                          width: 120,
                          height: 14,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(4),
                          ),
                        ),
                        const SizedBox(height: 6),
                        Container(
                          width: 80,
                          height: 12,
                          decoration: BoxDecoration(
                            color: Colors.white,
                            borderRadius: BorderRadius.circular(4),
                          ),
                        ),
                      ],
                    ),
                  ),
                  Container(
                    width: 40,
                    height: 20,
                    decoration: BoxDecoration(
                      color: Colors.white,
                      borderRadius: BorderRadius.circular(4),
                    ),
                  ),
                ],
              ),
              const SizedBox(height: 12),
              Container(
                width: double.infinity,
                height: 8,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(6),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
