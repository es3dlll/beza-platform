import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../auth/widgets/phone_number_input.dart';
import '../../auth/widgets/auth_button.dart';
import '../../auth/providers/auth_provider.dart';
import '../../auth/services/auth_service.dart';

final dioProvider = Provider((ref) => throw UnimplementedError('Dio must be provided'));
final authServiceProvider = Provider((ref) => AuthService(ref.watch(dioProvider)));
final authProvider = StateNotifierProvider<AuthProvider, AuthState>((ref) {
  return AuthProvider(ref.watch(authServiceProvider));
});

class PhoneEntryScreen extends ConsumerStatefulWidget {
  const PhoneEntryScreen({super.key});

  @override
  ConsumerState<PhoneEntryScreen> createState() => _PhoneEntryScreenState();
}

class _PhoneEntryScreenState extends ConsumerState<PhoneEntryScreen> {
  final _phoneController = TextEditingController();
  String? _errorText;

  bool get _isValidSyrianPhone {
    final raw = _phoneController.text.replaceAll(' ', '');
    final regExp = RegExp(r'^09\d{8}$');
    return regExp.hasMatch(raw);
  }

  bool get _isInternationalPhone {
    final raw = _phoneController.text.replaceAll(' ', '');
    return raw.length >= 10 && raw.length <= 15;
  }

  @override
  void dispose() {
    _phoneController.dispose();
    super.dispose();
  }

  void _submit() {
    final raw = _phoneController.text.replaceAll(' ', '');
    if (raw.isEmpty) {
      setState(() => _errorText = 'يرجى إدخال رقم الهاتف');
      return;
    }
    if (!_isValidSyrianPhone && !_isInternationalPhone) {
      setState(() => _errorText = 'رقم الهاتف غير صحيح');
      return;
    }
    setState(() => _errorText = null);

    final fullPhone = '+963$raw';
    ref.read(authProvider.notifier).register(fullPhone);
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    ref.listen<AuthState>(authProvider, (prev, next) {
      if (!next.isLoading && next.errorMessageAr != null) {
        setState(() => _errorText = next.errorMessageAr);
      }
      if (next.currentStep == AuthStep.otp) {
        Navigator.of(context).pushReplacementNamed('/otp');
      }
    });

    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF212121)),
          onPressed: () => Navigator.of(context).pop(),
        ),
      ),
      body: SafeArea(
        child: SingleChildScrollView(
          padding: const EdgeInsets.symmetric(horizontal: 28),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 20),
              const Text(
                'ما رقم هاتفك؟',
                style: TextStyle(
                  fontSize: 26,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF212121),
                  fontFamily: 'NotoNaskhArabic',
                ),
              ),
              const SizedBox(height: 10),
              Text(
                'سنرسل لك رمز تحقق عبر الرسائل النصية',
                style: TextStyle(
                  fontSize: 15,
                  fontWeight: FontWeight.w400,
                  color: Colors.grey[600],
                  fontFamily: 'NotoNaskhArabic',
                ),
              ),
              const SizedBox(height: 40),
              PhoneNumberInput(
                controller: _phoneController,
                errorText: _errorText,
                onChanged: (_) {
                  if (_errorText != null) {
                    setState(() => _errorText = null);
                  }
                },
              ),
              const SizedBox(height: 32),
              AuthButton(
                label: 'التالي',
                isLoading: authState.isLoading,
                onPressed: _submit,
              ),
              const SizedBox(height: 24),
              Center(
                child: Text(
                  'باستخدامك للتطبيق، أنت توافق على الشروط والأحكام',
                  textAlign: TextAlign.center,
                  style: TextStyle(
                    fontSize: 12,
                    color: Colors.grey[400],
                    fontFamily: 'NotoNaskhArabic',
                  ),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
