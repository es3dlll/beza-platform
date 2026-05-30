import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:flutter/services.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';
import '../widgets/pin_dot_indicator.dart';
import '../providers/auth_provider.dart';

class PinCreateScreen extends ConsumerStatefulWidget {
  const PinCreateScreen({super.key});

  @override
  ConsumerState<PinCreateScreen> createState() => _PinCreateScreenState();
}

class _PinCreateScreenState extends ConsumerState<PinCreateScreen> {
  final List<int> _pin = [];
  static const int _pinLength = 6;
  bool _isConfirming = false;
  final List<int> _firstPin = [];
  bool _hasError = false;

  void _onDigitPressed(int digit) {
    if (_pin.length >= _pinLength) return;
    HapticFeedback.selectionClick();
    if (_hasError) setState(() => _hasError = false);
    setState(() => _pin.add(digit));

    if (_pin.length == _pinLength) {
      if (!_isConfirming) {
        Future.delayed(const Duration(milliseconds: 200), () {
          if (!mounted) return;
          _firstPin.addAll(_pin);
          _pin.clear();
          setState(() => _isConfirming = true);
        });
      } else {
        if (_pin.join() == _firstPin.join()) {
          final pin = _pin.join();
          ref.read(authProvider.notifier).createPin(pin, pin);
        } else {
          HapticFeedback.heavyImpact();
          setState(() {
            _hasError = true;
            _isConfirming = false;
            _pin.clear();
            _firstPin.clear();
          });
          _showError('الرقم السري غير متطابق. حاول مرة أخرى');
        }
      }
    }
  }

  void _onDeletePressed() {
    if (_pin.isNotEmpty) {
      HapticFeedback.selectionClick();
      setState(() => _pin.removeLast());
    }
  }

  void _showError(String message) {
    if (!mounted) return;
    ScaffoldMessenger.of(context).showSnackBar(
      SnackBar(
        content: Row(
          children: [
            const Icon(Icons.error_outline, color: Colors.white, size: 18),
            const SizedBox(width: 8),
            Text(message),
          ],
        ),
        backgroundColor: AppTheme.error,
        duration: const Duration(seconds: 2),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    ref.listen<AuthState>(authProvider, (prev, next) {
      if (next.currentStep == AuthStep.biometric && prev?.currentStep != AuthStep.biometric) {
        context.pushReplacement('/biometric');
      } else if (next.isAuthenticated && next.currentStep == AuthStep.home) {
        context.go('/');
      }
    });

    return Scaffold(
      body: SafeArea(
        child: Column(
          children: [
            const SizedBox(height: 16),
            Padding(
              padding: AppTheme.screenPadding,
              child: Row(
                children: [
                  IconButton(
                    icon: const Icon(Icons.arrow_back_rounded),
                    onPressed: () => context.pop(),
                    style: IconButton.styleFrom(
                      backgroundColor: AppTheme.surfaceVariant,
                      shape: RoundedRectangleBorder(borderRadius: AppTheme.radiusMd),
                    ),
                  ),
                  const Spacer(),
                  Text(
                    'الخطوة ${_isConfirming ? "2" : "1"} من 2',
                    style: const TextStyle(
                      fontSize: 13,
                      color: AppTheme.textSecondary,
                      fontWeight: FontWeight.w500,
                    ),
                  ),
                ],
              ),
            ),
            const Spacer(flex: 1),
            Column(
              children: [
                Container(
                  padding: const EdgeInsets.all(20),
                  decoration: BoxDecoration(
                    color: AppTheme.primary.withValues(alpha: 0.08),
                    shape: BoxShape.circle,
                  ),
                  child: Icon(
                    Icons.lock_outline_rounded,
                    size: 36,
                    color: AppTheme.primary,
                  ),
                ),
                const SizedBox(height: 24),
                Text(
                  _isConfirming ? 'أعد إدخال الرقم السري' : 'إنشاء الرقم السري',
                  style: const TextStyle(
                    fontSize: 22,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.textPrimary,
                  ),
                ),
                const SizedBox(height: 8),
                Text(
                  _isConfirming
                      ? 'أعد إدخال الرقم السري للتأكيد'
                      : 'اختر 6 أرقام لحماية حسابك',
                  style: const TextStyle(
                    fontSize: 14,
                    color: AppTheme.textSecondary,
                  ),
                ),
              ],
            ),
            const SizedBox(height: 48),
            PinDotIndicator(
              totalDots: _pinLength,
              filledDots: _pin.length,
              hasError: _hasError,
            ),
            if (authState.isLoading) ...[
              const SizedBox(height: 32),
              const SizedBox(
                width: 24,
                height: 24,
                child: CircularProgressIndicator(strokeWidth: 2.5),
              ),
            ],
            const Spacer(flex: 2),
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
