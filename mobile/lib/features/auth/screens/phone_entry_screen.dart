import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';
import '../widgets/auth_button.dart';
import '../widgets/phone_number_input.dart';
import '../providers/auth_provider.dart';

class PhoneEntryScreen extends ConsumerStatefulWidget {
  const PhoneEntryScreen({super.key});

  @override
  ConsumerState<PhoneEntryScreen> createState() => _PhoneEntryScreenState();
}

class _PhoneEntryScreenState extends ConsumerState<PhoneEntryScreen>
    with SingleTickerProviderStateMixin {
  final _controller = TextEditingController();
  String? _error;
  bool _hasInteracted = false;

  late AnimationController _animController;
  late Animation<double> _fadeAnim;
  late Animation<double> _slideAnim;
  late Animation<double> _stepAnim;

  @override
  void initState() {
    super.initState();
    _animController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 800),
    );
    _fadeAnim = Tween<double>(begin: 0.0, end: 1.0).animate(
      CurvedAnimation(parent: _animController, curve: const Interval(0.0, 0.6, curve: Curves.easeOut)),
    );
    _slideAnim = Tween<double>(begin: 30, end: 0).animate(
      CurvedAnimation(parent: _animController, curve: const Interval(0.1, 0.6, curve: Curves.easeOutCubic)),
    );
    _stepAnim = Tween<double>(begin: 0.5, end: 1.0).animate(
      CurvedAnimation(parent: _animController, curve: const Interval(0.0, 0.4, curve: Curves.easeOut)),
    );
    _animController.forward();
  }

  @override
  void dispose() {
    _controller.dispose();
    _animController.dispose();
    super.dispose();
  }

  bool _isValidPhone(String phone) {
    if (phone.length < 9 || phone.length > 9) return false;
    if (!RegExp(r'^[0-9]{9}$').hasMatch(phone)) return false;
    return true;
  }

  void _submit() {
    final phone = _controller.text.trim();
    setState(() => _hasInteracted = true);

    if (!_isValidPhone(phone)) {
      setState(() => _error = 'يرجى إدخال 9 أرقام صحيحة (مثال: 933XXXXXX)');
      return;
    }

    setState(() => _error = null);
    ref.read(authProvider.notifier).register('963$phone');
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    ref.listen<AuthState>(authProvider, (prev, next) {
      if (next.currentStep == AuthStep.otp && prev?.currentStep != AuthStep.otp) {
        context.pushReplacement('/otp');
      }
    });

    final displayError = _error ?? authState.error;

    return Scaffold(
      body: SafeArea(
        child: GestureDetector(
          onTap: () => FocusScope.of(context).unfocus(),
          child: Padding(
            padding: AppTheme.screenPadding,
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                const SizedBox(height: 12),
                _buildAppBar(),
                const SizedBox(height: 32),
                _buildStepIndicator(),
                const SizedBox(height: 36),
                Expanded(
                  child: SingleChildScrollView(
                    physics: const ClampingScrollPhysics(),
                    child: AnimatedBuilder(
                      animation: _animController,
                      builder: (context, _) => Opacity(
                        opacity: _fadeAnim.value,
                        child: Transform.translate(
                          offset: Offset(0, _slideAnim.value),
                          child: Column(
                            crossAxisAlignment: CrossAxisAlignment.start,
                            children: [
                              _buildHeader(),
                              const SizedBox(height: 36),
                              PhoneNumberInput(
                                controller: _controller,
                                errorText: displayError,
                                  onChanged: (_) {
                                    if (_hasInteracted) setState(() => _error = null);
                                  },
                              ),
                              const SizedBox(height: 16),
                              _buildTerms(),
                              const SizedBox(height: 32),
                              AuthButton(
                                label: 'إرسال رمز التحقق',
                                isLoading: authState.isLoading,
                                onPressed: _submit,
                              ),
                              const SizedBox(height: 24),
                              _buildHelpText(),
                              const SizedBox(height: 32),
                            ],
                          ),
                        ),
                      ),
                    ),
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
    return Row(
      children: [
        Container(
          decoration: BoxDecoration(
            color: AppTheme.surfaceVariant,
            borderRadius: AppTheme.radiusMd,
          ),
          child: IconButton(
            icon: const Icon(Icons.arrow_back_rounded),
            onPressed: () => context.pop(),
            style: IconButton.styleFrom(
              backgroundColor: Colors.transparent,
              shape: RoundedRectangleBorder(borderRadius: AppTheme.radiusMd),
            ),
          ),
        ),
        const Spacer(),
        Container(
          padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
          decoration: BoxDecoration(
            color: AppTheme.primary.withValues(alpha: 0.08),
            borderRadius: AppTheme.radiusFull,
          ),
          child: const Row(
            mainAxisSize: MainAxisSize.min,
            children: [
              Icon(Icons.headset_mic, size: 14, color: AppTheme.primary),
              SizedBox(width: 6),
              Text(
                'دعم فني',
                style: TextStyle(
                  fontFamily: 'Cairo',
                  fontSize: 12,
                  color: AppTheme.primary,
                  fontWeight: FontWeight.w500,
                ),
              ),
            ],
          ),
        ),
      ],
    );
  }

  Widget _buildStepIndicator() {
    return AnimatedBuilder(
      animation: _animController,
      builder: (context, _) => Opacity(
        opacity: _stepAnim.value,
        child: Row(
          children: [
            _StepDot(label: 'رقم الهاتف', isActive: true, isDone: false),
            _StepLine(isActive: false),
            _StepDot(label: 'رمز التحقق', isActive: false, isDone: false),
            _StepLine(isActive: false),
            _StepDot(label: 'الرقم السري', isActive: false, isDone: false),
          ],
        ),
      ),
    );
  }

  Widget _buildHeader() {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            gradient: AppTheme.primaryGradient,
            borderRadius: BorderRadius.circular(18),
            boxShadow: [
              BoxShadow(
                color: AppTheme.primary.withValues(alpha: 0.25),
                blurRadius: 16,
                offset: const Offset(0, 6),
              ),
            ],
          ),
          child: const Icon(
            Icons.phone_android,
            size: 32,
            color: Colors.white,
          ),
        ),
        const SizedBox(height: 20),
        const Text(
          'ما رقم هاتفك؟',
          style: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 26,
            fontWeight: FontWeight.bold,
            color: AppTheme.textPrimary,
            height: 1.2,
          ),
        ),
        const SizedBox(height: 10),
        const Text(
          'سنرسل لك رمز تحقق عبر الرسائل النصية للتحقق من هويتك',
          style: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 14,
            color: AppTheme.textSecondary,
            height: 1.6,
          ),
        ),
      ],
    );
  }

  Widget _buildTerms() {
    return Row(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Icon(Icons.security, size: 14, color: AppTheme.textTertiary),
        const SizedBox(width: 8),
        Expanded(
          child: Text(
            'بإنشاء حسابك، أنت توافق على شروط الاستخدام وسياسة الخصوصية الخاصة بنا',
            style: TextStyle(
              fontFamily: 'Cairo',
              fontSize: 11,
              color: AppTheme.textTertiary,
              height: 1.5,
            ),
          ),
        ),
      ],
    );
  }

  Widget _buildHelpText() {
    return Center(
      child: TextButton.icon(
        onPressed: () => context.push('/pin/entry'),
        icon: const Icon(Icons.lock_outline, size: 16),
        label: const Text(
          'لديك حساب بالفعل؟ سجل الدخول',
          style: TextStyle(
            fontFamily: 'Cairo',
            fontSize: 13,
            fontWeight: FontWeight.w500,
          ),
        ),
      ),
    );
  }
}

class _StepDot extends StatelessWidget {
  final String label;
  final bool isActive;
  final bool isDone;

  const _StepDot({
    required this.label,
    required this.isActive,
    required this.isDone,
  });

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          AnimatedContainer(
            duration: const Duration(milliseconds: 300),
            width: isActive ? 32 : 28,
            height: 3,
            decoration: BoxDecoration(
              gradient: isActive ? AppTheme.primaryGradient : null,
              color: isActive ? null : AppTheme.divider,
              borderRadius: BorderRadius.circular(2),
            ),
          ),
          const SizedBox(height: 6),
          Text(
            label,
            style: TextStyle(
              fontFamily: 'Cairo',
              fontSize: 10,
              color: isActive ? AppTheme.primary : AppTheme.textTertiary,
              fontWeight: isActive ? FontWeight.w600 : FontWeight.w400,
            ),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }
}

class _StepLine extends StatelessWidget {
  final bool isActive;

  const _StepLine({required this.isActive});

  @override
  Widget build(BuildContext context) {
    return Container(
      width: 24,
      height: 2,
      margin: const EdgeInsets.only(bottom: 18),
      decoration: BoxDecoration(
        color: isActive ? AppTheme.primary : AppTheme.divider,
        borderRadius: BorderRadius.circular(1),
      ),
    );
  }
}