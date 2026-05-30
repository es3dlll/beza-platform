import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/theme/app_theme.dart';
import '../providers/remittance_provider.dart';

class RemittanceScreen extends ConsumerStatefulWidget {
  const RemittanceScreen({super.key});

  @override
  ConsumerState<RemittanceScreen> createState() => _RemittanceScreenState();
}

class _RemittanceScreenState extends ConsumerState<RemittanceScreen> {
  final _amountController = TextEditingController();
  final _fundsController = TextEditingController();
  final _senderNameController = TextEditingController();
  final _senderPhoneController = TextEditingController();
  final _beneficiaryNameController = TextEditingController();
  final _beneficiaryNameEnController = TextEditingController();
  final _beneficiaryPhoneController = TextEditingController();
  final _beneficiaryNationalIdController = TextEditingController();
  final _beneficiaryGovernorateController = TextEditingController();
  final _beneficiaryCityController = TextEditingController();
  final _beneficiaryAddressController = TextEditingController();

  String _selectedRelationship = 'family';
  String _selectedPurpose = 'FAMILY_SUPPORT';
  String _selectedPayoutMethod = 'agent';

  @override
  void initState() {
    super.initState();
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(remittanceProvider.notifier).loadCorridors();
      ref.read(remittanceProvider.notifier).loadBeneficiaries();
    });
  }

  @override
  void dispose() {
    _amountController.dispose();
    _fundsController.dispose();
    _senderNameController.dispose();
    _senderPhoneController.dispose();
    _beneficiaryNameController.dispose();
    _beneficiaryNameEnController.dispose();
    _beneficiaryPhoneController.dispose();
    _beneficiaryNationalIdController.dispose();
    _beneficiaryGovernorateController.dispose();
    _beneficiaryCityController.dispose();
    _beneficiaryAddressController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(remittanceProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('الحوالات')),
      body: Column(
        children: [
          _buildTabToggle(state),
          Expanded(
            child: state.activeTab == ActiveTab.newTransfer
                ? _buildNewTransfer(state)
                : _buildOrdersHistory(state),
          ),
        ],
      ),
    );
  }

  Widget _buildTabToggle(RemittanceState state) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(16, 16, 16, 0),
      child: Row(
        children: [
          Expanded(
            child: _buildTabButton(
              'إرسال جديد',
              state.activeTab == ActiveTab.newTransfer,
              () =>
                  ref.read(remittanceProvider.notifier).setActiveTab(ActiveTab.newTransfer),
            ),
          ),
          const SizedBox(width: 8),
          Expanded(
            child: _buildTabButton(
              'الطلبات السابقة',
              state.activeTab == ActiveTab.orders,
              () =>
                  ref.read(remittanceProvider.notifier).setActiveTab(ActiveTab.orders),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildTabButton(String text, bool isActive, VoidCallback onTap) {
    return GestureDetector(
      onTap: onTap,
      child: Container(
        padding: const EdgeInsets.symmetric(vertical: 12),
        decoration: BoxDecoration(
          color: isActive ? AppTheme.primary : Colors.grey[200],
          borderRadius: BorderRadius.circular(12),
        ),
        child: Center(
          child: Text(
            text,
            style: TextStyle(
              fontFamily: 'Cairo',
              color: isActive ? Colors.white : AppTheme.textPrimary,
              fontWeight: FontWeight.w600,
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildNewTransfer(RemittanceState state) {
    if (state.isSuccess) {
      return _buildSuccessView(state);
    }
    if (      state.isLoading && state.corridors.isEmpty) {
      return Center(
        child: Shimmer.fromColors(
          baseColor: AppTheme.shimmer,
          highlightColor: AppTheme.surfaceContainerLow,
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: 3,
            itemBuilder: (_, _) => Container(
              margin: const EdgeInsets.only(bottom: 12),
              height: 100,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ),
      );
    }
    return Directionality(
      textDirection: TextDirection.rtl,
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            _buildStepIndicator(state.currentStep),
            const SizedBox(height: 24),
            if (state.error != null) _buildErrorBanner(state.error!),
            if (state.currentStep == 0) _buildStepCorridors(state),
            if (state.currentStep == 1) _buildStepBeneficiaries(state),
            if (state.currentStep == 2) _buildStepAmount(state),
            if (state.currentStep == 3) _buildStepConfirm(state),
          ],
        ),
      ),
    );
  }

  Widget _buildErrorBanner(String message) {
    return Container(
      width: double.infinity,
      margin: const EdgeInsets.only(bottom: 16),
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppTheme.error.withValues(alpha: 0.1),
        borderRadius: BorderRadius.circular(8),
      ),
      child: Row(
        children: [
          const Icon(Icons.error_outline, color: AppTheme.error, size: 20),
          const SizedBox(width: 8),
          Expanded(
            child: Text(message, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.error)),
          ),
        ],
      ),
    );
  }

  Widget _buildStepIndicator(int currentStep) {
    const steps = ['الممر', 'المستفيد', 'المبلغ', 'التأكيد'];
    return Row(
      textDirection: TextDirection.rtl,
      children: List.generate(steps.length * 2 - 1, (i) {
        if (i.isOdd) {
          final stepIdx = i ~/ 2;
          return Expanded(
            child: Container(
              height: 2,
              color: stepIdx < currentStep ? AppTheme.primary : Colors.grey[300],
            ),
          );
        }
        final stepIdx = i ~/ 2;
        final isActive = stepIdx <= currentStep;
        final isCurrent = stepIdx == currentStep;
        return Container(
          width: isCurrent ? 40 : 32,
          height: isCurrent ? 40 : 32,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: isActive ? AppTheme.primary : Colors.grey[300],
          ),
          child: Center(
            child: isCurrent
                ? const Icon(Icons.circle, color: Colors.white, size: 12)
                : stepIdx < currentStep
                    ? const Icon(Icons.check, color: Colors.white, size: 16)
                    : Text(
                        '${stepIdx + 1}',
                        style: const TextStyle(
                          fontFamily: 'Cairo',
                          color: Colors.white,
                          fontSize: 14,
                          fontWeight: FontWeight.bold,
                        ),
                      ),
          ),
        );
      }),
    );
  }

  Widget _buildStepHeader(String title, String subtitle) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Text(title, style: const TextStyle(fontFamily: 'Cairo', fontSize: 20, fontWeight: FontWeight.bold)),
        const SizedBox(height: 4),
        Text(subtitle, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
        const SizedBox(height: 16),
      ],
    );
  }

  Widget _buildStepCorridors(RemittanceState state) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        _buildStepHeader('اختيار الممر', 'اختر الممر الذي تريد التحويل عبره'),
        if (state.corridors.isEmpty && !state.isLoading)
          const Center(child: Text('لا توجد ممرات متاحة', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)))
        else
          ...state.corridors.map(
            (c) => _buildCorridorCard(c, c['id'] == state.selectedCorridor?['id']),
          ),
      ],
    );
  }

  Widget _buildCorridorCard(Map<String, dynamic> corridor, bool isSelected) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: AppTheme.cardDecoration.copyWith(
        border: isSelected ? Border.all(color: AppTheme.primary, width: 2) : null,
      ),
      child: InkWell(
        onTap: () => ref.read(remittanceProvider.notifier).selectCorridor(corridor),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '${corridor['source_country'] ?? ''} ← ${corridor['target_currency'] ?? ''}',
                      style: const TextStyle(fontFamily: 'Cairo', fontSize: 16, fontWeight: FontWeight.w600),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      '${corridor['source_currency'] ?? ''} → ${corridor['target_currency'] ?? ''}',
                      style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      'الحد الأدنى: ${corridor['min_amount'] ?? 0}  •  الحد الأقصى: ${corridor['max_amount'] ?? 0}',
                      style: const TextStyle(fontFamily: 'Cairo', fontSize: 12, color: AppTheme.textSecondary),
                    ),
                    if (corridor['supported_payout_methods'] != null)
                      Padding(
                        padding: const EdgeInsets.only(top: 4),
                        child: Text(
                          'طرق الدفع: ${(corridor['supported_payout_methods'] as List).join(' - ')}',
                          style: const TextStyle(fontFamily: 'Cairo', fontSize: 12, color: AppTheme.primaryLight),
                        ),
                      ),
                  ],
                ),
              ),
              if (isSelected) const Icon(Icons.check_circle, color: AppTheme.primary),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildStepBeneficiaries(RemittanceState state) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            IconButton(
              icon: const Icon(Icons.arrow_forward),
              onPressed: () => ref.read(remittanceProvider.notifier).previousStep(),
            ),
            Expanded(
              child: _buildStepHeader('اختيار المستفيد', 'اختر مستفيداً أو أضف مستفيداً جديداً'),
            ),
          ],
        ),
        if (state.beneficiaries.isEmpty && !state.isLoading)
          const Center(child: Text('لا يوجد مستفيدون', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)))
        else
          ...state.beneficiaries.map(
            (b) => _buildBeneficiaryCard(b, b['id'] == state.selectedBeneficiary?['id']),
          ),
        const SizedBox(height: 16),
        SizedBox(
          width: double.infinity,
          child: OutlinedButton.icon(
            onPressed: _showAddBeneficiarySheet,
            icon: const Icon(Icons.person_add),
            label: const Text('إضافة مستفيد جديد'),
          ),
        ),
        if (state.selectedBeneficiary != null) ...[
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: () => ref.read(remittanceProvider.notifier).nextStep(),
              child: const Text('التالي'),
            ),
          ),
        ],
      ],
    );
  }

  Widget _buildBeneficiaryCard(Map<String, dynamic> beneficiary, bool isSelected) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: AppTheme.cardDecoration.copyWith(
        border: isSelected ? Border.all(color: AppTheme.primary, width: 2) : null,
      ),
      child: InkWell(
        onTap: () => ref.read(remittanceProvider.notifier).selectBeneficiary(beneficiary),
        borderRadius: BorderRadius.circular(12),
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              const CircleAvatar(
                backgroundColor: AppTheme.primaryLight,
                child: Icon(Icons.person, color: Colors.white),
              ),
              const SizedBox(width: 12),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      beneficiary['full_name_ar'] as String? ?? '',
                    style: const TextStyle(fontFamily: 'Cairo', fontSize: 16, fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 4),
                    Text(
                      _relationshipLabel(beneficiary['relationship'] as String? ?? ''),
                      style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary),
                    ),
                    Text(
                      beneficiary['phone'] as String? ?? '',
                      style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 12),
                    ),
                  ],
                ),
              ),
              if (isSelected) const Icon(Icons.check_circle, color: AppTheme.primary),
            ],
          ),
        ),
      ),
    );
  }

  String _relationshipLabel(String rel) {
    const labels = {
      'family': 'عائلة',
      'friend': 'صديق',
      'colleague': 'زميل عمل',
      'client': 'عميل',
      'other': 'آخر',
    };
    return labels[rel] ?? rel;
  }

  void _showAddBeneficiarySheet() {
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (_) => _buildAddBeneficiarySheet(),
    );
  }

  Widget _buildAddBeneficiarySheet() {
    final bottom = MediaQuery.of(context).viewInsets.bottom;
    return Padding(
      padding: EdgeInsets.fromLTRB(24, 24, 24, 24 + bottom),
      child: Directionality(
        textDirection: TextDirection.rtl,
        child: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text(
                'إضافة مستفيد جديد',
                style: TextStyle(fontFamily: 'Cairo', fontSize: 20, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 16),
              TextField(
                controller: _beneficiaryNameController,
                decoration: const InputDecoration(
                  labelText: 'الاسم الكامل',
                  hintText: 'الاسم بالعربية',
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _beneficiaryNameEnController,
                decoration: const InputDecoration(
                  labelText: 'الاسم بالإنجليزية (اختياري)',
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _beneficiaryPhoneController,
                decoration: const InputDecoration(
                  labelText: 'رقم الهاتف',
                  hintText: '09xxxxxxxx',
                ),
                keyboardType: TextInputType.phone,
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _beneficiaryNationalIdController,
                decoration: const InputDecoration(
                  labelText: 'رقم الهوية (اختياري)',
                ),
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                initialValue: _selectedRelationship,
                decoration: const InputDecoration(labelText: 'صلة القرابة'),
                items: const [
                  DropdownMenuItem(value: 'family', child: Text('عائلة')),
                  DropdownMenuItem(value: 'friend', child: Text('صديق')),
                  DropdownMenuItem(value: 'colleague', child: Text('زميل عمل')),
                  DropdownMenuItem(value: 'client', child: Text('عميل')),
                  DropdownMenuItem(value: 'other', child: Text('آخر')),
                ],
                onChanged: (v) {
                  if (v != null) {
                    setState(() => _selectedRelationship = v);
                  }
                },
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _beneficiaryGovernorateController,
                decoration: const InputDecoration(
                  labelText: 'المحافظة (اختياري)',
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _beneficiaryCityController,
                decoration: const InputDecoration(
                  labelText: 'المدينة (اختياري)',
                ),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: _beneficiaryAddressController,
                decoration: const InputDecoration(
                  labelText: 'العنوان (اختياري)',
                ),
              ),
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () {
                    if (_beneficiaryNameController.text.isEmpty ||
                        _beneficiaryPhoneController.text.isEmpty) {
                      return;
                    }
                    ref.read(remittanceProvider.notifier).addBeneficiary({
                      'full_name_ar': _beneficiaryNameController.text,
                      if (_beneficiaryNameEnController.text.isNotEmpty)
                        'full_name_en': _beneficiaryNameEnController.text,
                      'phone': _beneficiaryPhoneController.text,
                      if (_beneficiaryNationalIdController.text.isNotEmpty)
                        'national_id': _beneficiaryNationalIdController.text,
                      'relationship': _selectedRelationship,
                      if (_beneficiaryGovernorateController.text.isNotEmpty)
                        'governorate': _beneficiaryGovernorateController.text,
                      if (_beneficiaryCityController.text.isNotEmpty)
                        'city': _beneficiaryCityController.text,
                      if (_beneficiaryAddressController.text.isNotEmpty)
                        'address': _beneficiaryAddressController.text,
                    });
                    _clearBeneficiaryFields();
                    Navigator.pop(context);
                  },
                  child: const Text('حفظ'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  void _clearBeneficiaryFields() {
    _beneficiaryNameController.clear();
    _beneficiaryNameEnController.clear();
    _beneficiaryPhoneController.clear();
    _beneficiaryNationalIdController.clear();
    _beneficiaryGovernorateController.clear();
    _beneficiaryCityController.clear();
    _beneficiaryAddressController.clear();
    _selectedRelationship = 'family';
  }

  Widget _buildStepAmount(RemittanceState state) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            IconButton(
              icon: const Icon(Icons.arrow_forward),
              onPressed: () => ref.read(remittanceProvider.notifier).previousStep(),
            ),
            Expanded(
              child: _buildStepHeader('إدخال المبلغ', 'أدخل المبلغ المراد تحويله'),
            ),
          ],
        ),
        TextField(
          controller: _amountController,
          decoration: InputDecoration(
            labelText: 'المبلغ',
            suffixText: state.selectedCorridor?['source_currency'] as String? ?? '',
          ),
          keyboardType: TextInputType.number,
          onChanged: (v) {
            final amount = int.tryParse(v) ?? 0;
            ref.read(remittanceProvider.notifier).setAmount(amount);
          },
        ),
        const SizedBox(height: 8),
        if (state.sourceAmount > 0)
          Container(
            decoration: AppTheme.cardDecoration,
            child: Padding(
              padding: const EdgeInsets.all(16),
              child: Row(
                children: [
                  const Icon(Icons.info_outline, color: AppTheme.primary),
                  const SizedBox(width: 8),
                  Expanded(
                    child: Text(
                      'المبلغ المقدر للمستفيد: ~${state.sourceAmount ~/ 100} ${state.selectedCorridor?['target_currency'] as String? ?? ''}',
                      style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w500),
                    ),
                  ),
                ],
              ),
            ),
          ),
        const SizedBox(height: 16),
        DropdownButtonFormField<String>(
          initialValue: _selectedPurpose,
          decoration: const InputDecoration(labelText: 'الغرض من التحويل'),
          items: const [
            DropdownMenuItem(value: 'FAMILY_SUPPORT', child: Text('دعم عائلي')),
            DropdownMenuItem(value: 'SALARY', child: Text('راتب')),
            DropdownMenuItem(value: 'EDUCATION', child: Text('تعليم')),
            DropdownMenuItem(value: 'MEDICAL', child: Text('طبي')),
            DropdownMenuItem(value: 'SAVINGS', child: Text('ادخار')),
            DropdownMenuItem(value: 'INVESTMENT', child: Text('استثمار')),
            DropdownMenuItem(value: 'BUSINESS', child: Text('أعمال')),
            DropdownMenuItem(value: 'CHARITY', child: Text('صدقة')),
            DropdownMenuItem(value: 'OTHER', child: Text('آخر')),
          ],
          onChanged: (v) {
            if (v != null) {
              setState(() => _selectedPurpose = v);
              ref.read(remittanceProvider.notifier).setPurposeCode(v);
            }
          },
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _fundsController,
          decoration: const InputDecoration(
            labelText: 'مصدر الأموال',
            hintText: 'مثال: راتب شهري',
          ),
          maxLines: 2,
          onChanged: (v) =>
              ref.read(remittanceProvider.notifier).setSourceOfFunds(v),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _senderNameController,
          decoration: const InputDecoration(labelText: 'اسم المرسل'),
          onChanged: (v) => ref.read(remittanceProvider.notifier).setSenderInfo(
                v,
                _senderPhoneController.text,
              ),
        ),
        const SizedBox(height: 12),
        TextField(
          controller: _senderPhoneController,
          decoration: const InputDecoration(
            labelText: 'رقم المرسل',
            hintText: '09xxxxxxxx',
          ),
          keyboardType: TextInputType.phone,
          onChanged: (v) => ref.read(remittanceProvider.notifier).setSenderInfo(
                _senderNameController.text,
                v,
              ),
        ),
        const SizedBox(height: 12),
        DropdownButtonFormField<String>(
          initialValue: _selectedPayoutMethod,
          decoration: const InputDecoration(labelText: 'طريقة الدفع'),
          items: const [
            DropdownMenuItem(value: 'agent', child: Text('وكيل')),
            DropdownMenuItem(value: 'wallet', child: Text('محفظة')),
            DropdownMenuItem(value: 'bank', child: Text('بنك')),
          ],
          onChanged: (v) {
            if (v != null) {
              setState(() => _selectedPayoutMethod = v);
              ref.read(remittanceProvider.notifier).setPayoutMethod(v);
            }
          },
        ),
        const SizedBox(height: 24),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: state.sourceAmount > 0 &&
                    _fundsController.text.isNotEmpty &&
                    _senderNameController.text.isNotEmpty &&
                    _senderPhoneController.text.isNotEmpty
                ? () => ref.read(remittanceProvider.notifier).nextStep()
                : null,
            child: const Text('التالي'),
          ),
        ),
      ],
    );
  }

  Widget _buildStepConfirm(RemittanceState state) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Row(
          children: [
            IconButton(
              icon: const Icon(Icons.arrow_forward),
              onPressed: () => ref.read(remittanceProvider.notifier).previousStep(),
            ),
            Expanded(
              child: _buildStepHeader('تأكيد التحويل', 'يرجى مراجعة البيانات قبل التأكيد'),
            ),
          ],
        ),
        Container(
          decoration: AppTheme.cardDecoration,
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              children: [
                _buildSummaryRow(
                  'الممر',
                  '${state.selectedCorridor?['source_country']} ← ${state.selectedCorridor?['target_currency']}',
                ),
                const Divider(height: 1),
                _buildSummaryRow(
                  'المستفيد',
                  state.selectedBeneficiary?['full_name_ar'] as String? ?? '',
                ),
                const Divider(height: 1),
                _buildSummaryRow(
                  'المبلغ',
                  '${state.sourceAmount} ${state.selectedCorridor?['source_currency']}',
                ),
                const Divider(height: 1),
                _buildSummaryRow(
                  'المبلغ المقدر للمستفيد',
                  '~${state.sourceAmount ~/ 100} ${state.selectedCorridor?['target_currency']}',
                ),
                const Divider(height: 1),
                _buildSummaryRow('الغرض', _purposeLabel(state.purposeCode)),
                const Divider(height: 1),
                _buildSummaryRow('مصدر الأموال', state.sourceOfFunds),
                const Divider(height: 1),
                _buildSummaryRow('المرسل', state.senderFullName),
                const Divider(height: 1),
                _buildSummaryRow('رقم المرسل', state.senderPhone),
                const Divider(height: 1),
                _buildSummaryRow('طريقة الدفع', _payoutLabel(state.payoutMethod)),
              ],
            ),
          ),
        ),
        const SizedBox(height: 24),
        SizedBox(
          width: double.infinity,
          child: ElevatedButton(
            onPressed: state.isLoading
                ? null
                : () => ref.read(remittanceProvider.notifier).submitOrder(),
            child: state.isLoading
                ? const SizedBox(
                    width: 20,
                    height: 20,
                    child: CircularProgressIndicator(
                      strokeWidth: 2,
                      color: Colors.white,
                    ),
                  )
                : const Text('تأكيد التحويل'),
          ),
        ),
        const SizedBox(height: 8),
        SizedBox(
          width: double.infinity,
          child: TextButton(
            onPressed: () => ref.read(remittanceProvider.notifier).previousStep(),
            child: const Text('تعديل'),
          ),
        ),
      ],
    );
  }

  Widget _buildSummaryRow(String label, String initialValue) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Text('$label: ', style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
          const SizedBox(width: 4),
          Expanded(
            child: Text(
              initialValue,
              style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w500),
              textAlign: TextAlign.end,
            ),
          ),
        ],
      ),
    );
  }

  String _purposeLabel(String code) {
    const labels = {
      'FAMILY_SUPPORT': 'دعم عائلي',
      'SALARY': 'راتب',
      'EDUCATION': 'تعليم',
      'MEDICAL': 'طبي',
      'SAVINGS': 'ادخار',
      'INVESTMENT': 'استثمار',
      'BUSINESS': 'أعمال',
      'CHARITY': 'صدقة',
      'OTHER': 'آخر',
    };
    return labels[code] ?? code;
  }

  String _payoutLabel(String method) {
    const labels = {
      'agent': 'وكيل',
      'wallet': 'محفظة',
      'bank': 'بنك',
    };
    return labels[method] ?? method;
  }

  Widget _buildSuccessView(RemittanceState state) {
    return Directionality(
      textDirection: TextDirection.rtl,
      child: Center(
        child: Padding(
          padding: const EdgeInsets.all(32),
          child: Column(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const Icon(Icons.check_circle, color: AppTheme.primaryLight, size: 80),
              const SizedBox(height: 24),
              const Text(
                'تمت الحوالة بنجاح',
                style: TextStyle(fontFamily: 'Cairo', fontSize: 24, fontWeight: FontWeight.bold),
              ),
              const SizedBox(height: 8),
              Text(
                'رقم المرجع: ${state.referenceNumber ?? ''}',
                style: const TextStyle(fontFamily: 'Cairo', fontSize: 16, color: AppTheme.textSecondary),
              ),
              const SizedBox(height: 32),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () {
                    ref.read(remittanceProvider.notifier).reset();
                  },
                  child: const Text('إرسال حوالة جديدة'),
                ),
              ),
              const SizedBox(height: 8),
              SizedBox(
                width: double.infinity,
                child: TextButton(
                  onPressed: () {
                    ref.read(remittanceProvider.notifier).reset();
                    ref.read(remittanceProvider.notifier).setActiveTab(ActiveTab.orders);
                  },
                  child: const Text('الطلبات السابقة'),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildOrdersHistory(RemittanceState state) {
    if (state.isLoading) {
      return Center(
        child: Shimmer.fromColors(
          baseColor: AppTheme.shimmer,
          highlightColor: AppTheme.surfaceContainerLow,
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: 3,
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
      );
    }
    if (state.error != null) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: const BoxDecoration(color: AppTheme.errorLight, shape: BoxShape.circle),
              child: const Icon(Icons.error_outline, size: 40, color: AppTheme.error),
            ),
            const SizedBox(height: 16),
            Text(state.error!, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.error)),
            const SizedBox(height: 16),
            ElevatedButton(
              onPressed: () =>
                  ref.read(remittanceProvider.notifier).setActiveTab(ActiveTab.orders),
              child: const Text('إعادة المحاولة'),
            ),
          ],
        ),
      );
    }
    if (state.orders.isEmpty) {
      return Center(
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
              child: const Icon(Icons.receipt_long, size: 36, color: AppTheme.textSecondary),
            ),
            const SizedBox(height: 16),
            Text(
              'لا توجد طلبات سابقة',
              style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary),
            ),
          ],
        ),
      );
    }
    return Directionality(
      textDirection: TextDirection.rtl,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: state.orders.length,
        itemBuilder: (_, i) => _buildOrderCard(state.orders[i]),
      ),
    );
  }

  Widget _buildOrderCard(Map<String, dynamic> order) {
    final status = order['status'] as String? ?? '';
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: AppTheme.cardDecoration,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Row(
              children: [
                _buildStatusBadge(status),
                const Spacer(),
                Text(
                  order['reference_number'] as String? ?? '',
                  style: const TextStyle(
                    fontFamily: 'Cairo',
                    fontWeight: FontWeight.bold,
                    color: AppTheme.textSecondary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 8),
            Row(
              children: [
                Text(
                '${order['source_amount'] ?? 0} ${order['source_currency'] ?? ''}',
                style: const TextStyle(fontFamily: 'Cairo', fontSize: 18, fontWeight: FontWeight.bold),
                ),
                const SizedBox(width: 8),
                Text(
                  '→ ${order['target_amount'] ?? 0} ${order['target_currency'] ?? ''}',
                style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary),
              ),
            ],
          ),
          const SizedBox(height: 4),
          Text(
            order['sender_full_name'] as String? ?? '',
            style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary),
            ),
            if (order['completed_at'] != null)
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Text(
                  order['completed_at'] as String,
                  style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 12),
                ),
              ),
            if (order['failure_reason'] != null)
              Padding(
                padding: const EdgeInsets.only(top: 4),
                child: Text(
                  order['failure_reason'] as String,
                  style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.error, fontSize: 12),
                ),
              ),
          ],
        ),
      ),
    );
  }

  Widget _buildStatusBadge(String status) {
    Color color;
    String label;
    switch (status) {
      case 'completed':
        color = AppTheme.success;
        label = 'مكتمل';
      case 'failed':
        color = AppTheme.error;
        label = 'فاشل';
      case 'refunded':
        color = AppTheme.warning;
        label = 'مسترجع';
      case 'paid_in':
        color = AppTheme.info;
        label = 'تم الدفع';
      case 'quoted':
        color = AppTheme.info;
        label = 'تم التسعير';
      case 'screened':
        color = AppTheme.info;
        label = 'قيد المراجعة';
      default:
        color = AppTheme.info;
        label = 'تم الإنشاء';
    }
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
      decoration: BoxDecoration(
        color: color.withValues(alpha: 0.15),
        borderRadius: BorderRadius.circular(20),
      ),
      child: Text(
        label,
        style: TextStyle(fontFamily: 'Cairo', color: color, fontSize: 12, fontWeight: FontWeight.w600),
      ),
    );
  }
}
