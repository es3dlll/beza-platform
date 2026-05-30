import 'dart:ui' as ui show TextDirection;
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/api/api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/providers/auth_provider.dart';
import '../services/profile_service.dart';

String _arabicDigits(String s) {
  const a = '٠١٢٣٤٥٦٧٨٩';
  return s.split('').map((c) {
    final i = '0123456789'.indexOf(c);
    return i >= 0 ? a[i] : c;
  }).join();
}

class ProfileScreen extends ConsumerStatefulWidget {
  const ProfileScreen({super.key});

  @override
  ConsumerState<ProfileScreen> createState() => _ProfileScreenState();
}

class _ProfileScreenState extends ConsumerState<ProfileScreen> {
  final _service = ProfileService(ApiClient());

  UserProfile? _profile;
  bool _isLoading = true;
  String? _error;
  bool _isEditing = false;

  // Edit controllers
  final _nameCtrl = TextEditingController();
  final _nameArCtrl = TextEditingController();
  final _nationalIdCtrl = TextEditingController();
  final _dobCtrl = TextEditingController();
  final _addressCtrl = TextEditingController();
  final _cityCtrl = TextEditingController();
  final _provinceCtrl = TextEditingController();
  String _gender = '';

  // PIN change
  final _currentPinCtrl = TextEditingController();
  final _newPinCtrl = TextEditingController();
  final _confirmPinCtrl = TextEditingController();

  @override
  void initState() {
    super.initState();
    _fetchProfile();
  }

  @override
  void dispose() {
    _nameCtrl.dispose();
    _nameArCtrl.dispose();
    _nationalIdCtrl.dispose();
    _dobCtrl.dispose();
    _addressCtrl.dispose();
    _cityCtrl.dispose();
    _provinceCtrl.dispose();
    _currentPinCtrl.dispose();
    _newPinCtrl.dispose();
    _confirmPinCtrl.dispose();
    super.dispose();
  }

  Future<void> _fetchProfile() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final profile = await _service.getProfile();
      setState(() {
        _profile = profile;
        _isLoading = false;
        _populateEditFields();
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  void _populateEditFields() {
    final p = _profile?.profile;
    _nameCtrl.text = p?.fullName ?? '';
    _nameArCtrl.text = p?.fullNameAr ?? '';
    _nationalIdCtrl.text = p?.nationalId ?? '';
    _dobCtrl.text = p?.dateOfBirth ?? '';
    _addressCtrl.text = p?.address ?? '';
    _cityCtrl.text = p?.city ?? '';
    _provinceCtrl.text = p?.province ?? '';
    _gender = p?.gender ?? '';
  }

  Future<void> _saveProfile() async {
    try {
      final updated = await _service.updateProfile({
        'full_name': _nameCtrl.text,
        'full_name_ar': _nameArCtrl.text,
        'national_id': _nationalIdCtrl.text,
        'date_of_birth': _dobCtrl.text,
        'gender': _gender,
        'address': _addressCtrl.text,
        'city': _cityCtrl.text,
        'province': _provinceCtrl.text,
      });
      setState(() {
        _profile = updated;
        _isEditing = false;
      });
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('تم حفظ البيانات بنجاح')),
        );
      }
    } catch (e) {
      if (mounted) {
        ScaffoldMessenger.of(context).showSnackBar(
          const SnackBar(content: Text('فشل حفظ البيانات')),
        );
      }
    }
  }

  void _showChangePinDialog() {
    _currentPinCtrl.clear();
    _newPinCtrl.clear();
    _confirmPinCtrl.clear();
    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: ui.TextDirection.rtl,
        child: AlertDialog(
        title: const Text('تغيير الرقم السري', style: TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold)),
        content: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            TextField(
              controller: _currentPinCtrl,
              decoration: const InputDecoration(labelText: 'الرقم السري الحالي'),
              obscureText: true,
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _newPinCtrl,
              decoration: const InputDecoration(labelText: 'الرقم السري الجديد'),
              obscureText: true,
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 12),
            TextField(
              controller: _confirmPinCtrl,
              decoration: const InputDecoration(labelText: 'تأكيد الرقم السري الجديد'),
              obscureText: true,
              keyboardType: TextInputType.number,
            ),
          ],
        ),
        actions: [
          TextButton(
            onPressed: () => Navigator.pop(ctx),
            child: const Text('إلغاء'),
          ),
          FilledButton(
            onPressed: () async {
              if (_newPinCtrl.text != _confirmPinCtrl.text) {
                ScaffoldMessenger.of(context).showSnackBar(
                  const SnackBar(content: Text('الرقم السري الجديد غير متطابق')),
                );
                return;
              }
              try {
                await _service.changePin(
                  currentPin: _currentPinCtrl.text,
                  newPin: _newPinCtrl.text,
                  newPinConfirmation: _confirmPinCtrl.text,
                );
                if (ctx.mounted) Navigator.pop(ctx);
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('تم تغيير الرقم السري بنجاح')),
                  );
                }
              } catch (e) {
                if (mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('فشل تغيير الرقم السري')),
                  );
                }
              }
            },
            child: const Text('حفظ'),
          ),
        ],
      ),
      ),
    );
  }

  void _confirmLogout() {
    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: ui.TextDirection.rtl,
        child: AlertDialog(
          title: const Text('تسجيل الخروج', style: TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold)),
          content: const Text('هل أنت متأكد من تسجيل الخروج؟', style: TextStyle(fontFamily: 'Cairo')),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('إلغاء'),
            ),
            ElevatedButton(
              onPressed: () {
                Navigator.pop(ctx);
                ref.read(authProvider.notifier).logout();
              },
              style: ElevatedButton.styleFrom(backgroundColor: AppTheme.error),
              child: const Text('تسجيل الخروج', style: TextStyle(fontFamily: 'Cairo')),
            ),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('الملف الشخصي'),
        actions: [
          if (_profile != null && !_isLoading)
            IconButton(
              icon: Icon(_isEditing ? Icons.close : Icons.edit),
              onPressed: () {
                setState(() {
                  _isEditing = !_isEditing;
                  if (!_isEditing) _populateEditFields();
                });
              },
            ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) return _buildShimmer();
    if (_error != null) return _buildError();
    if (_profile == null) return _buildError();
    return RefreshIndicator(
      onRefresh: _fetchProfile,
      child: _buildContent(),
    );
  }

  Widget _buildShimmer() {
    return Shimmer.fromColors(
      baseColor: AppTheme.shimmer,
      highlightColor: Colors.grey[100]!,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            const CircleAvatar(radius: 48, child: Icon(Icons.person)),
            const SizedBox(height: 16),
            Container(
              height: 18,
              width: 120,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(4),
              ),
            ),
            const SizedBox(height: 32),
            ...List.generate(5, (_) => Padding(
              padding: const EdgeInsets.only(bottom: 12),
              child: Container(
                height: 50,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
            )),
          ],
        ),
      ),
    );
  }

  Widget _buildError() {
    return Center(
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
            Text(
              _error ?? 'حدث خطأ أثناء تحميل الملف الشخصي',
              style: const TextStyle(fontFamily: 'Cairo', fontSize: 16, color: AppTheme.textSecondary),
              textAlign: TextAlign.center,
            ),
            const SizedBox(height: 24),
            ElevatedButton.icon(
              onPressed: _fetchProfile,
              icon: const Icon(Icons.refresh, size: 18),
              label: const Text('إعادة المحاولة'),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildContent() {
    final auth = ref.watch(authProvider);
    final user = _profile!;
    final p = user.profile;
    final initial = (p?.fullNameAr ?? p?.fullName ?? user.phone).isNotEmpty
        ? (p?.fullNameAr ?? p?.fullName ?? user.phone)[0]
        : '?';

    return ListView(
      padding: const EdgeInsets.all(16),
      children: [
        // Avatar
        Center(
          child: Container(
            width: 96,
            height: 96,
            decoration: BoxDecoration(
              gradient: AppTheme.primaryGradient,
              shape: BoxShape.circle,
              boxShadow: [
                BoxShadow(
                  color: AppTheme.primary.withValues(alpha: 0.3),
                  blurRadius: 16,
                  offset: const Offset(0, 6),
                ),
              ],
            ),
            child: Center(
              child: Text(
                initial,
                style: const TextStyle(
                  fontFamily: 'Cairo',
                  fontSize: 36,
                  fontWeight: FontWeight.bold,
                  color: Colors.white,
                ),
              ),
            ),
          ),
        ),
        const SizedBox(height: 16),
        // Name
        Text(
          p?.fullNameAr ?? p?.fullName ?? user.phone,
          textAlign: TextAlign.center,
          style: const TextStyle(fontFamily: 'Cairo', fontSize: 18, fontWeight: FontWeight.w600, color: AppTheme.textPrimary),
        ),
        const SizedBox(height: 24),

        // Info section
        Container(
          padding: const EdgeInsets.all(16),
          decoration: AppTheme.cardDecoration,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
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
                  const Text('معلومات الحساب', style: TextStyle(fontFamily: 'Cairo', fontSize: 16, fontWeight: FontWeight.bold, color: AppTheme.textPrimary)),
                ],
              ),
              const SizedBox(height: 16),
              _infoRow(
                label: 'رقم الهاتف',
                value: auth.phone.isNotEmpty ? auth.phone : user.phone,
                trailing: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                  decoration: BoxDecoration(
                    color: AppTheme.success.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(8),
                  ),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      const Icon(Icons.check_circle, size: 12, color: AppTheme.success),
                      const SizedBox(width: 4),
                      const Text('موثق', style: TextStyle(fontFamily: 'Cairo', fontSize: 11, color: AppTheme.success)),
                    ],
                  ),
                ),
              ),
              const Divider(height: 20, color: AppTheme.divider),
              _infoRow(label: 'البريد الإلكتروني', value: user.email ?? '---'),
              const Divider(height: 20, color: AppTheme.divider),
              _infoRow(label: 'الحالة', value: user.status == 'active' ? 'نشط' : user.status),
              const Divider(height: 20, color: AppTheme.divider),
              _infoRow(
                label: 'عضو منذ',
                value: _arabicDigits(DateFormat('yyyy/MM/dd').format(user.createdAt)),
              ),
            ],
          ),
        ),
        const SizedBox(height: 16),

        // Profile fields
        Container(
          padding: const EdgeInsets.all(16),
          decoration: AppTheme.cardDecoration,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
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
                  const Text('البيانات الشخصية', style: TextStyle(fontFamily: 'Cairo', fontSize: 16, fontWeight: FontWeight.bold, color: AppTheme.textPrimary)),
                ],
              ),
              const SizedBox(height: 16),
              if (_isEditing) ...[
                _buildEditField('الاسم (إنجليزي)', _nameCtrl),
                _buildEditField('الاسم (عربي)', _nameArCtrl),
                _buildEditField('الرقم الوطني', _nationalIdCtrl),
                _buildEditField('تاريخ الميلاد', _dobCtrl),
                _buildGenderDropdown(),
                _buildEditField('المدينة', _cityCtrl),
                _buildEditField('المحافظة', _provinceCtrl),
                _buildEditField('العنوان', _addressCtrl),
                const SizedBox(height: 16),
                SizedBox(
                  width: double.infinity,
                  child: ElevatedButton(
                    onPressed: _saveProfile,
                    child: const Text('حفظ'),
                  ),
                ),
              ] else ...[
                _buildField('الاسم (إنجليزي)', p?.fullName ?? '---'),
                _buildField('الاسم (عربي)', p?.fullNameAr ?? '---'),
                _buildField('الرقم الوطني', p?.nationalId ?? '---'),
                _buildField('تاريخ الميلاد', p?.dateOfBirth ?? '---'),
                _buildField('الجنس', p?.gender == 'male' ? 'ذكر' : p?.gender == 'female' ? 'أنثى' : '---'),
                _buildField('المدينة', p?.city ?? '---'),
                _buildField('المحافظة', p?.province ?? '---'),
                _buildField('العنوان', p?.address ?? '---'),
                ],
              ],
            ),
          ),
        const SizedBox(height: 16),

        // Settings
        Container(
          decoration: AppTheme.cardDecoration,
          child: Column(
            children: [
              Padding(
                padding: const EdgeInsets.fromLTRB(16, 16, 16, 8),
                child: Row(
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
                    const Text('الإعدادات', style: TextStyle(fontFamily: 'Cairo', fontSize: 16, fontWeight: FontWeight.bold, color: AppTheme.textPrimary)),
                  ],
                ),
              ),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppTheme.primary.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.lock_outline, color: AppTheme.primary, size: 20),
                ),
                title: const Text('تغيير الرقم السري', style: TextStyle(fontFamily: 'Cairo', fontSize: 15, color: AppTheme.textPrimary)),
                trailing: const Icon(Icons.chevron_left, color: AppTheme.textTertiary),
                onTap: _showChangePinDialog,
              ),
              const Divider(height: 1, indent: 72, endIndent: 16, color: AppTheme.divider),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppTheme.primary.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.phone_outlined, color: AppTheme.primary, size: 20),
                ),
                title: const Text('توثيق رقم الجوال', style: TextStyle(fontFamily: 'Cairo', fontSize: 15, color: AppTheme.textPrimary)),
                trailing: ref.watch(authProvider).isPhoneVerified
                    ? Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppTheme.success.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.check_circle, size: 12, color: AppTheme.success),
                            SizedBox(width: 4),
                            Text('موثق', style: TextStyle(fontFamily: 'Cairo', fontSize: 11, color: AppTheme.success)),
                          ],
                        ),
                      )
                    : Container(
                        padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                        decoration: BoxDecoration(
                          color: AppTheme.warning.withValues(alpha: 0.1),
                          borderRadius: BorderRadius.circular(8),
                        ),
                        child: const Row(
                          mainAxisSize: MainAxisSize.min,
                          children: [
                            Icon(Icons.warning_amber_rounded, size: 12, color: AppTheme.warning),
                            SizedBox(width: 4),
                            Text('غير موثق', style: TextStyle(fontFamily: 'Cairo', fontSize: 11, color: AppTheme.warning)),
                          ],
                        ),
                      ),
                onTap: () => context.push('/settings/verify-phone'),
              ),
              const Divider(height: 1, indent: 72, endIndent: 16, color: AppTheme.divider),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppTheme.primary.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.language, color: AppTheme.primary, size: 20),
                ),
                title: const Text('اللغة', style: TextStyle(fontFamily: 'Cairo', fontSize: 15, color: AppTheme.textPrimary)),
                trailing: const Text('العربية', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
                onTap: () {},
              ),
              const Divider(height: 1, indent: 72, endIndent: 16, color: AppTheme.divider),
              ListTile(
                leading: Container(
                  padding: const EdgeInsets.all(10),
                  decoration: BoxDecoration(
                    color: AppTheme.primary.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: const Icon(Icons.info_outline, color: AppTheme.primary, size: 20),
                ),
                title: const Text('حول التطبيق', style: TextStyle(fontFamily: 'Cairo', fontSize: 15, color: AppTheme.textPrimary)),
                trailing: const Text('1.0.0', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
                onTap: () {},
              ),
            ],
          ),
        ),
        const SizedBox(height: 24),

        // Logout
        SizedBox(
          width: double.infinity,
          child: ElevatedButton.icon(
            onPressed: _confirmLogout,
            icon: const Icon(Icons.logout, color: AppTheme.error),
            label: const Text('تسجيل الخروج', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.error)),
            style: ElevatedButton.styleFrom(
              backgroundColor: AppTheme.error.withValues(alpha: 0.1),
              elevation: 0,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildField(String label, String value) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 8),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          SizedBox(
            width: 100,
            child: Text(
              label,
              style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 13),
            ),
          ),
          Expanded(
            child: Text(value, style: const TextStyle(fontFamily: 'Cairo', fontSize: 13, color: AppTheme.textPrimary)),
          ),
        ],
      ),
    );
  }

  Widget _buildEditField(String label, TextEditingController ctrl) {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: TextField(
        controller: ctrl,
        decoration: InputDecoration(
          labelText: label,
          isDense: true,
        ),
      ),
    );
  }

  Widget _buildGenderDropdown() {
    return Padding(
      padding: const EdgeInsets.only(bottom: 12),
      child: DropdownButtonFormField<String>(
        initialValue: _gender.isEmpty ? null : _gender,
        decoration: const InputDecoration(
          labelText: 'الجنس',
          isDense: true,
        ),
        items: const [
          DropdownMenuItem(value: 'male', child: Text('ذكر')),
          DropdownMenuItem(value: 'female', child: Text('أنثى')),
        ],
        onChanged: (v) => setState(() => _gender = v ?? ''),
      ),
    );
  }

  Widget _infoRow({
    required String label,
    required String value,
    Widget? trailing,
  }) {
    return Row(
      children: [
        SizedBox(
          width: 100,
          child: Text(
            label,
            style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 13),
          ),
        ),
        Expanded(
          child: Text(value, style: const TextStyle(fontFamily: 'Cairo', fontSize: 13, color: AppTheme.textPrimary)),
        ),
        if (trailing != null) trailing,
      ],
    );
  }
}
