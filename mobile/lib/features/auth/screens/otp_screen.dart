import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../onboarding/screens/phone_entry_screen.dart';
import '../widgets/otp_digit_input.dart';
import '../widgets/otp_timer.dart';
import '../widgets/auth_button.dart';
import '../providers/auth_provider.dart';

class OtpScreen extends ConsumerStatefulWidget {
  const OtpScreen({super.key});

  @override
  ConsumerState<OtpScreen> createState() => _OtpScreenState();
}

class _OtpScreenState extends ConsumerState<OtpScreen> {
  final List<TextEditingController> _controllers = [];
  final List<FocusNode> _focusNodes = [];
  final _formKey = GlobalKey<FormState>();
  late OtpTimer _otpTimer;
  bool _canResend = false;

  @override
  void initState() {
    super.initState();
    for (int i = 0; i < 6; i++) {
      _controllers.add(TextEditingController());
      _focusNodes.add(FocusNode());
    }
  }

  @override
  void dispose() {
    for (final c in _controllers) {
      c.dispose();
    }
    for (final f in _focusNodes) {
      f.dispose();
    }
    super.dispose();
  }

  void _onDigitChanged(int index, String value) {
    if (value.isNotEmpty && index < 5) {
      _focusNodes[index + 1].requestFocus();
    }
    if (index == 5 && value.isNotEmpty) {
      _focusNodes[index].unfocus();
      _verifyOtp();
    }
  }

  void _onBackspace(int index) {
    if (_controllers[index].text.isEmpty && index > 0) {
      _focusNodes[index - 1].requestFocus();
    }
  }

  String get _otpCode {
    return _controllers.map((c) => c.text).join();
  }

  void _verifyOtp() {
    final code = _otpCode;
    if (code.length != 6) return;
    final authState = ref.read(authProvider);
    if (authState.phone != null) {
      ref.read(authProvider.notifier).verifyOtp(authState.phone!, code);
    }
  }

  void _resendOtp() {
    if (!_canResend) return;
    final authState = ref.read(authProvider);
    if (authState.phone != null) {
      ref.read(authProvider.notifier).sendOtp(authState.phone!);
      _otpTimer.reset();
      setState(() => _canResend = false);
    }
  }

  String _formatPhone(String phone) {
    if (phone.length == 13 && phone.startsWith('+963')) {
      final prefix = phone.substring(0, 4);
      final rest = phone.substring(4);
      if (rest.length == 9) {
        return '$prefix ${rest.substring(0, 2)} ${rest.substring(2, 5)} ${rest.substring(5, 7)} ${rest.substring(7)}';
      }
    }
    return phone;
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    ref.listen<AuthState>(authProvider, (prev, next) {
      if (next.currentStep == AuthStep.pinCreate) {
        Navigator.of(context).pushReplacementNamed('/pin-create');
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
                'أدخل رمز التحقق',
                style: TextStyle(
                  fontSize: 26,
                  fontWeight: FontWeight.w800,
                  color: Color(0xFF212121),
                  fontFamily: 'NotoNaskhArabic',
                ),
              ),
              const SizedBox(height: 10),
              Text(
                authState.phone != null
                    ? 'تم إرسال الرمز إلى ${_formatPhone(authState.phone!)}'
                    : '',
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w400,
                  color: Colors.grey[600],
                  fontFamily: 'NotoNaskhArabic',
                ),
              ),
              const SizedBox(height: 40),
              Form(
                key: _formKey,
                child: Directionality(
                  textDirection: TextDirection.ltr,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: List.generate(6, (index) {
                      return OtpDigitInput(
                        controller: _controllers[index],
                        focusNode: _focusNodes[index],
                        autoFocus: index == 0,
                        onChanged: (value) {
                          if (value.isNotEmpty) {
                            _onDigitChanged(index, value);
                          }
                        },
                      );
                    }),
                  ),
                ),
              ),
              const SizedBox(height: 32),
              if (authState.errorMessageAr != null)
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(12),
                  margin: const EdgeInsets.only(bottom: 16),
                  decoration: BoxDecoration(
                    color: Colors.red[50],
                    borderRadius: BorderRadius.circular(12),
                    border: Border.all(color: Colors.red[200]!),
                  ),
                  child: Text(
                    authState.errorMessageAr!,
                    textAlign: TextAlign.center,
                    style: const TextStyle(
                      color: Colors.red,
                      fontSize: 14,
                      fontFamily: 'NotoNaskhArabic',
                    ),
                  ),
                ),
              Center(
                child: OtpTimer(
                  key: ValueKey(authState.otpRemainingSeconds),
                  initialSeconds: authState.otpRemainingSeconds > 0
                      ? authState.otpRemainingSeconds
                      : 300,
                  onTimerEnd: () {
                    if (mounted) setState(() => _canResend = true);
                  },
                ),
              ),
              const SizedBox(height: 12),
              Center(
                child: TextButton(
                  onPressed: _canResend && !authState.isLoading
                      ? _resendOtp
                      : null,
                  child: Text(
                    'إعادة إرسال الرمز',
                    style: TextStyle(
                      color: _canResend
                          ? const Color(0xFF2E7D32)
                          : Colors.grey[400],
                      fontSize: 15,
                      fontWeight: FontWeight.w600,
                      fontFamily: 'NotoNaskhArabic',
                    ),
                  ),
                ),
              ),
              const SizedBox(height: 24),
              AuthButton(
                label: 'تأكيد',
                isLoading: authState.isLoading,
                onPressed: _otpCode.length == 6 ? _verifyOtp : null,
                disabled: _otpCode.length != 6,
              ),
            ],
          ),
        ),
      ),
    );
  }
}
