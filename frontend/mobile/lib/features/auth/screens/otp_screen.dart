import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';
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

  void _onDigitEntered(int index, String value) {
    if (value.isNotEmpty && index < 5) {
      _focusNodes[index + 1].requestFocus();
    }
  }

  void _verifyCode() {
    final code = _controllers.map((c) => c.text).join();
    if (code.length == 6) {
      final phone = ref.read(authProvider).phone;
      if (phone.isNotEmpty) {
        ref.read(authProvider.notifier).verifyOtp(phone, code);
      }
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    ref.listen<AuthState>(authProvider, (prev, next) {
      if (next.currentStep == AuthStep.pinCreate && prev?.currentStep != AuthStep.pinCreate) {
        context.pushReplacement('/pin/create');
      }
    });

    return Scaffold(
      body: SafeArea(
        child: Padding(
          padding: AppTheme.screenPadding,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const SizedBox(height: 16),
              IconButton(
                icon: const Icon(Icons.arrow_back_rounded),
                onPressed: () => context.pop(),
                style: IconButton.styleFrom(
                  backgroundColor: AppTheme.surfaceVariant,
                  shape: RoundedRectangleBorder(borderRadius: AppTheme.radiusMd),
                ),
              ),
              const SizedBox(height: 40),
              const Text(
                'رمز التحقق',
                style: TextStyle(
                  fontSize: 28,
                  fontWeight: FontWeight.bold,
                  color: AppTheme.textPrimary,
                ),
              ),
              const SizedBox(height: 12),
              Text(
                'تم إرسال رمز مكون من 6 أرقام إلى',
                style: const TextStyle(
                  fontSize: 15,
                  color: AppTheme.textSecondary,
                ),
              ),
              const SizedBox(height: 4),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                decoration: BoxDecoration(
                  color: AppTheme.primary.withValues(alpha: 0.08),
                  borderRadius: AppTheme.radiusSm,
                ),
                child: Text(
                  '+${authState.phone}',
                  style: const TextStyle(
                    fontSize: 16,
                    fontWeight: FontWeight.w600,
                    color: AppTheme.primary,
                    letterSpacing: 1,
                  ),
                  textDirection: TextDirection.ltr,
                ),
              ),
              const SizedBox(height: 40),
              Center(
                child: Directionality(
                  textDirection: TextDirection.ltr,
                  child: Row(
                    mainAxisAlignment: MainAxisAlignment.center,
                    children: List.generate(6, (index) {
                      return Padding(
                        padding: EdgeInsets.only(
                          left: index < 5 ? 10 : 0,
                          right: 0,
                        ),
                        child: OtpDigitInput(
                          controller: _controllers[index],
                          focusNode: _focusNodes[index],
                          onChanged: (value) => _onDigitEntered(index, value),
                        ),
                      );
                    }),
                  ),
                ),
              ),
              if (authState.error != null) ...[
                const SizedBox(height: 20),
                Center(
                  child: Container(
                    padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 10),
                    decoration: BoxDecoration(
                      color: AppTheme.errorLight,
                      borderRadius: AppTheme.radiusMd,
                    ),
                    child: Row(
                      mainAxisSize: MainAxisSize.min,
                      children: [
                        const Icon(Icons.error_outline, size: 16, color: AppTheme.error),
                        const SizedBox(width: 8),
                        Text(
                          authState.error!,
                          style: const TextStyle(
                            fontSize: 13,
                            color: AppTheme.error,
                            fontWeight: FontWeight.w500,
                          ),
                        ),
                      ],
                    ),
                  ),
                ),
              ],
              const SizedBox(height: 24),
              Center(child: OtpTimer(
                initialSeconds: 60,
                onResend: () => ref.read(authProvider.notifier).requestOtp(authState.phone),
              )),
              const Spacer(),
              AuthButton(
                label: 'تحقق',
                isLoading: authState.isLoading,
                onPressed: _verifyCode,
              ),
              const SizedBox(height: 48),
            ],
          ),
        ),
      ),
    );
  }
}
