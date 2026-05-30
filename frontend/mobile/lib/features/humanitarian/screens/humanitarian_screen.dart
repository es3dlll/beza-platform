import 'dart:ui' as ui show TextDirection;
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/api/api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../services/humanitarian_service.dart';

class HumanitarianState {
  final List<Map<String, dynamic>> organizations;
  final List<Map<String, dynamic>> programs;
  final List<Map<String, dynamic>> disbursements;
  final String? selectedOrgId;
  final bool isLoading;
  final String? error;
  final int tabIndex;

  const HumanitarianState({
    this.organizations = const [],
    this.programs = const [],
    this.disbursements = const [],
    this.selectedOrgId,
    this.isLoading = false,
    this.error,
    this.tabIndex = 0,
  });

  HumanitarianState copyWith({
    List<Map<String, dynamic>>? organizations,
    List<Map<String, dynamic>>? programs,
    List<Map<String, dynamic>>? disbursements,
    String? selectedOrgId,
    bool? isLoading,
    String? error,
    int? tabIndex,
  }) {
    return HumanitarianState(
      organizations: organizations ?? this.organizations,
      programs: programs ?? this.programs,
      disbursements: disbursements ?? this.disbursements,
      selectedOrgId: selectedOrgId ?? this.selectedOrgId,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      tabIndex: tabIndex ?? this.tabIndex,
    );
  }
}

class HumanitarianNotifier extends StateNotifier<HumanitarianState> {
  final HumanitarianService _service;

  HumanitarianNotifier(this._service) : super(const HumanitarianState());

  Future<void> loadOrganizations() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getOrganizations();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(organizations: list, isLoading: false);
      if (list.isNotEmpty && state.selectedOrgId == null) {
        state = state.copyWith(selectedOrgId: list.first['id'] as String?);
      }
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل المنظمات');
    }
  }

  Future<void> loadPrograms({String? orgId}) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getPrograms(orgId: orgId ?? state.selectedOrgId);
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(programs: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل البرامج');
    }
  }

  Future<void> loadHistory() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getHistory();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(disbursements: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل السجل');
    }
  }

  Future<bool> disburse({
    required String programId,
    required String type,
    required int amount,
    String? beneficiaryId,
  }) async {
    try {
      await _service.disburse(
        programId: programId,
        type: type,
        amount: amount,
        beneficiaryId: beneficiaryId,
      );
      return true;
    } catch (e) {
      state = state.copyWith(error: 'فشل تنفيذ الصرف');
      return false;
    }
  }

  void setOrgFilter(String? orgId) {
    state = state.copyWith(selectedOrgId: orgId);
    loadPrograms(orgId: orgId);
  }

  void setTab(int index) => state = state.copyWith(tabIndex: index);

  void clearError() => state = state.copyWith(error: null);
}

final humanitarianProvider =
    StateNotifierProvider<HumanitarianNotifier, HumanitarianState>((ref) {
  final api = ApiClient();
  final service = HumanitarianService(api);
  return HumanitarianNotifier(service);
});

class HumanitarianScreen extends ConsumerStatefulWidget {
  const HumanitarianScreen({super.key});
  @override
  ConsumerState<HumanitarianScreen> createState() => _HumanitarianScreenState();
}

class _HumanitarianScreenState extends ConsumerState<HumanitarianScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(() {
      final index = _tabController.index;
      ref.read(humanitarianProvider.notifier).setTab(index);
      if (index == 1) {
        ref.read(humanitarianProvider.notifier).loadHistory();
      }
    });
    Future.microtask(() {
      ref.read(humanitarianProvider.notifier).loadOrganizations();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(humanitarianProvider);
    return Scaffold(
      appBar: AppBar(
        title: const Text('المساعدات الإنسانية'),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'البرامج'),
            Tab(text: 'السجل'),
          ],
        ),
      ),
      body: state.error != null
          ? Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(state.error!, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.error)),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () {
                      ref.read(humanitarianProvider.notifier).clearError();
                      ref.read(humanitarianProvider.notifier).loadOrganizations();
                    },
                    child: const Text('إعادة المحاولة'),
                  ),
                ],
              ),
            )
          : TabBarView(
              controller: _tabController,
              children: [
                _ProgramsTab(state: state, ref: ref),
                _HistoryTab(state: state, ref: ref),
              ],
            ),
    );
  }
}

class _ProgramsTab extends StatelessWidget {
  final HumanitarianState state;
  final WidgetRef ref;

  const _ProgramsTab({required this.state, required this.ref});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        if (state.organizations.isNotEmpty)
          Padding(
            padding: const EdgeInsets.fromLTRB(16, 12, 16, 0),
            child: DropdownButtonFormField<String>(
              initialValue: state.selectedOrgId,
              decoration: const InputDecoration(
                labelText: 'المنظمة',
                prefixIcon: Icon(Icons.business),
              ),
              items: state.organizations.map((org) {
                return DropdownMenuItem(
                  value: org['id'] as String,
                  child: Text(org['name_ar'] ?? org['name'] ?? ''),
                );
              }).toList(),
              onChanged: (val) {
                if (val != null) {
                  ref.read(humanitarianProvider.notifier).setOrgFilter(val);
                }
              },
            ),
          ),
        if (state.isLoading && state.programs.isEmpty)
          Expanded(
            child: Shimmer.fromColors(
              baseColor: AppTheme.shimmer,
              highlightColor: AppTheme.surfaceContainerLow,
              child: ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: 4,
                itemBuilder: (_, _) => Container(
                  margin: const EdgeInsets.only(bottom: 12),
                  height: 120,
                  decoration: BoxDecoration(
                    color: Colors.white,
                    borderRadius: BorderRadius.circular(12),
                  ),
                ),
              ),
            ),
          )
        else if (state.programs.isEmpty)
          Expanded(
            child: Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Container(
                    padding: const EdgeInsets.all(16),
                    decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
                    child: const Icon(Icons.volunteer_activism_outlined, size: 36, color: AppTheme.textSecondary),
                  ),
                  const SizedBox(height: 16),
                  Text('لا توجد برامج', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
                ],
              ),
            ),
          )
        else
          Expanded(
            child: RefreshIndicator(
              onRefresh: () => ref.read(humanitarianProvider.notifier).loadPrograms(),
              child: ListView.builder(
                padding: const EdgeInsets.all(16),
                itemCount: state.programs.length,
                itemBuilder: (context, index) {
                  final program = state.programs[index];
                  final totalBudget = program['total_budget'] as int? ?? 0;
                  final remaining = program['remaining_budget'] as int? ?? 0;
                  final progress = totalBudget > 0 ? remaining / totalBudget : 0.0;
                  return Container(
                    margin: const EdgeInsets.only(bottom: 12),
                    decoration: AppTheme.cardDecoration,
                    child: InkWell(
                      borderRadius: BorderRadius.circular(12),
                      onTap: () => _showDisburseDialog(context, program),
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          crossAxisAlignment: CrossAxisAlignment.start,
                          children: [
                            Row(
                              children: [
                                Expanded(
                                  child: Text(
                                    program['name_ar'] ?? program['name'] ?? '',
                                    style: const TextStyle(
                                      fontFamily: 'Cairo',
                                      fontSize: 16,
                                      fontWeight: FontWeight.w600,
                                    ),
                                  ),
                                ),
                                Container(
                                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                  decoration: BoxDecoration(
                    color: program['is_active'] == true
                        ? AppTheme.successLight
                        : AppTheme.surfaceContainerLow,
                    borderRadius: BorderRadius.circular(8),
                                  ),
                                  child: Text(
                                    program['is_active'] == true ? 'نشط' : 'غير نشط',
                                    style: TextStyle(
                                      fontFamily: 'Cairo',
                                      fontSize: 12,
                                      color: program['is_active'] == true
                                          ? Colors.green
                                          : AppTheme.textSecondary,
                                    ),
                                  ),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            Text(
                              program['type'] ?? '',
                              style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 12),
                            ),
                            const SizedBox(height: 12),
                            Row(
                              mainAxisAlignment: MainAxisAlignment.spaceBetween,
                              children: [
                                Text(
                                  'المتبقي: ${NumberFormat('#,###').format(remaining)} ل.س',
                                  style: const TextStyle(fontFamily: 'Cairo', fontSize: 12, color: AppTheme.textSecondary),
                                ),
                                Text(
                                  'الإجمالي: ${NumberFormat('#,###').format(totalBudget)} ل.س',
                                  style: const TextStyle(fontFamily: 'Cairo', fontSize: 12, color: AppTheme.textSecondary),
                                ),
                              ],
                            ),
                            const SizedBox(height: 8),
                            ClipRRect(
                              borderRadius: BorderRadius.circular(4),
                              child: LinearProgressIndicator(
                                value: progress,
                                backgroundColor: AppTheme.divider,
                                valueColor: AlwaysStoppedAnimation<Color>(
                                  progress > 0.5 ? AppTheme.primary : Colors.orange,
                                ),
                                minHeight: 8,
                              ),
                            ),
                          ],
                        ),
                      ),
                    ),
                  );
                },
              ),
            ),
          ),
      ],
    );
  }

  void _showDisburseDialog(BuildContext context, Map<String, dynamic> program) {
    final amountCtrl = TextEditingController();
    final beneficiaryCtrl = TextEditingController();
    String selectedType = 'cash';

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: ui.TextDirection.rtl,
        child: StatefulBuilder(
          builder: (ctx, setDialogState) {
            return AlertDialog(
              title: Text('صرف - ${program['name_ar'] ?? program['name'] ?? ''}'),
              content: SingleChildScrollView(
                child: Column(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    DropdownButtonFormField<String>(
                      initialValue: selectedType,
                      decoration: const InputDecoration(
                        labelText: 'نوع الصرف',
                        prefixIcon: Icon(Icons.category),
                      ),
                      items: const [
                        DropdownMenuItem(value: 'cash', child: Text('نقدي')),
                        DropdownMenuItem(value: 'food', child: Text('غذائي')),
                        DropdownMenuItem(value: 'medical', child: Text('طبي')),
                      ],
                      onChanged: (val) {
                        if (val != null) {
                          setDialogState(() => selectedType = val);
                        }
                      },
                    ),
                    const SizedBox(height: 12),
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
                      controller: beneficiaryCtrl,
                      decoration: const InputDecoration(
                        labelText: 'رقم المستفيد (اختياري)',
                        prefixIcon: Icon(Icons.person_outline),
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
                    if (amount <= 0) return;
                    final success = await ref.read(humanitarianProvider.notifier).disburse(
                      programId: program['id'],
                      type: selectedType,
                      amount: amount,
                      beneficiaryId: beneficiaryCtrl.text.isEmpty ? null : beneficiaryCtrl.text,
                    );
                    if (ctx.mounted) Navigator.pop(ctx);
                    if (success && context.mounted) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('تم تنفيذ الصرف بنجاح')),
                      );
                    }
                  },
                  child: const Text('صرف'),
                ),
              ],
            );
          },
        ),
      ),
    );
  }
}

class _HistoryTab extends StatelessWidget {
  final HumanitarianState state;
  final WidgetRef ref;

  const _HistoryTab({required this.state, required this.ref});

  @override
  Widget build(BuildContext context) {
    if (state.isLoading && state.disbursements.isEmpty) {
      return Center(
        child: Shimmer.fromColors(
          baseColor: AppTheme.shimmer,
          highlightColor: AppTheme.surfaceContainerLow,
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: 4,
            itemBuilder: (_, _) => Container(
              margin: const EdgeInsets.only(bottom: 12),
              height: 80,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ),
      );
    }
    if (state.disbursements.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
              child: const Icon(Icons.history, size: 36, color: AppTheme.textSecondary),
            ),
            const SizedBox(height: 16),
            Text('لا يوجد سجل صرف', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: () => ref.read(humanitarianProvider.notifier).loadHistory(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: state.disbursements.length,
        itemBuilder: (context, index) {
          final d = state.disbursements[index];
          final typeColors = {
            'cash': Colors.green,
            'food': Colors.orange,
            'medical': Colors.red,
          };
          final typeNames = {
            'cash': 'نقدي',
            'food': 'غذائي',
            'medical': 'طبي',
          };
          final color = typeColors[d['type'] as String?] ?? AppTheme.textSecondary;
          return Container(
            margin: const EdgeInsets.only(bottom: 8),
            decoration: AppTheme.cardDecoration,
            child: ListTile(
              leading: CircleAvatar(
                backgroundColor: color.withValues(alpha: 0.2),
                child: Icon(
                  d['type'] == 'cash'
                      ? Icons.monetization_on
                      : d['type'] == 'food'
                          ? Icons.restaurant
                          : Icons.medical_services,
                  color: color,
                  size: 20,
                ),
              ),
              title: Text(typeNames[d['type'] as String?] ?? d['type'] ?? ''),
              subtitle: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text('${NumberFormat('#,###').format(d['amount'])} ل.س'),
                  if (d['beneficiary_id'] != null)
                    Text('مستفيد: ${d['beneficiary_id']}', style: const TextStyle(fontFamily: 'Cairo', fontSize: 12)),
                ],
              ),
              trailing: Text(
                d['status'] == 'completed' ? 'مكتمل' : d['status'] ?? '',
                  style: TextStyle(
                    fontFamily: 'Cairo',
                    fontSize: 12,
            color: d['status'] == 'completed' ? AppTheme.success : AppTheme.warning,
            fontWeight: FontWeight.w600,
          ),
              ),
            ),
          );
        },
      ),
    );
  }
}
