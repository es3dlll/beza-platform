import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../widgets/pin_dot_indicator.dart';
import '../widgets/biometric_icon.dart';
import '../widgets/auth_button.dart';
import '../providers/auth_provider.dart';
import 'dart:math';

class PinEntryScreen extends ConsumerStatefulWidget {
  const PinEntryScreen({super.key});

  @override
  ConsumerState<PinEntryScreen> createState() => _PinEntryScreenState();
}

class _PinEntryScreenState extends ConsumerState<PinEntryScreen>
    with SingleTickerProviderStateMixin {
  String _pin = '';
  String? _error;
  bool _isShaking = false;
  late AnimationController _shakeController;
  late Animation<double> _shakeAnimation;

  final List<int> _shuffledKeys = [];

  @override
  void initState() {
    super.initState();
    _shakeController = AnimationController(
      vsync: this,
      duration: const Duration(milliseconds: 500),
    );
    _shakeAnimation = TweenSequence<double>([
      TweenSequenceItem(tween: Tween(begin: 0, end: 15), weight: 1),
      TweenSequenceItem(tween: Tween(begin: 15, end: -15), weight: 2),
      TweenSequenceItem(tween: Tween(begin: -15, end: 12), weight: 2),
      TweenSequenceItem(tween: Tween(begin: 12, end: -12), weight: 2),
      TweenSequenceItem(tween: Tween(begin: -12, end: 8), weight: 2),
      TweenSequenceItem(tween: Tween(begin: 8, end: -8), weight: 2),
      TweenSequenceItem(tween: Tween(begin: -8, end: 4), weight: 2),
      TweenSequenceItem(tween: Tween(begin: 4, end: 0), weight: 1),
    ]).animate(_shakeController);
    _shuffleKeys();
  }

  void _shuffleKeys() {
    _shuffledKeys.clear();
    for (int i = 0; i < 10; i++) _shuffledKeys.add(i);
    _shuffledKeys.shuffle(Random());
  }

  @override
  void dispose() {
    _shakeController.dispose();
    super.dispose();
  }

  void _onKeyPressed(int digit) {
    if (_pin.length < 6) {
      setState(() {
        _pin += digit.toString();
        _error = null;
      });
      if (_pin.length == 6) {
        _submitPin();
      }
    }
  }

  void _onDelete() {
    if (_pin.isNotEmpty) {
      setState(() => _pin = _pin.substring(0, _pin.length - 1));
    }
  }

  void _submitPin() {
    final authState = ref.read(authProvider);
    if (authState.phone != null) {
      ref.read(authProvider.notifier).login(authState.phone!, _pin);
    }
  }

  Future<void> _authenticateBiometric() async {
    await ref.read(authProvider.notifier).loginWithBiometric();
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

    ref.listen<AuthState>(authProvider, (prev, next) {
      if (next.isLocked) {
        setState(() {
          _error = 'تم قفل الحساب. حاول بعد 30 دقيقة';
          _isShaking = true;
        });
        _shakeController.forward(from: 0);
        Future.delayed(const Duration(milliseconds: 600), () {
          if (mounted) setState(() => _isShaking = false);
        });
      } else if (!next.isLoading && next.errorMessageAr != null) {
        setState(() {
          _error = next.pinAttemptsRemaining > 0
              ? 'الرمز غير صحيح. المحاولات المتبقية: ${next.pinAttemptsRemaining}'
              : next.errorMessageAr;
          _isShaking = true;
          _pin = '';
        });
        _shakeController.forward(from: 0);
        Future.delayed(const Duration(milliseconds: 600), () {
          if (mounted) setState(() => _isShaking = false);
        });
      }
      if (next.currentStep == AuthStep.home) {
        Navigator.of(context).pushReplacementNamed('/home');
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
        child: Column(
          children: [
            Expanded(
              child: Column(
                mainAxisAlignment: MainAxisAlignment.center,
                children: [
                  Container(
                    width: 80,
                    height: 80,
                    decoration: BoxDecoration(
                      shape: BoxShape.circle,
                      color: const Color(0xFF2E7D32).withValues(alpha: 0.1),
                    ),
                    child: const Icon(
                      Icons.lock_outline,
                      size: 40,
                      color: Color(0xFF2E7D32),
                    ),
                  ),
                  const SizedBox(height: 20),
                  const Text(
                    'أدخل رمز PIN',
                    style: TextStyle(
                      fontSize: 22,
                      fontWeight: FontWeight.w700,
                      color: Color(0xFF212121),
                      fontFamily: 'NotoNaskhArabic',
                    ),
                  ),
                  const SizedBox(height: 32),
                  AnimatedBuilder(
                    animation: _shakeAnimation,
                    builder: (context, child) {
                      return Transform.translate(
                        offset:
                            Offset(_isShaking ? _shakeAnimation.value : 0, 0),
                        child: child,
                      );
                    },
                    child: PinDotIndicator(filledDots: _pin.length),
                  ),
                  if (_error != null) ...[
                    const SizedBox(height: 16),
                    Text(
                      _error!,
                      textAlign: TextAlign.center,
                      style: const TextStyle(
                        color: Colors.red,
                        fontSize: 14,
                        fontFamily: 'NotoNaskhArabic',
                      ),
                    ),
                  ],
                  if (!authState.isLocked && authState.biometricEnabled) ...[
                    const SizedBox(height: 32),
                    GestureDetector(
                      onTap: _authenticateBiometric,
                      child: BiometricIcon(size: 56),
                    ),
                    const SizedBox(height: 8),
                    const Text(
                      'بصمة الإصبع',
                      style: TextStyle(
                        fontSize: 13,
                        color: Color(0xFF2E7D32),
                        fontFamily: 'NotoNaskhArabic',
                      ),
                    ),
                  ],
                ],
              ),
            ),
            _buildKeypad(),
            const SizedBox(height: 16),
          ],
        ),
      ),
    );
  }

  Widget _buildKeypad() {
    return Container(
      padding: const EdgeInsets.symmetric(horizontal: 32),
      child: Column(
        children: [
          for (int row = 0; row < 3; row++)
            Padding(
              padding: const EdgeInsets.symmetric(vertical: 6),
              child: Row(
                mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                children: List.generate(3, (col) {
                  final digit = _shuffledKeys[row * 3 + col];
                  return _buildKeyButton(digit);
                }),
              ),
            ),
          Padding(
            padding: const EdgeInsets.symmetric(vertical: 6),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.spaceEvenly,
              children: [
                const SizedBox(width: 72),
                _buildKeyButton(_shuffledKeys[9]),
                _buildDeleteButton(),
              ],
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildKeyButton(int digit) {
    return GestureDetector(
      onTap: () => _onKeyPressed(digit),
      child: Container(
        width: 72,
        height: 72,
        decoration: BoxDecoration(
          shape: BoxShape.circle,
          color: const Color(0xFFF5F5F5),
        ),
        child: Center(
          child: Text(
            digit.toString(),
            style: const TextStyle(
              fontSize: 28,
              fontWeight: FontWeight.w600,
              color: Color(0xFF212121),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildDeleteButton() {
    return GestureDetector(
      onTap: _onDelete,
      child: Container(
        width: 72,
        height: 72,
        decoration: const BoxDecoration(
          shape: BoxShape.circle,
          color: Color(0xFFF5F5F5),
        ),
        child: const Center(
          child: Icon(Icons.backspace_outlined,
              size: 24, color: Color(0xFF757575)),
        ),
      ),
    );
  }
}
