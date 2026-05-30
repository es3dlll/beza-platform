import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shimmer/shimmer.dart';
import 'package:qr_flutter/qr_flutter.dart';
import '../../../core/theme/app_theme.dart';
import '../providers/merchant_provider.dart';

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

const _categories = [
  'متجر تجزئة',
  'مطعم',
  'خدمة',
  'جملة',
  'أخرى',
];

String _formatSyp(int amount) => '${amount ~/ 100} ل.س';

class MerchantScreen extends ConsumerStatefulWidget {
  const MerchantScreen({super.key});
  @override
  ConsumerState<MerchantScreen> createState() => _MerchantScreenState();
}

class _MerchantScreenState extends ConsumerState<MerchantScreen>
    with TickerProviderStateMixin {
  late TabController _tabController;

  final _businessNameController = TextEditingController();
  final _businessNameArController = TextEditingController();
  final _phoneController = TextEditingController();
  String? _selectedGovernorate;
  String? _selectedCity;
  String? _selectedCategory;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _tabController.addListener(() {
      if (!_tabController.indexIsChanging) {
        ref.read(merchantProvider.notifier).setTab(_tabController.index);
      }
    });
    WidgetsBinding.instance.addPostFrameCallback((_) {
      ref.read(merchantProvider.notifier).loadProfile();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    _businessNameController.dispose();
    _businessNameArController.dispose();
    _phoneController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(merchantProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('التاجر')),
      body: state.isLoading
          ? _shimmerLoading()
          : state.isRegistered && state.profile != null
              ? _dashboard(state)
              : _registrationForm(state),
    );
  }

  Widget _shimmerLoading() {
    return Shimmer.fromColors(
      baseColor: AppTheme.shimmer,
      highlightColor: AppTheme.surfaceContainerLow,
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

  Widget _dashboard(MerchantState state) {
    final profile = state.profile!;
    return Column(
      children: [
        Container(
          color: Colors.white,
          padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 12),
          child: Row(
            children: [
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      profile['business_name_ar'] as String? ?? profile['business_name'] as String? ?? '',
                      style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w600, fontSize: 16),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      profile['governorate'] as String? ?? '',
                      style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 13),
                    ),
                  ],
                ),
              ),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: profile['status'] == 'active'
                      ? AppTheme.successLight
                      : profile['status'] == 'pending'
                          ? AppTheme.warningLight
                          : AppTheme.errorLight,
                  borderRadius: BorderRadius.circular(16),
                ),
                child: Text(
                  profile['status'] == 'active'
                      ? 'نشط'
                      : profile['status'] == 'pending'
                          ? 'قيد المراجعة'
                          : 'موقوف',
                  style: TextStyle(
                    fontFamily: 'Cairo',
                    color: profile['status'] == 'active'
                        ? AppTheme.success
                        : profile['status'] == 'pending'
                            ? AppTheme.warning
                            : AppTheme.error,
                    fontWeight: FontWeight.w600,
                    fontSize: 12,
                  ),
                ),
              ),
            ],
          ),
        ),
        TabBar(
          controller: _tabController,
          labelColor: AppTheme.primary,
          unselectedLabelColor: AppTheme.textSecondary,
          tabs: const [
            Tab(text: 'المدفوعات'),
            Tab(text: 'المتاجر'),
            Tab(text: 'QR'),
          ],
        ),
        Expanded(
          child: TabBarView(
            controller: _tabController,
            children: [
              _paymentsTab(state),
              _storesTab(state),
              _qrTab(state),
            ],
          ),
        ),
      ],
    );
  }

  Widget _paymentsTab(MerchantState state) {
    return RefreshIndicator(
      onRefresh: () => ref.read(merchantProvider.notifier).loadPayments(),
      child: state.payments.isEmpty
          ? ListView(
              children: [
                _emptyState('لا توجد مدفوعات', Icons.payment),
              ],
            )
          : ListView.builder(
              padding: const EdgeInsets.all(16),
              itemCount: state.payments.length,
              itemBuilder: (_, i) => _paymentCard(state.payments[i]),
            ),
    );
  }

  Widget _paymentCard(Map<String, dynamic> payment) {
    final status = payment['status'] as String? ?? '';
    final isCompleted = status == 'completed' || status == 'paid';
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: AppTheme.cardDecoration,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Row(
          children: [
            Expanded(
              child: Column(
                crossAxisAlignment: CrossAxisAlignment.start,
                children: [
                  Text(
                    'المبلغ: ${_formatSyp(payment['amount'] as int? ?? 0)}',
                    style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w600),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'الرسوم: ${_formatSyp(payment['mdr_fee'] as int? ?? 0)}',
                    style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 13),
                  ),
                  const SizedBox(height: 4),
                  Text(
                    'الصافي: ${_formatSyp(payment['net_amount'] as int? ?? 0)}',
                    style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 13),
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
                    color: isCompleted ? AppTheme.successLight : AppTheme.warningLight,
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Text(
                    isCompleted ? 'مكتمل' : 'قيد الانتظار',
                    style: TextStyle(
                      fontFamily: 'Cairo',
                      color: isCompleted ? AppTheme.success : AppTheme.warning,
                      fontSize: 12,
                      fontWeight: FontWeight.w600,
                    ),
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  payment['paid_at'] as String? ?? '',
                  style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 11),
                ),
              ],
            ),
          ],
        ),
      ),
    );
  }

  Widget _storesTab(MerchantState state) {
    return Stack(
      children: [
        RefreshIndicator(
          onRefresh: () => ref.read(merchantProvider.notifier).loadStores(),
          child: state.stores.isEmpty
              ? ListView(
                  children: [
                    _emptyState('لا توجد متاجر مسجلة', Icons.store),
                  ],
                )
              : ListView.builder(
                  padding: const EdgeInsets.all(16),
                  itemCount: state.stores.length,
                  itemBuilder: (_, i) => _storeCard(state.stores[i]),
                ),
        ),
        Positioned(
          right: 16,
          bottom: 16,
          child: FloatingActionButton(
            onPressed: () => _showCreateStoreDialog(),
            backgroundColor: AppTheme.primary,
            foregroundColor: Colors.white,
            child: const Icon(Icons.add),
          ),
        ),
      ],
    );
  }

  Widget _storeCard(Map<String, dynamic> store) {
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: AppTheme.cardDecoration,
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: AppTheme.primary.withValues(alpha: 0.1),
          child: const Icon(Icons.store, color: AppTheme.primary),
        ),
        title: Text(
          store['name_ar'] as String? ?? store['name'] as String? ?? '',
          style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w600),
        ),
        subtitle: Text(store['address'] as String? ?? ''),
        trailing: store['is_active'] == true
            ? Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                  color: AppTheme.successLight,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text('نشط', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.success, fontSize: 12, fontWeight: FontWeight.w600)),
              )
            : null,
      ),
    );
  }

  Widget _qrTab(MerchantState state) {
    return Center(
      child: SingleChildScrollView(
        padding: const EdgeInsets.all(24),
        child: Column(
          mainAxisAlignment: MainAxisAlignment.center,
          children: [
            const Text(
              'رمز الدفع الخاص بك',
              style: TextStyle(fontFamily: 'Cairo', fontSize: 18, fontWeight: FontWeight.w600),
            ),
            const SizedBox(height: 24),
            if (state.qrCode != null)
              Container(
                padding: const EdgeInsets.all(16),
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(16),
                  border: Border.all(color: AppTheme.divider),
                ),
                child: QrImageView(
                  data: state.qrCode!,
                  version: QrVersions.auto,
                  size: 220,
                  backgroundColor: Colors.white,
                ),
              )
            else
              ElevatedButton.icon(
                onPressed: () => ref.read(merchantProvider.notifier).loadQrCode(),
                icon: const Icon(Icons.qr_code),
                label: const Text('إنشاء رمز QR'),
              ),
            if (state.qrCode != null) ...[
              const SizedBox(height: 16),
              Text(
                state.qrCode!,
                style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 12),
                textAlign: TextAlign.center,
              ),
            ],
          ],
        ),
      ),
    );
  }

  Widget _emptyState(String message, IconData icon) {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(64),
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
              child: Icon(icon, size: 36, color: AppTheme.textSecondary.withValues(alpha: 0.5)),
            ),
            const SizedBox(height: 16),
            Text(message, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 16)),
          ],
        ),
      ),
    );
  }

  Widget _registrationForm(MerchantState state) {
    return SingleChildScrollView(
      padding: const EdgeInsets.all(24),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.stretch,
        children: [
          const Icon(Icons.storefront, size: 72, color: AppTheme.primary),
          const SizedBox(height: 16),
          Text(
            'سجل كتاجر',
            style: Theme.of(context).textTheme.titleLarge,
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 8),
            const Text(
              'انضم إلى شبكة التجار لدينا',
              style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary),
            textAlign: TextAlign.center,
          ),
          const SizedBox(height: 32),
          TextField(
            controller: _businessNameController,
            decoration: const InputDecoration(labelText: 'اسم المتجر (إنجليزي)'),
          ),
          const SizedBox(height: 16),
          TextField(
            controller: _businessNameArController,
            decoration: const InputDecoration(labelText: 'اسم المتجر (عربي)'),
          ),
          const SizedBox(height: 16),
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
          DropdownButtonFormField<String>(
            initialValue: _selectedCategory,
            decoration: const InputDecoration(labelText: 'الفئة'),
            items: _categories.map((c) => DropdownMenuItem(value: c, child: Text(c))).toList(),
            onChanged: (v) => setState(() => _selectedCategory = v),
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
    if (_businessNameController.text.trim().isEmpty ||
        _businessNameArController.text.trim().isEmpty ||
        _phoneController.text.trim().isEmpty ||
        _selectedGovernorate == null ||
        _selectedCity == null ||
        _selectedCategory == null) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('يرجى تعبئة جميع الحقول')),
      );
      return;
    }
    final success = await ref.read(merchantProvider.notifier).register({
      'business_name': _businessNameController.text.trim(),
      'business_name_ar': _businessNameArController.text.trim(),
      'phone': _phoneController.text.trim(),
      'governorate': _selectedGovernorate,
      'city': _selectedCity,
      'category': _selectedCategory,
    });
    if (success && mounted) {
      ScaffoldMessenger.of(context).showSnackBar(
        const SnackBar(content: Text('تم تقديم الطلب بنجاح')),
      );
    }
  }

  void _showCreateStoreDialog() {
    final nameController = TextEditingController();
    final nameArController = TextEditingController();
    final storePhoneController = TextEditingController();
    final addressController = TextEditingController();
    String? storeGovernorate;
    String? storeCity;

    showDialog(
      context: context,
      builder: (ctx) => AlertDialog(
        title: const Text('إضافة متجر جديد'),
        content: SingleChildScrollView(
          child: Column(
            mainAxisSize: MainAxisSize.min,
            children: [
              TextField(
                controller: nameController,
                decoration: const InputDecoration(labelText: 'اسم المتجر (إنجليزي)'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: nameArController,
                decoration: const InputDecoration(labelText: 'اسم المتجر (عربي)'),
              ),
              const SizedBox(height: 12),
              TextField(
                controller: storePhoneController,
                decoration: const InputDecoration(labelText: 'رقم الهاتف'),
                keyboardType: TextInputType.phone,
                textDirection: TextDirection.ltr,
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                initialValue: storeGovernorate,
                decoration: const InputDecoration(labelText: 'المحافظة'),
                items: _governorates.map((g) => DropdownMenuItem(value: g, child: Text(g))).toList(),
                onChanged: (v) => storeGovernorate = v,
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                initialValue: storeCity,
                decoration: const InputDecoration(labelText: 'المدينة'),
                items: _syrianCities.map((c) => DropdownMenuItem(value: c, child: Text(c))).toList(),
                onChanged: (v) => storeCity = v,
              ),
              const SizedBox(height: 12),
              TextField(
                controller: addressController,
                decoration: const InputDecoration(labelText: 'العنوان'),
                maxLines: 2,
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
              if (nameController.text.trim().isEmpty ||
                  nameArController.text.trim().isEmpty) {
                return;
              }
              Navigator.pop(ctx);
              await ref.read(merchantProvider.notifier).createStore({
                'name': nameController.text.trim(),
                'name_ar': nameArController.text.trim(),
                'phone': storePhoneController.text.trim(),
                'governorate': storeGovernorate,
                'city': storeCity,
                'address': addressController.text.trim(),
              });
            },
            child: const Text('حفظ'),
          ),
        ],
      ),
    );
  }
}
