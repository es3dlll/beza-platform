import 'dart:async';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';
import '../widgets/auth_button.dart';
import '../widgets/otp_digit_input.dart';
import '../widgets/biometric_icon.dart';
import '../providers/auth_provider.dart';

class SecuritySetupScreen extends ConsumerStatefulWidget {
  const SecuritySetupScreen({super.key});

  @override
  ConsumerState<SecuritySetupScreen> createState() => _SecuritySetupScreenState();
}

class _SecuritySetupScreenState extends ConsumerState<SecuritySetupScreen> {
  final PageController _pageCtrl = PageController();
  final Set<int> _completedSteps = {};
  final List<TextEditingController> _otpControllers = List.generate(6, (_) => TextEditingController());
  final List<FocusNode> _otpFocusNodes = List.generate(6, (_) => FocusNode());
  bool _otpSent = false;
  bool _isSendingOtp = false;
  int _otpTimerSeconds = 60;
  Timer? _otpTimer;
  bool _canResendOtp = false;
  bool _phoneVerified = false;

  @override
  void initState() {
    super.initState();
  }

  @override
  void dispose() {
    _pageCtrl.dispose();
    _otpTimer?.cancel();
    for (final c in _otpControllers) { c.dispose(); }
    for (final f in _otpFocusNodes) { f.dispose(); }
    super.dispose();
  }

  void _startOtpTimer() {
    _canResendOtp = false;
    _otpTimerSeconds = 60;
    _otpTimer?.cancel();
    _otpTimer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_otpTimerSeconds > 0) {
        setState(() => _otpTimerSeconds--);
      } else {
        setState(() => _canResendOtp = true);
        timer.cancel();
      }
    });
  }

  String get _otpTimeText {
    final min = (_otpTimerSeconds ~/ 60).toString().padLeft(2, '0');
    final sec = (_otpTimerSeconds % 60).toString().padLeft(2, '0');
    return '$min:$sec';
  }

  Future<void> _sendOtp() async {
    setState(() => _isSendingOtp = true);
    try {
      await ref.read(authProvider.notifier).sendPhoneVerificationOtp();
      setState(() {
        _otpSent = true;
        _isSendingOtp = false;
      });
      _startOtpTimer();
      _otpFocusNodes[0].requestFocus();
    } catch (e) {
      setState(() => _isSendingOtp = false);
    }
  }

  Future<void> _verifyOtp() async {
    final code = _otpControllers.map((c) => c.text).join();
    if (code.length != 6) return;
    await ref.read(authProvider.notifier).verifyPhoneOtp(code);
    if (mounted && ref.read(authProvider).isPhoneVerified) {
      setState(() {
        _phoneVerified = true;
        _completedSteps.add(0);
      });
    }
  }

  void _onOtpChanged(String value, int index) {
    if (value.isNotEmpty && index < 5) {
      _otpFocusNodes[index + 1].requestFocus();
    }
    if (_otpControllers.every((c) => c.text.isNotEmpty)) {
      _verifyOtp();
    }
  }

  void _goToPage(int page) {
    _pageCtrl.animateToPage(page, duration: const Duration(milliseconds: 300), curve: Curves.easeInOut);
  }

  void _complete() {
    ref.read(authProvider.notifier).completeSecuritySetup();
    context.go('/');
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    ref.listen<AuthState>(authProvider, (prev, next) {
      if (next.currentStep == AuthStep.home && next.isSecuritySetupComplete) {
        context.go('/');
      }
    });

    return Directionality(
      textDirection: TextDirection.rtl,
      child: Scaffold(
        body: Container(
          decoration: const BoxDecoration(gradient: AppTheme.surfaceGradient),
          child: SafeArea(
            child: Column(
              children: [
                _buildAppBar(),
                _buildStepIndicator(),
                Expanded(
                  child: PageView(
                    controller: _pageCtrl,
                    physics: const NeverScrollableScrollPhysics(),
                    children: [
                      _buildPhonePage(authState),
                      _buildBiometricPage(),
                    ],
                  ),
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildAppBar() {
    return Container(
      decoration: BoxDecoration(
        gradient: AppTheme.primaryGradient,
        boxShadow: [
          BoxShadow(
            color: AppTheme.primary.withValues(alpha: 0.3),
            blurRadius: 16,
            offset: const Offset(0, 4),
          ),
        ],
      ),
      child: Padding(
        padding: const EdgeInsets.fromLTRB(4, 8, 4, 20),
        child: Row(
          children: [
            IconButton(
              icon: const Icon(Icons.arrow_back_rounded, color: Colors.white),
              onPressed: () => context.pop(),
            ),
            const Spacer(),
            const Text(
              'إعدادات الأمان',
              style: TextStyle(
                fontFamily: 'Cairo',
                fontSize: 18,
                fontWeight: FontWeight.w600,
                color: Colors.white,
              ),
            ),
            const Spacer(),
            const SizedBox(width: 48),
          ],
        ),
      ),
    );
  }

  Widget _buildStepIndicator() {
    final steps = ['توثيق الجوال', 'البصمة'];
    final icons = [Icons.phone_android_outlined, Icons.fingerprint];
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 24, vertical: 20),
      child: Row(
        children: List.generate(steps.length, (i) {
          final isCompleted = _completedSteps.contains(i);
          final isActive = _pageCtrl.hasClients && _pageCtrl.page?.round() == i;
          return Expanded(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Row(
                  children: [
                    if (i > 0)
                      Expanded(
                        child: Container(
                          height: 2,
                          color: _completedSteps.contains(i - 1) ? AppTheme.primary : AppTheme.divider,
                        ),
                      ),
                    Container(
                      width: 36,
                      height: 36,
                      decoration: BoxDecoration(
                        shape: BoxShape.circle,
                        color: isCompleted || isActive ? AppTheme.primary : AppTheme.surfaceVariant,
                        border: !isCompleted && !isActive ? Border.all(color: AppTheme.divider) : null,
                        boxShadow: isActive
                            ? [
                                BoxShadow(
                                  color: AppTheme.primary.withValues(alpha: 0.3),
                                  blurRadius: 8,
                                  offset: const Offset(0, 2),
                                ),
                              ]
                            : null,
                      ),
                      child: Icon(
                        isCompleted ? Icons.check : icons[i],
                        size: 16,
                        color: isCompleted || isActive ? Colors.white : AppTheme.textSecondary,
                      ),
                    ),
                    if (i < steps.length - 1)
                      Expanded(
                        child: Container(
                          height: 2,
                          color: isCompleted ? AppTheme.primary : AppTheme.divider,
                        ),
                      ),
                  ],
                ),
                const SizedBox(height: 8),
                Text(
                  steps[i],
                  style: TextStyle(
                    fontFamily: 'Cairo',
                    fontSize: 11,
                    fontWeight: isActive || isCompleted ? FontWeight.w600 : FontWeight.w400,
                    color: isActive || isCompleted ? AppTheme.primary : AppTheme.textSecondary,
                  ),
                ),
              ],
            ),
          );
        }),
      ),
    );
  }

  Widget _buildSectionHeader(String text) {
    return Row(
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
        Text(
          text,
          style: const TextStyle(
            fontFamily: 'Cairo',
            fontSize: 16,
            fontWeight: FontWeight.bold,
            color: AppTheme.textPrimary,
          ),
        ),
      ],
    );
  }

  Widget _buildInfoBox(String text) {
    return Container(
      padding: const EdgeInsets.all(12),
      decoration: BoxDecoration(
        color: AppTheme.infoLight.withValues(alpha: 0.3),
        borderRadius: AppTheme.radiusMd,
        border: Border.all(color: AppTheme.info.withValues(alpha: 0.15)),
      ),
      child: Row(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Icon(Icons.info_outline, size: 16, color: AppTheme.info),
          const SizedBox(width: 8),
          Expanded(
            child: Text(
              text,
              style: const TextStyle(
                fontFamily: 'Cairo',
                fontSize: 12,
                color: AppTheme.textSecondary,
                height: 1.4,
              ),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildPhonePage(AuthState authState) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          const SizedBox(height: 24),
          Container(
            padding: AppTheme.cardPadding,
            decoration: AppTheme.cardDecoration,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildSectionHeader('توثيق رقم الجوال'),
                const SizedBox(height: 16),
                Row(
                  children: [
                    Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: AppTheme.primary.withValues(alpha: 0.1),
                        borderRadius: AppTheme.radiusMd,
                      ),
                      child: const Icon(Icons.phone_outlined, color: AppTheme.primary, size: 20),
                    ),
                    const SizedBox(width: 14),
                    Text(
                      '+963 ${authState.phone}',
                      style: const TextStyle(
                        fontFamily: 'Cairo',
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: AppTheme.textPrimary,
                      ),
                      textDirection: TextDirection.ltr,
                    ),
                    const Spacer(),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 10, vertical: 4),
                      decoration: BoxDecoration(
                        color: _phoneVerified ? AppTheme.successLight : AppTheme.warningLight,
                        borderRadius: AppTheme.radiusSm,
                      ),
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          Icon(
                            _phoneVerified ? Icons.check_circle : Icons.warning_amber_rounded,
                            size: 14,
                            color: _phoneVerified ? AppTheme.success : AppTheme.warning,
                          ),
                          const SizedBox(width: 4),
                          Text(
                            _phoneVerified ? 'موثق' : 'غير موثق',
                            style: TextStyle(
                              fontFamily: 'Cairo',
                              fontSize: 12,
                              fontWeight: FontWeight.w600,
                              color: _phoneVerified ? AppTheme.success : AppTheme.warning,
                            ),
                          ),
                        ],
                      ),
                    ),
                  ],
                ),
                if (!_otpSent && !_phoneVerified) ...[
                  const SizedBox(height: 20),
                  SizedBox(
                    width: double.infinity,
                    child: ElevatedButton.icon(
                      onPressed: _isSendingOtp ? null : _sendOtp,
                      icon: _isSendingOtp
                          ? const SizedBox(
                              width: 18, height: 18,
                              child: CircularProgressIndicator(strokeWidth: 2),
                            )
                          : const Icon(Icons.send_outlined),
                      label: const Text('إرسال رمز التحقق'),
                    ),
                  ),
                ],
                if (_otpSent && !_phoneVerified) ...[
                  const SizedBox(height: 20),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceBetween,
                    children: List.generate(6, (i) {
                      return SizedBox(
                        width: 44,
                        child: OtpDigitInput(
                          controller: _otpControllers[i],
                          focusNode: _otpFocusNodes[i],
                          onChanged: (v) => _onOtpChanged(v, i),
                        ),
                      );
                    }),
                  ),
                  const SizedBox(height: 16),
                  Center(
                    child: GestureDetector(
                      onTap: _canResendOtp ? _sendOtp : null,
                      child: Row(
                        mainAxisSize: MainAxisSize.min,
                        children: [
                          if (_canResendOtp)
                            const Icon(Icons.refresh, size: 16, color: AppTheme.primary),
                          if (_canResendOtp) const SizedBox(width: 6),
                          Text(
                            _canResendOtp ? 'إعادة إرسال الرمز' : 'إعادة الإرسال بعد $_otpTimeText',
                            style: TextStyle(
                              fontFamily: 'Cairo',
                              fontSize: 13,
                              fontWeight: _canResendOtp ? FontWeight.w600 : FontWeight.w400,
                              color: _canResendOtp ? AppTheme.primary : AppTheme.textSecondary,
                            ),
                          ),
                          if (!_canResendOtp) ...[
                            const SizedBox(width: 6),
                            Container(
                              padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                              decoration: BoxDecoration(
                                color: AppTheme.primary.withValues(alpha: 0.1),
                                borderRadius: AppTheme.radiusSm,
                              ),
                              child: Text(
                                _otpTimeText,
                                style: const TextStyle(
                                  fontFamily: 'Cairo',
                                  fontSize: 13,
                                  fontWeight: FontWeight.w600,
                                  color: AppTheme.primary,
                                ),
                              ),
                            ),
                          ],
                        ],
                      ),
                    ),
                  ),
                ],
              ],
            ),
          ),
          const SizedBox(height: 16),
          _buildInfoBox('توثيق رقم الجوال يضمن أن رقمك مسجل باسمك ويمنع الاحتيال'),
          const Spacer(),
          Row(
            children: [
              TextButton(
                onPressed: () => _goToPage(0),
                child: const Text(
                  'رجوع',
                  style: TextStyle(
                    fontFamily: 'Cairo',
                    color: AppTheme.textSecondary,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
              const Spacer(),
              TextButton(
                onPressed: () {
                  _completedSteps.add(0);
                  _goToPage(1);
                },
                child: const Text(
                  'تخطي',
                  style: TextStyle(
                    fontFamily: 'Cairo',
                    color: AppTheme.textSecondary,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
              const SizedBox(width: 8),
              Expanded(
                child: AuthButton(
                  label: 'التالي',
                  onPressed: _phoneVerified ? () => _goToPage(1) : null,
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }

  Widget _buildBiometricPage() {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 20),
      child: Column(
        children: [
          const SizedBox(height: 24),
          Container(
            padding: AppTheme.cardPadding,
            decoration: AppTheme.cardDecoration,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                _buildSectionHeader('تفعيل البصمة'),
                const SizedBox(height: 24),
                const Center(child: BiometricIcon(size: 72)),
                const SizedBox(height: 20),
                const Text(
                  'فعّل بصمة الإصبع أو بصمة الوجه لتسجيل الدخول السريع',
                  style: TextStyle(
                    fontFamily: 'Cairo',
                    fontSize: 15,
                    color: AppTheme.textSecondary,
                    height: 1.5,
                  ),
                  textAlign: TextAlign.center,
                ),
                const SizedBox(height: 20),
                AuthButton(
                  label: 'تفعيل البصمة',
                  icon: Icons.fingerprint,
                  onPressed: () {
                    ref.read(authProvider.notifier).enableBiometric();
                    setState(() => _completedSteps.add(1));
                  },
                ),
                const SizedBox(height: 12),
                SizedBox(
                  width: double.infinity,
                  child: OutlinedButton(
                    onPressed: () {
                      _completedSteps.add(1);
                      _complete();
                    },
                    child: const Text('تخطي'),
                  ),
                ),
                const SizedBox(height: 12),
                Center(
                  child: Text(
                    'يمكنك تفعيل هذا لاحقاً من الإعدادات',
                    style: const TextStyle(
                      fontFamily: 'Cairo',
                      fontSize: 12,
                      color: AppTheme.textTertiary,
                    ),
                  ),
                ),
              ],
            ),
          ),
          const SizedBox(height: 16),
          _buildInfoBox('بيانات بصمتك تبقى مشفرة على جهازك فقط'),
          const Spacer(),
          Row(
            children: [
              TextButton(
                onPressed: () => _goToPage(0),
                child: const Text(
                  'رجوع',
                  style: TextStyle(
                    fontFamily: 'Cairo',
                    color: AppTheme.textSecondary,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
              const Spacer(),
              Expanded(
                child: AuthButton(
                  label: 'بدء استخدام بزة',
                  onPressed: () {
                    _completedSteps.add(2);
                    _complete();
                  },
                ),
              ),
            ],
          ),
          const SizedBox(height: 16),
        ],
      ),
    );
  }
}
