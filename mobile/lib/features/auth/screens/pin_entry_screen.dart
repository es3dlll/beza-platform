import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';
import '../widgets/pin_dot_indicator.dart';
import '../widgets/biometric_icon.dart';
import '../providers/auth_provider.dart';

class PinEntryScreen extends ConsumerStatefulWidget {
  const PinEntryScreen({super.key});

  @override
  ConsumerState<PinEntryScreen> createState() => _PinEntryScreenState();
}

class _PinEntryScreenState extends ConsumerState<PinEntryScreen> {
  final List<int> _pin = [];
  static const int _pinLength = 6;
  bool _hasError = false;

  @override
  void initState() {
    super.initState();
    _tryBiometric();
  }

  Future<void> _tryBiometric() async {}

  void _onDigitPressed(int digit) {
    if (_pin.length >= _pinLength) return;
    HapticFeedback.selectionClick();
    if (_hasError) setState(() => _hasError = false);
    setState(() => _pin.add(digit));
    if (_pin.length == _pinLength) {
      _verifyPin();
    }
  }

  void _onDeletePressed() {
    if (_pin.isNotEmpty) {
      HapticFeedback.selectionClick();
      setState(() => _pin.removeLast());
    }
  }

  void _verifyPin() {
    final pinStr = _pin.join();
    final phone = ref.read(authProvider).phone;
    if (phone.isNotEmpty) {
      ref.read(authProvider.notifier).loginWithPin(phone: phone, pin: pinStr);
    }
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    ref.listen<AuthState>(authProvider, (prev, next) {
      if (next.isAuthenticated && next.currentStep == AuthStep.home) {
        context.go('/');
      }
    });

    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            const Spacer(flex: 1),
            const BiometricIcon(size: 56),
            const SizedBox(height: 24),
            const Text(
              'مرحباً بعودتك',
              style: TextStyle(
                fontSize: 24,
                fontWeight: FontWeight.bold,
                color: AppTheme.textPrimary,
              ),
            ),
            const SizedBox(height: 8),
            const Text(
              'أدخل الرقم السري للدخول',
              style: TextStyle(
                fontSize: 14,
                color: AppTheme.textSecondary,
              ),
            ),
            const SizedBox(height: 40),
            PinDotIndicator(
              totalDots: _pinLength,
              filledDots: _pin.length,
              hasError: _hasError,
            ),
            if (authState.error != null) ...[
              const SizedBox(height: 16),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 8),
                decoration: BoxDecoration(
                  color: AppTheme.errorLight,
                  borderRadius: AppTheme.radiusMd,
                ),
                child: Text(
                  authState.error!,
                  style: const TextStyle(
                    fontSize: 13,
                    color: AppTheme.error,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ),
            ],
            const Spacer(flex: 1),
            Padding(
              padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 16),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  TextButton.icon(
                    icon: const Icon(Icons.fingerprint, size: 20),
                    label: const Text('بصمة الإصبع'),
                    onPressed: _tryBiometric,
                  ),
                ],
              ),
            ),
            _Numpad(
              onDigitPressed: _onDigitPressed,
              onDeletePressed: _onDeletePressed,
            ),
          ],
        ),
      ),
    );
  }
}

class _Numpad extends StatelessWidget {
  final ValueChanged<int> onDigitPressed;
  final VoidCallback onDeletePressed;

  const _Numpad({required this.onDigitPressed, required this.onDeletePressed});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.symmetric(horizontal: 40, vertical: 16),
      child: Column(
        children: [
          for (int row = 0; row < 3; row++)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 6),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: List.generate(3, (col) {
                  final digit = row * 3 + col + 1;
                  return _NumpadButton(
                    label: '$digit',
                    onPressed: () => onDigitPressed(digit),
                  );
                }),
              ),
            ),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 6),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                const SizedBox(width: 76),
                _NumpadButton(
                  label: '0',
                  onPressed: () => onDigitPressed(0),
                ),
                _NumpadButton(
                  label: '⌫',
                  isDelete: true,
                  onPressed: onDeletePressed,
                ),
              ],
            ),
          ),
        ],
      ),
    );
  }
}

class _NumpadButton extends StatefulWidget {
  final String label;
  final VoidCallback onPressed;
  final bool isDelete;

  const _NumpadButton({
    required this.label,
    required this.onPressed,
    this.isDelete = false,
  });

  @override
  State<_NumpadButton> createState() => _NumpadButtonState();
}

class _NumpadButtonState extends State<_NumpadButton> {
  bool _isPressed = false;

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTapDown: (_) => setState(() => _isPressed = true),
      onTapUp: (_) {
        setState(() => _isPressed = false);
        widget.onPressed();
      },
      onTapCancel: () => setState(() => _isPressed = false),
      child: AnimatedContainer(
        duration: const Duration(milliseconds: 100),
        width: 76,
        height: 76,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: _isPressed
              ? AppTheme.primary.withValues(alpha: 0.15)
              : AppTheme.surfaceVariant,
          boxShadow: _isPressed
              ? []
              : [
                  BoxShadow(
                    color: Colors.black.withValues(alpha: 0.04),
                    blurRadius: 8,
                    offset: const Offset(0, 2),
                  ),
                ],
        ),
        child: Center(
          child: Text(
            widget.label,
            style: TextStyle(
              fontSize: widget.isDelete ? 24 : 28,
              fontWeight: widget.isDelete ? FontWeight.w300 : FontWeight.w500,
              color: widget.isDelete
                  ? AppTheme.textSecondary
                  : AppTheme.textPrimary,
            ),
          ),
        ),
      ),
    );
  }
}
