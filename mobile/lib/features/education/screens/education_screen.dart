import 'dart:ui' as ui show TextDirection;
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/api/api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../services/education_service.dart';

class EducationState {
  final List<Map<String, dynamic>> institutions;
  final List<Map<String, dynamic>> registeredStudents;
  final List<Map<String, dynamic>> currentFees;
  final bool isLoading;
  final String? error;
  final int tabIndex;
  final String? selectedStudentId;

  const EducationState({
    this.institutions = const [],
    this.registeredStudents = const [],
    this.currentFees = const [],
    this.isLoading = false,
    this.error,
    this.tabIndex = 0,
    this.selectedStudentId,
  });

  EducationState copyWith({
    List<Map<String, dynamic>>? institutions,
    List<Map<String, dynamic>>? registeredStudents,
    List<Map<String, dynamic>>? currentFees,
    bool? isLoading,
    String? error,
    int? tabIndex,
    String? selectedStudentId,
  }) {
    return EducationState(
      institutions: institutions ?? this.institutions,
      registeredStudents: registeredStudents ?? this.registeredStudents,
      currentFees: currentFees ?? this.currentFees,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      tabIndex: tabIndex ?? this.tabIndex,
      selectedStudentId: selectedStudentId ?? this.selectedStudentId,
    );
  }
}

class EducationNotifier extends StateNotifier<EducationState> {
  final EducationService _service;

  EducationNotifier(this._service) : super(const EducationState());

  Future<void> loadInstitutions() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getInstitutions();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(institutions: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل المؤسسات');
    }
  }

  Future<bool> registerStudent({
    required String institutionId,
    required String studentId,
    required String fullName,
    required String fullNameAr,
    String? grade,
  }) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.registerStudent(
        institutionId: institutionId,
        studentId: studentId,
        fullName: fullName,
        fullNameAr: fullNameAr,
        grade: grade,
      );
      final student = result['data'] as Map<String, dynamic>? ?? {};
      state = state.copyWith(
        isLoading: false,
        registeredStudents: [...state.registeredStudents, student],
      );
      return true;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تسجيل الطالب');
      return false;
    }
  }

  Future<void> loadStudentFees(String id) async {
    state = state.copyWith(isLoading: true, error: null, selectedStudentId: id);
    try {
      final result = await _service.getStudentFees(id);
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(currentFees: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل الرسوم');
    }
  }

  Future<bool> createFee({
    required String studentId,
    required String feeType,
    required int amount,
    required String dueDate,
  }) async {
    try {
      final result = await _service.createFee(
        studentId: studentId,
        feeType: feeType,
        amount: amount,
        dueDate: dueDate,
      );
      final fee = result['data'] as Map<String, dynamic>? ?? {};
      state = state.copyWith(currentFees: [...state.currentFees, fee]);
      return true;
    } catch (e) {
      state = state.copyWith(error: 'فشل إنشاء الرسوم');
      return false;
    }
  }

  Future<bool> payFee(String id, {required int amount}) async {
    try {
      final result = await _service.payFee(id, amount: amount);
      final updated = result['data'] as Map<String, dynamic>? ?? {};
      final fees = state.currentFees.map((f) {
        return f['id'] == id ? updated : f;
      }).toList();
      state = state.copyWith(currentFees: fees);
      return true;
    } catch (e) {
      state = state.copyWith(error: 'فشل دفع الرسوم');
      return false;
    }
  }

  void setTab(int index) => state = state.copyWith(tabIndex: index);

  void clearError() => state = state.copyWith(error: null);

  void clearFees() => state = state.copyWith(currentFees: []);
}

final educationProvider =
    StateNotifierProvider<EducationNotifier, EducationState>((ref) {
  final api = ApiClient();
  final service = EducationService(api);
  return EducationNotifier(service);
});

class EducationScreen extends ConsumerStatefulWidget {
  const EducationScreen({super.key});
  @override
  ConsumerState<EducationScreen> createState() => _EducationScreenState();
}

class _EducationScreenState extends ConsumerState<EducationScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _tabController.addListener(() {
      ref.read(educationProvider.notifier).setTab(_tabController.index);
    });
    Future.microtask(() {
      ref.read(educationProvider.notifier).loadInstitutions();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(educationProvider);
    return Scaffold(
      appBar: AppBar(
        title: const Text('التعليم'),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'المؤسسات'),
            Tab(text: 'الطلاب'),
            Tab(text: 'الرسوم'),
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
                        ref.read(educationProvider.notifier).clearError();
                        ref.read(educationProvider.notifier).loadInstitutions();
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
                _InstitutionsTab(state: state, ref: ref),
                _StudentsTab(state: state, ref: ref),
                _FeesTab(state: state, ref: ref),
              ],
            ),
    );
  }
}

class _InstitutionsTab extends StatelessWidget {
  final EducationState state;
  final WidgetRef ref;

  const _InstitutionsTab({required this.state, required this.ref});

  Widget _shimmerGrid() {
    return Shimmer.fromColors(
      baseColor: AppTheme.shimmer,
      highlightColor: Colors.grey[100]!,
      child: GridView.builder(
        padding: const EdgeInsets.all(16),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          childAspectRatio: 1.2,
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
        ),
        itemCount: 4,
        itemBuilder: (_, _) => Container(
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
    if (state.isLoading && state.institutions.isEmpty) {
      return _shimmerGrid();
    }
    if (state.institutions.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
              child: Icon(Icons.school_outlined, size: 36, color: AppTheme.textSecondary.withValues(alpha: 0.5)),
            ),
            const SizedBox(height: 14),
            const Text('لا توجد مؤسسات', style: TextStyle(fontFamily: 'Cairo', fontSize: 15, color: AppTheme.textSecondary)),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: () => ref.read(educationProvider.notifier).loadInstitutions(),
      child: GridView.builder(
        padding: const EdgeInsets.all(16),
        gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
          crossAxisCount: 2,
          childAspectRatio: 1.2,
          crossAxisSpacing: 12,
          mainAxisSpacing: 12,
        ),
        itemCount: state.institutions.length,
        itemBuilder: (context, index) {
          final inst = state.institutions[index];
          return Container(
            decoration: AppTheme.cardDecoration,
            child: InkWell(
              borderRadius: AppTheme.radiusLg,
              onTap: () => _showRegisterDialog(context, inst),
              child: Padding(
                padding: const EdgeInsets.all(12),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  mainAxisAlignment: MainAxisAlignment.center,
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: AppTheme.primary.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(
                        inst['type'] == 'school' ? Icons.school : Icons.account_balance,
                        color: AppTheme.primary,
                        size: 22,
                      ),
                    ),
                    const SizedBox(height: 10),
                    Text(
                      inst['name_ar'] ?? inst['name'] ?? '',
                      style: const TextStyle(
                        fontFamily: 'Cairo',
                        fontWeight: FontWeight.w600,
                        fontSize: 14,
                      ),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${inst['governorate'] ?? ''} - ${inst['city'] ?? ''}',
                      style: const TextStyle(
                        fontFamily: 'Cairo',
                        color: AppTheme.textSecondary,
                        fontSize: 12,
                      ),
                      maxLines: 1,
                      overflow: TextOverflow.ellipsis,
                    ),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  void _showRegisterDialog(BuildContext context, Map<String, dynamic> institution) {
    final studentIdCtrl = TextEditingController();
    final fullNameCtrl = TextEditingController();
    final fullNameArCtrl = TextEditingController();
    final gradeCtrl = TextEditingController();

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: ui.TextDirection.rtl,
        child: AlertDialog(
          title: Text('تسجيل طالب في ${institution['name_ar'] ?? institution['name'] ?? ''}'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: studentIdCtrl,
                  decoration: const InputDecoration(labelText: 'رقم الطالب', hintText: 'أدخل رقم الطالب'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: fullNameCtrl,
                  decoration: const InputDecoration(labelText: 'الاسم الكامل (إنجليزي)'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: fullNameArCtrl,
                  decoration: const InputDecoration(labelText: 'الاسم الكامل (عربي)'),
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: gradeCtrl,
                  decoration: const InputDecoration(labelText: 'الصف (اختياري)'),
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
                if (studentIdCtrl.text.isEmpty || fullNameCtrl.text.isEmpty || fullNameArCtrl.text.isEmpty) {
                  return;
                }
                final success = await ref.read(educationProvider.notifier).registerStudent(
                  institutionId: institution['id'],
                  studentId: studentIdCtrl.text,
                  fullName: fullNameCtrl.text,
                  fullNameAr: fullNameArCtrl.text,
                  grade: gradeCtrl.text.isEmpty ? null : gradeCtrl.text,
                );
                if (ctx.mounted) Navigator.pop(ctx);
                if (success && context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('تم تسجيل الطالب بنجاح')),
                  );
                }
              },
              child: const Text('تسجيل'),
            ),
          ],
        ),
      ),
    );
  }
}

class _StudentsTab extends StatelessWidget {
  final EducationState state;
  final WidgetRef ref;

  const _StudentsTab({required this.state, required this.ref});

  @override
  Widget build(BuildContext context) {
    if (state.registeredStudents.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
              child: Icon(Icons.people_outline, size: 36, color: AppTheme.textSecondary.withValues(alpha: 0.5)),
            ),
            const SizedBox(height: 14),
            const Text('لا يوجد طلاب مسجلون', style: TextStyle(fontFamily: 'Cairo', fontSize: 15, color: AppTheme.textSecondary)),
            const SizedBox(height: 8),
            const Text('سجل طالباً من تبويب المؤسسات', style: TextStyle(fontFamily: 'Cairo', fontSize: 12, color: AppTheme.textSecondary)),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: () async {
        ref.read(educationProvider.notifier).loadInstitutions();
      },
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: state.registeredStudents.length,
        itemBuilder: (context, index) {
          final student = state.registeredStudents[index];
          return Container(
            margin: const EdgeInsets.only(bottom: 12),
            decoration: AppTheme.cardDecoration,
            child: InkWell(
              borderRadius: AppTheme.radiusLg,
              onTap: () => _showStudentDetail(context, student),
              child: Padding(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 14),
                child: Row(
                  children: [
                    Container(
                      width: 48,
                      height: 48,
                      decoration: BoxDecoration(
                        color: AppTheme.primaryLight.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Center(
                        child: Text(
                          (student['full_name_ar'] as String?)?.substring(0, 1).toUpperCase() ?? '?',
                          style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.primary, fontWeight: FontWeight.bold, fontSize: 18),
                        ),
                      ),
                    ),
                    const SizedBox(width: 14),
                    Expanded(
                      child: Column(
                        crossAxisAlignment: CrossAxisAlignment.start,
                        children: [
                          Text(student['full_name_ar'] ?? '', style: const TextStyle(fontFamily: 'Cairo', fontSize: 15, fontWeight: FontWeight.w600, color: AppTheme.textPrimary)),
                          const SizedBox(height: 4),
                          Text('${student['student_id'] ?? ''} ${student['grade'] != null ? '| ${student['grade']}' : ''}', style: const TextStyle(fontFamily: 'Cairo', fontSize: 13, color: AppTheme.textSecondary)),
                        ],
                      ),
                    ),
                    const Icon(Icons.chevron_left, color: AppTheme.textSecondary),
                  ],
                ),
              ),
            ),
          );
        },
      ),
    );
  }

  void _showStudentDetail(BuildContext context, Map<String, dynamic> student) {
    ref.read(educationProvider.notifier).loadStudentFees(student['student_id']);
    final feeTypeCtrl = TextEditingController();
    final amountCtrl = TextEditingController();
    final dueDateCtrl = TextEditingController();

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: ui.TextDirection.rtl,
        child: StatefulBuilder(
          builder: (ctx, setDialogState) {
            final currentState = ref.watch(educationProvider);
            return AlertDialog(
              title: Text('الطالب: ${student['full_name_ar'] ?? ''}'),
              content: SizedBox(
                width: double.maxFinite,
                child: SingleChildScrollView(
                  child: Column(
                    mainAxisSize: MainAxisSize.min,
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text('رقم الطالب: ${student['student_id'] ?? ''}', style: const TextStyle(fontFamily: 'Cairo')),
                      const Divider(),
                      const Text('الرسوم:', style: TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold)),
                      const SizedBox(height: 8),
                      if (currentState.isLoading)
                        const Center(child: CircularProgressIndicator())
                      else if (currentState.currentFees.isEmpty)
                        const Text('لا توجد رسوم', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary))
                      else
                        ...currentState.currentFees.map((fee) => Container(
                              margin: const EdgeInsets.only(bottom: 8),
                              padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 10),
                              decoration: BoxDecoration(
                                color: Colors.white,
                                borderRadius: BorderRadius.circular(12),
                                boxShadow: [
                                  BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 6, offset: const Offset(0, 2)),
                                ],
                              ),
                              child: Row(
                                children: [
                                  Expanded(
                                    child: Column(
                                      crossAxisAlignment: CrossAxisAlignment.start,
                                      children: [
                                        Text(fee['fee_type'] ?? '', style: const TextStyle(fontFamily: 'Cairo', fontSize: 13, fontWeight: FontWeight.w500)),
                                        const SizedBox(height: 2),
                                        Text('${NumberFormat('#,###').format(fee['amount'])} ل.س', style: const TextStyle(fontFamily: 'Cairo', fontSize: 12, color: AppTheme.textSecondary)),
                                      ],
                                    ),
                                  ),
                                  GestureDetector(
                                    onTap: fee['status'] != 'paid' ? () => _payFeeDialog(context, fee) : null,
                                    child: Container(
                                      padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                                      decoration: BoxDecoration(
                                        color: (fee['status'] == 'paid' ? AppTheme.success : AppTheme.warning).withValues(alpha: 0.1),
                                        borderRadius: BorderRadius.circular(8),
                                      ),
                                      child: Text(
                                        fee['status'] == 'paid' ? 'مدفوع' : 'غير مدفوع',
                                        style: TextStyle(
                                          fontFamily: 'Cairo',
                                          fontSize: 12,
                                          color: fee['status'] == 'paid' ? AppTheme.success : AppTheme.warning,
                                          fontWeight: FontWeight.w600,
                                        ),
                                      ),
                                    ),
                                  ),
                                ],
                              ),
                            )),
                      const Divider(),
                      const Text('إنشاء رسم جديد:', style: TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold)),
                      const SizedBox(height: 8),
                      TextField(
                        controller: feeTypeCtrl,
                        decoration: const InputDecoration(labelText: 'نوع الرسم', hintText: 'مثال: تسجيل, قسط'),
                      ),
                      const SizedBox(height: 8),
                      TextField(
                        controller: amountCtrl,
                        decoration: const InputDecoration(labelText: 'المبلغ (ل.س)'),
                        keyboardType: TextInputType.number,
                      ),
                      const SizedBox(height: 8),
                      TextField(
                        controller: dueDateCtrl,
                        decoration: const InputDecoration(labelText: 'تاريخ الاستحقاق (YYYY-MM-DD)'),
                      ),
                      const SizedBox(height: 12),
                      ElevatedButton(
                        onPressed: () async {
                          if (feeTypeCtrl.text.isEmpty || amountCtrl.text.isEmpty || dueDateCtrl.text.isEmpty) return;
                          await ref.read(educationProvider.notifier).createFee(
                            studentId: student['student_id'],
                            feeType: feeTypeCtrl.text,
                            amount: int.tryParse(amountCtrl.text) ?? 0,
                            dueDate: dueDateCtrl.text,
                          );
                          feeTypeCtrl.clear();
                          amountCtrl.clear();
                          dueDateCtrl.clear();
                        },
                        child: const Text('إضافة رسم'),
                      ),
                    ],
                  ),
                ),
              ),
              actions: [
                TextButton(
                  onPressed: () {
                    ref.read(educationProvider.notifier).clearFees();
                    Navigator.pop(ctx);
                  },
                  child: const Text('إغلاق'),
                ),
              ],
            );
          },
        ),
      ),
    );
  }

  void _payFeeDialog(BuildContext context, Map<String, dynamic> fee) {
    final amountCtrl = TextEditingController(text: fee['amount'].toString());

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: ui.TextDirection.rtl,
        child: AlertDialog(
          title: const Text('دفع الرسم'),
          content: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              Text('نوع الرسم: ${fee['fee_type'] ?? ''}', style: const TextStyle(fontFamily: 'Cairo')),
              const SizedBox(height: 8),
              TextField(
                controller: amountCtrl,
                decoration: const InputDecoration(labelText: 'المبلغ'),
                keyboardType: TextInputType.number,
              ),
            ],
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
                await ref.read(educationProvider.notifier).payFee(fee['id'], amount: amount);
                if (ctx.mounted) Navigator.pop(ctx);
              },
              child: const Text('دفع'),
            ),
          ],
        ),
      ),
    );
  }
}

class _FeesTab extends StatelessWidget {
  final EducationState state;
  final WidgetRef ref;

  const _FeesTab({required this.state, required this.ref});

  @override
  Widget build(BuildContext context) {
    if (state.currentFees.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
              child: Icon(Icons.receipt_long_outlined, size: 36, color: AppTheme.textSecondary.withValues(alpha: 0.5)),
            ),
            const SizedBox(height: 14),
            const Text('لا توجد رسوم', style: TextStyle(fontFamily: 'Cairo', fontSize: 15, color: AppTheme.textSecondary)),
            const SizedBox(height: 8),
            const Text('اختر طالباً لعرض رسومه', style: TextStyle(fontFamily: 'Cairo', fontSize: 12, color: AppTheme.textSecondary)),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: () async {
        if (state.selectedStudentId != null) {
          ref.read(educationProvider.notifier).loadStudentFees(state.selectedStudentId!);
        }
      },
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: state.currentFees.length,
        itemBuilder: (context, index) {
          final fee = state.currentFees[index];
          final isPaid = fee['status'] == 'paid';
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
                    color: (isPaid ? AppTheme.success : AppTheme.warning).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Icon(
                    isPaid ? Icons.check_circle : Icons.pending,
                    color: isPaid ? AppTheme.success : AppTheme.warning,
                    size: 22,
                  ),
                ),
                const SizedBox(width: 12),
                Expanded(
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(fee['fee_type'] as String? ?? '', style: const TextStyle(fontFamily: 'Cairo', fontSize: 14, fontWeight: FontWeight.w500, color: AppTheme.textPrimary)),
                      const SizedBox(height: 4),
                      Text('${NumberFormat('#,###').format(fee['amount'])} ل.س', style: const TextStyle(fontFamily: 'Cairo', fontSize: 13, color: AppTheme.textSecondary)),
                    ],
                  ),
                ),
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                  decoration: BoxDecoration(
                    color: (isPaid ? AppTheme.success : AppTheme.warning).withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    isPaid ? 'مدفوع' : 'غير مدفوع',
                    style: TextStyle(
                      fontFamily: 'Cairo',
                      color: isPaid ? AppTheme.success : AppTheme.warning,
                      fontWeight: FontWeight.w600,
                      fontSize: 12,
                    ),
                  ),
                ),
              ],
            ),
          );
        },
      ),
    );
  }
}
