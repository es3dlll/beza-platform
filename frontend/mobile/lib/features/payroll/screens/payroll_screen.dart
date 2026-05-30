import 'dart:ui' as ui show TextDirection;
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/api/api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../services/payroll_service.dart';

class PayrollState {
  final Map<String, dynamic>? employer;
  final List<Map<String, dynamic>> batches;
  final Map<String, dynamic>? selectedBatch;
  final bool isLoading;
  final String? error;
  final int tabIndex;
  final bool isRegistered;

  const PayrollState({
    this.employer,
    this.batches = const [],
    this.selectedBatch,
    this.isLoading = false,
    this.error,
    this.tabIndex = 0,
    this.isRegistered = false,
  });

  PayrollState copyWith({
    Map<String, dynamic>? employer,
    List<Map<String, dynamic>>? batches,
    Map<String, dynamic>? selectedBatch,
    bool? isLoading,
    String? error,
    int? tabIndex,
    bool? isRegistered,
  }) {
    return PayrollState(
      employer: employer ?? this.employer,
      batches: batches ?? this.batches,
      selectedBatch: selectedBatch ?? this.selectedBatch,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      tabIndex: tabIndex ?? this.tabIndex,
      isRegistered: isRegistered ?? this.isRegistered,
    );
  }
}

class PayrollNotifier extends StateNotifier<PayrollState> {
  final PayrollService _service;

  PayrollNotifier(this._service) : super(const PayrollState());

  Future<void> checkRegistration() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getMy();
      final employer = result['data'] as Map<String, dynamic>?;
      if (employer != null && employer['id'] != null) {
        state = state.copyWith(employer: employer, isRegistered: true, isLoading: false);
      } else {
        state = state.copyWith(isLoading: false);
      }
    } catch (e) {
      state = state.copyWith(isLoading: false, isRegistered: false);
    }
  }

  Future<bool> register({
    required String companyName,
    required String companyNameAr,
    required String phone,
    required String governorate,
    required String city,
    String? commercialRegistration,
    String? taxNumber,
    String? email,
    String? address,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.register(
        companyName: companyName,
        companyNameAr: companyNameAr,
        phone: phone,
        governorate: governorate,
        city: city,
        commercialRegistration: commercialRegistration,
        taxNumber: taxNumber,
        email: email,
        address: address,
      );
      final employer = result['data'] as Map<String, dynamic>?;
      state = state.copyWith(
        employer: employer,
        isRegistered: true,
        isLoading: false,
      );
      return true;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تسجيل صاحب العمل');
      return false;
    }
  }

  Future<void> loadBatches() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getBatches();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(batches: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل دفعات الرواتب');
    }
  }

  Future<void> loadBatchDetail(String id) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getBatch(id);
      final batch = result['data'] as Map<String, dynamic>?;
      state = state.copyWith(selectedBatch: batch, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل تفاصيل الدفعة');
    }
  }

  Future<bool> createBatch({
    required String periodMonth,
    String? notes,
    required List<Map<String, dynamic>> employees,
  }) async {
    state = state.copyWith(isLoading: true);
    try {
      await _service.createBatch(
        periodMonth: periodMonth,
        notes: notes,
        employees: employees,
      );
      state = state.copyWith(isLoading: false);
      return true;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل إنشاء دفعة الرواتب');
      return false;
    }
  }

  Future<bool> approveBatch(String id) async {
    try {
      await _service.approveBatch(id);
      return true;
    } catch (e) {
      state = state.copyWith(error: 'فشل اعتماد الدفعة');
      return false;
    }
  }

  Future<bool> processBatch(String id) async {
    try {
      await _service.processBatch(id);
      return true;
    } catch (e) {
      state = state.copyWith(error: 'فشل معالجة الدفعة');
      return false;
    }
  }

  void setTab(int index) => state = state.copyWith(tabIndex: index);

  void clearError() => state = state.copyWith(error: null);
}

final payrollProvider = StateNotifierProvider<PayrollNotifier, PayrollState>((ref) {
  final api = ApiClient();
  final service = PayrollService(api);
  return PayrollNotifier(service);
});

class PayrollScreen extends ConsumerStatefulWidget {
  const PayrollScreen({super.key});
  @override
  ConsumerState<PayrollScreen> createState() => _PayrollScreenState();
}

class _PayrollScreenState extends ConsumerState<PayrollScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 2, vsync: this);
    _tabController.addListener(() {
      final index = _tabController.index;
      ref.read(payrollProvider.notifier).setTab(index);
      if (index == 0) {
        ref.read(payrollProvider.notifier).loadBatches();
      }
    });
    Future.microtask(() {
      ref.read(payrollProvider.notifier).checkRegistration();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(payrollProvider);

    if (state.isLoading && state.employer == null) {
      return Scaffold(
        appBar: AppBar(title: const Text('الرواتب')),
        body: const _PayrollShimmer(),
      );
    }

    if (!state.isRegistered) {
      return Scaffold(
        appBar: AppBar(title: const Text('الرواتب')),
        body: _RegistrationForm(state: state),
      );
    }

    return Scaffold(
      appBar: AppBar(
        title: const Text('الرواتب'),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'الرواتب'),
            Tab(text: 'إنشاء'),
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
                      decoration: const BoxDecoration(color: AppTheme.errorLight, shape: BoxShape.circle),
                      child: const Icon(Icons.error_outline, size: 40, color: AppTheme.error),
                    ),
                    const SizedBox(height: 20),
                    Text(state.error!, style: const TextStyle(fontFamily: 'Cairo', fontSize: 16, color: AppTheme.textSecondary), textAlign: TextAlign.center),
                    const SizedBox(height: 24),
                    ElevatedButton.icon(
                      onPressed: () { ref.read(payrollProvider.notifier).clearError(); },
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
                _BatchesTab(state: state, ref: ref),
                _CreateBatchTab(state: state, ref: ref),
              ],
            ),
    );
  }
}

class _RegistrationForm extends ConsumerStatefulWidget {
  final PayrollState state;
  const _RegistrationForm({required this.state});
  @override
  ConsumerState<_RegistrationForm> createState() => _RegistrationFormState();
}

class _RegistrationFormState extends ConsumerState<_RegistrationForm> {
  final _nameCtrl = TextEditingController();
  final _nameArCtrl = TextEditingController();
  final _phoneCtrl = TextEditingController();
  final _govCtrl = TextEditingController();
  final _cityCtrl = TextEditingController();
  final _commercialCtrl = TextEditingController();
  final _taxCtrl = TextEditingController();
  final _emailCtrl = TextEditingController();
  final _addressCtrl = TextEditingController();

  @override
  void dispose() {
    _nameCtrl.dispose();
    _nameArCtrl.dispose();
    _phoneCtrl.dispose();
    _govCtrl.dispose();
    _cityCtrl.dispose();
    _commercialCtrl.dispose();
    _taxCtrl.dispose();
    _emailCtrl.dispose();
    _addressCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Container(
            padding: const EdgeInsets.all(16),
            decoration: AppTheme.cardDecoration,
            child: Row(
              children: [
                Container(
                  padding: const EdgeInsets.all(12),
                  decoration: BoxDecoration(
                    gradient: AppTheme.primaryGradient,
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.business, color: Colors.white, size: 28),
                ),
                const SizedBox(width: 12),
                const Expanded(
                  child: Text(
                    'سجل شركتك لإدارة رواتب الموظفين',
                    style: TextStyle(fontFamily: 'Cairo', fontSize: 16, fontWeight: FontWeight.w600, color: AppTheme.textPrimary),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 20),
          TextField(controller: _nameCtrl, decoration: const InputDecoration(labelText: 'اسم الشركة (إنجليزي)', prefixIcon: Icon(Icons.business))),
          const SizedBox(height: 12),
          TextField(controller: _nameArCtrl, decoration: const InputDecoration(labelText: 'اسم الشركة (عربي)', prefixIcon: Icon(Icons.business))),
          const SizedBox(height: 12),
          TextField(controller: _phoneCtrl, decoration: const InputDecoration(labelText: 'رقم الهاتف', prefixIcon: Icon(Icons.phone)), keyboardType: TextInputType.phone),
          const SizedBox(height: 12),
          TextField(controller: _govCtrl, decoration: const InputDecoration(labelText: 'المحافظة', prefixIcon: Icon(Icons.location_city))),
          const SizedBox(height: 12),
          TextField(controller: _cityCtrl, decoration: const InputDecoration(labelText: 'المدينة', prefixIcon: Icon(Icons.location_on))),
          const SizedBox(height: 12),
          TextField(controller: _commercialCtrl, decoration: const InputDecoration(labelText: 'السجل التجاري (اختياري)', prefixIcon: Icon(Icons.assignment))),
          const SizedBox(height: 12),
          TextField(controller: _taxCtrl, decoration: const InputDecoration(labelText: 'الرقم الضريبي (اختياري)', prefixIcon: Icon(Icons.receipt))),
          const SizedBox(height: 12),
          TextField(controller: _emailCtrl, decoration: const InputDecoration(labelText: 'البريد الإلكتروني (اختياري)', prefixIcon: Icon(Icons.email)), keyboardType: TextInputType.emailAddress),
          const SizedBox(height: 12),
          TextField(controller: _addressCtrl, decoration: const InputDecoration(labelText: 'العنوان (اختياري)', prefixIcon: Icon(Icons.home))),
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: widget.state.isLoading
                  ? null
                  : () async {
                      if (_nameCtrl.text.isEmpty || _nameArCtrl.text.isEmpty || _phoneCtrl.text.isEmpty || _govCtrl.text.isEmpty || _cityCtrl.text.isEmpty) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('يرجى ملء الحقول الإلزامية')),
                        );
                        return;
                      }
                      final success = await ref.read(payrollProvider.notifier).register(
                        companyName: _nameCtrl.text,
                        companyNameAr: _nameArCtrl.text,
                        phone: _phoneCtrl.text,
                        governorate: _govCtrl.text,
                        city: _cityCtrl.text,
                        commercialRegistration: _commercialCtrl.text.isEmpty ? null : _commercialCtrl.text,
                        taxNumber: _taxCtrl.text.isEmpty ? null : _taxCtrl.text,
                        email: _emailCtrl.text.isEmpty ? null : _emailCtrl.text,
                        address: _addressCtrl.text.isEmpty ? null : _addressCtrl.text,
                      );
                      if (success && context.mounted) {
                        ScaffoldMessenger.of(context).showSnackBar(
                          const SnackBar(content: Text('تم التسجيل بنجاح')),
                        );
                      }
                    },
              child: widget.state.isLoading
                  ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                  : const Text('تسجيل'),
            ),
          ),
        ],
      ),
    );
  }
}

class _BatchesTab extends StatelessWidget {
  final PayrollState state;
  final WidgetRef ref;
  const _BatchesTab({required this.state, required this.ref});

  @override
  Widget build(BuildContext context) {
    if (state.isLoading && state.batches.isEmpty) {
      return const _PayrollShimmer();
    }
    if (state.batches.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                color: AppTheme.surfaceContainerLow,
                borderRadius: BorderRadius.circular(20),
              ),
              child: Icon(Icons.payments_outlined, size: 48, color: AppTheme.textSecondary.withValues(alpha: 0.5)),
            ),
            const SizedBox(height: 16),
            const Text('لا توجد دفعات رواتب', style: TextStyle(fontFamily: 'Cairo', fontSize: 16, color: AppTheme.textSecondary)),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: () => ref.read(payrollProvider.notifier).loadBatches(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: state.batches.length,
        itemBuilder: (context, index) {
          final batch = state.batches[index];
          final totalAmount = batch['total_amount'] as int? ?? 0;
          final employeeCount = (batch['employees'] as List?)?.length ?? batch['employee_count'] ?? 0;
          return Container(
            margin: const EdgeInsets.only(bottom: 12),
            decoration: AppTheme.cardDecoration,
            child: InkWell(
              borderRadius: AppTheme.radiusLg,
              onTap: () => _showBatchDetail(context, batch, ref),
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Row(
                  children: [
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(
                            'شهر: ${batch['period_month'] ?? ''}',
                            style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold, fontSize: 16, color: AppTheme.textPrimary),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            '${NumberFormat('#,###').format(totalAmount)} ل.س',
                            style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.primary, fontWeight: FontWeight.w600, fontSize: 15),
                          ),
                          const SizedBox(height: 4),
                          Text(
                            '$employeeCount موظف',
                            style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 12),
                          ),
                        ],
                      ),
                    ),
                    _BatchStatusBadge(status: batch['status'] as String? ?? ''),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  void _showBatchDetail(BuildContext context, Map<String, dynamic> batch, WidgetRef ref) {
    ref.read(payrollProvider.notifier).loadBatchDetail(batch['id']);

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: ui.TextDirection.rtl,
        child: StatefulBuilder(
          builder: (ctx, setDialogState) {
            final currentState = ref.watch(payrollProvider);
            final detail = currentState.selectedBatch ?? batch;
            final employees = (detail['employees'] as List?)?.cast<Map<String, dynamic>>() ?? [];
            final status = detail['status'] as String? ?? '';
            return AlertDialog(
              title: Text('دفعة ${detail['period_month'] ?? ''}', style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold)),
              content: SizedBox(
                width: double.maxFinite,
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      _PayrollDetailRow(label: 'الشهر', value: detail['period_month'] ?? ''),
                      _PayrollDetailRow(label: 'الإجمالي', value: '${NumberFormat('#,###').format(detail['total_amount'] ?? 0)} ل.س'),
                      _PayrollDetailRow(label: 'عدد الموظفين', value: employees.length.toString()),
                      if (detail['notes'] != null)
                        _PayrollDetailRow(label: 'ملاحظات', value: detail['notes']),
                      const Divider(height: 1, color: AppTheme.divider),
                      const Padding(
                        padding: EdgeInsets.symmetric(vertical: 8),
                        child: Text('الموظفون:', style: TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold, fontSize: 16, color: AppTheme.textPrimary)),
                      ),
                      const SizedBox(height: 8),
                      if (employees.isEmpty)
                        const Padding(
                          padding: EdgeInsets.symmetric(vertical: 12),
                          child: Text('لا يوجد موظفون', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
                        )
                      else
                        ...employees.map((emp) => Container(
                              padding: const EdgeInsets.symmetric(vertical: 4),
                              margin: const EdgeInsets.only(bottom: 4),
                              child: Row(
                                children: [
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(emp['employee_name'] ?? '', style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w500, color: AppTheme.textPrimary)),
                                        Text(emp['phone'] ?? '', style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 12)),
                                      ],
                                    ),
                                  ),
                                  Text('${NumberFormat('#,###').format(emp['amount'])} ل.س', style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w600, color: AppTheme.primary)),
                                ],
                              ),
                            )),
                      const SizedBox(height: 16),
                      if (status == 'pending') ...[
                        Row(
                          children: [
                            Expanded(
                              child: ElevatedButton(
                                onPressed: () async {
                                  final success = await ref.read(payrollProvider.notifier).approveBatch(detail['id']);
                                  if (success && context.mounted) {
                                    Navigator.pop(ctx);
                                    ScaffoldMessenger.of(context).showSnackBar(
                                      const SnackBar(content: Text('تم اعتماد الدفعة')),
                                    );
                                  }
                                },
                                child: const Text('اعتماد'),
                              ),
                            ),
                            const SizedBox(width: 12),
                          ],
                        ),
                      ],
                      if (status == 'approved') ...[
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: () async {
                              final success = await ref.read(payrollProvider.notifier).processBatch(detail['id']);
                              if (success && context.mounted) {
                                Navigator.pop(ctx);
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('تمت معالجة الدفعة')),
                                );
                              }
                            },
                            child: const Text('معالجة'),
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

class _PayrollDetailRow extends StatelessWidget {
  final String label;
  final String value;
  const _PayrollDetailRow({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 13)),
          Text(value, style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w500, color: AppTheme.textPrimary, fontSize: 13)),
        ],
      ),
    );
  }
}

class _BatchStatusBadge extends StatelessWidget {
  final String status;
  const _BatchStatusBadge({required this.status});

  @override
  Widget build(BuildContext context) {
    Color color;
    String text;
    switch (status) {
      case 'pending':
        color = Colors.orange;
        text = 'قيد الانتظار';
        break;
      case 'approved':
        color = Colors.blue;
        text = 'معتمد';
        break;
      case 'processed':
        color = Colors.green;
        text = 'معالج';
        break;
      case 'cancelled':
        color = Colors.red;
        text = 'ملغي';
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
      ),
      child: Text(text, style: TextStyle(fontFamily: 'Cairo', color: color, fontSize: 12, fontWeight: FontWeight.w600)),
    );
  }
}

class _CreateBatchTab extends ConsumerStatefulWidget {
  final PayrollState state;
  final WidgetRef ref;
  const _CreateBatchTab({required this.state, required this.ref});

  @override
  ConsumerState<_CreateBatchTab> createState() => _CreateBatchTabState();
}

class _CreateBatchTabState extends ConsumerState<_CreateBatchTab> {
  final periodCtrl = TextEditingController();
  final notesCtrl = TextEditingController();
  final List<_EmployeeRow> _employees = [];

  void _addEmployee() {
    setState(() {
      _employees.add(_EmployeeRow(
        nameCtrl: TextEditingController(),
        phoneCtrl: TextEditingController(),
        amountCtrl: TextEditingController(),
      ));
    });
  }

  void _removeEmployee(int index) {
    setState(() {
      _employees[index].dispose();
      _employees.removeAt(index);
    });
  }

  int get _totalAmount => _employees.fold(0, (sum, e) => sum + (int.tryParse(e.amountCtrl.text) ?? 0));

  @override
  void dispose() {
    periodCtrl.dispose();
    notesCtrl.dispose();
    for (final e in _employees) {
      e.dispose();
    }
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          TextField(
            controller: periodCtrl,
            decoration: const InputDecoration(
              labelText: 'الشهر (YYYY-MM)',
              prefixIcon: Icon(Icons.calendar_month),
              hintText: '2026-05',
            ),
          ),
          const SizedBox(height: 12),
          TextField(
            controller: notesCtrl,
            decoration: const InputDecoration(
              labelText: 'ملاحظات (اختياري)',
              prefixIcon: Icon(Icons.notes),
            ),
            maxLines: 2,
          ),
          const SizedBox(height: 20),
          Row(
            mainAxisAlignment: MainAxisAlignment.spaceBetween,
            children: [
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
                  const Text('الموظفون:', style: TextStyle(fontFamily: 'Cairo', fontSize: 18, fontWeight: FontWeight.bold, color: AppTheme.textPrimary)),
                ],
              ),
              TextButton.icon(
                onPressed: _addEmployee,
                icon: const Icon(Icons.add),
                label: const Text('إضافة موظف'),
              ),
            ],
          ),
          const SizedBox(height: 8),
          if (_employees.isEmpty)
            Container(
              width: double.infinity,
              padding: const EdgeInsets.all(24),
              decoration: AppTheme.cardDecoration,
              child: Center(
                child: Column(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(12),
                      decoration: BoxDecoration(
                        color: AppTheme.surfaceContainerLow,
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(Icons.people_outline, size: 32, color: AppTheme.textSecondary.withValues(alpha: 0.5)),
                    ),
                    const SizedBox(height: 12),
                    const Text(
                      'أضف موظفين لإنشاء دفعة الرواتب',
                      style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary),
                    ),
                  ],
                ),
              ),
            )
          else
            ...List.generate(_employees.length, (index) {
              final emp = _employees[index];
              return Container(
                margin: const EdgeInsets.only(bottom: 8),
                decoration: AppTheme.cardDecoration,
                child: Padding(
                  padding: const EdgeInsets.all(12),
                  child: Column(
                    children: [
                      Row(
                        children: [
                          Container(
                            padding: const EdgeInsets.all(8),
                            decoration: BoxDecoration(
                              color: AppTheme.primary.withValues(alpha: 0.1),
                              borderRadius: BorderRadius.circular(10),
                            ),
                            child: Text('${index + 1}', style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold, color: AppTheme.primary)),
                          ),
                          const SizedBox(width: 10),
                          const Text('موظف', style: TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold, color: AppTheme.textPrimary)),
                          const Spacer(),
                          IconButton(
                            icon: const Icon(Icons.close, color: AppTheme.error, size: 20),
                            onPressed: () => _removeEmployee(index),
                          ),
                        ],
                      ),
                      const SizedBox(height: 8),
                      TextField(
                        controller: emp.nameCtrl,
                        decoration: const InputDecoration(labelText: 'اسم الموظف', isDense: true),
                      ),
                      const SizedBox(height: 8),
                      Row(
                        children: [
                          Expanded(
                            child: TextField(
                              controller: emp.phoneCtrl,
                              decoration: const InputDecoration(labelText: 'رقم الهاتف', isDense: true),
                              keyboardType: TextInputType.phone,
                            ),
                          ),
                          const SizedBox(width: 8),
                          Expanded(
                            child: TextField(
                              controller: emp.amountCtrl,
                              decoration: const InputDecoration(labelText: 'المبلغ (ل.س)', isDense: true),
                              keyboardType: TextInputType.number,
                              onChanged: (_) => setState(() {}),
                            ),
                          ),
                        ],
                      ),
                    ],
                  ),
                ),
              );
            }),
          if (_employees.isNotEmpty) ...[
            const SizedBox(height: 8),
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(
                color: AppTheme.primary.withValues(alpha: 0.05),
                borderRadius: AppTheme.radiusLg,
                border: Border.all(color: AppTheme.primary.withValues(alpha: 0.1)),
              ),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  Text('عدد الموظفين: ${_employees.length}', style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
                  Text('الإجمالي: ${NumberFormat('#,###').format(_totalAmount)} ل.س',
                      style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold, color: AppTheme.primary)),
                ],
              ),
            ),
            const SizedBox(height: 16),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: widget.state.isLoading
                    ? null
                    : () async {
                        if (periodCtrl.text.isEmpty || _employees.isEmpty) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('يرجى تحديد الشهر وإضافة موظفين')),
                          );
                          return;
                        }
                        final employees = _employees
                            .where((e) =>
                                e.nameCtrl.text.isNotEmpty &&
                                e.phoneCtrl.text.isNotEmpty &&
                                int.tryParse(e.amountCtrl.text) != null)
                            .map((e) => {
                                  'employee_name': e.nameCtrl.text,
                                  'phone': e.phoneCtrl.text,
                                  'amount': int.tryParse(e.amountCtrl.text) ?? 0,
                                })
                            .toList();
                        if (employees.isEmpty) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('يرجى ملء بيانات الموظفين بشكل صحيح')),
                          );
                          return;
                        }
                        final success = await ref.read(payrollProvider.notifier).createBatch(
                          periodMonth: periodCtrl.text,
                          notes: notesCtrl.text.isEmpty ? null : notesCtrl.text,
                          employees: employees,
                        );
                        if (success && context.mounted) {
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('تم إنشاء دفعة الرواتب بنجاح')),
                          );
                          setState(() {
                            periodCtrl.clear();
                            notesCtrl.clear();
                            for (final e in _employees) {
                              e.dispose();
                            }
                            _employees.clear();
                          });
                        }
                      },
                child: widget.state.isLoading
                    ? const SizedBox(height: 20, width: 20, child: CircularProgressIndicator(strokeWidth: 2, color: Colors.white))
                    : const Text('إنشاء دفعة الرواتب'),
              ),
            ),
          ],
        ],
      ),
    );
  }
}

class _PayrollShimmer extends StatelessWidget {
  const _PayrollShimmer();

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
              Container(width: 100, height: 14, decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(4))),
              const SizedBox(height: 8),
              Container(width: 80, height: 12, decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(4))),
              const SizedBox(height: 8),
              Container(width: 60, height: 10, decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(4))),
            ],
          ),
        ),
      ),
    );
  }
}

class _EmployeeRow {
  final TextEditingController nameCtrl;
  final TextEditingController phoneCtrl;
  final TextEditingController amountCtrl;

  _EmployeeRow({
    required this.nameCtrl,
    required this.phoneCtrl,
    required this.amountCtrl,
  });

  void dispose() {
    nameCtrl.dispose();
    phoneCtrl.dispose();
    amountCtrl.dispose();
  }
}
