import 'dart:math';
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../widgets/pin_dot_indicator.dart';
import '../widgets/auth_button.dart';
import '../providers/auth_provider.dart';

enum PinCreateStep { create, confirm, mismatch, success }

class PinCreateScreen extends ConsumerStatefulWidget {
  const PinCreateScreen({super.key});

  @override
  ConsumerState<PinCreateScreen> createState() => _PinCreateScreenState();
}

class _PinCreateScreenState extends ConsumerState<PinCreateScreen>
    with SingleTickerProviderStateMixin {
  PinCreateStep _step = PinCreateStep.create;
  String _firstPin = '';
  String _confirmPin = '';
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

  bool _isPinValid(String pin) {
    if (pin.length != 6) return false;
    if (pin == '000000') return false;
    if (RegExp(r'^(\d)\1{5}$').hasMatch(pin)) return false;
    bool sequential = true;
    for (int i = 1; i < 6; i++) {
      if (int.parse(pin[i]) != int.parse(pin[i - 1]) + 1) {
        sequential = false;
        break;
      }
    }
    if (sequential) return false;
    sequential = true;
    for (int i = 1; i < 6; i++) {
      if (int.parse(pin[i]) != int.parse(pin[i - 1]) - 1) {
        sequential = false;
        break;
      }
    }
    return !sequential;
  }

  void _onKeyPressed(int digit) {
    if (_step == PinCreateStep.success) return;

    setState(() => _error = null);

    if (_step == PinCreateStep.create) {
      if (_firstPin.length < 6) {
        _firstPin += digit.toString();
        if (_firstPin.length == 6) {
          if (!_isPinValid(_firstPin)) {
            setState(() {
              _error = 'الرقم ضعيف. تجنب الأرقام المتكررة أو المتسلسلة';
              _isShaking = true;
            });
            _shakeController.forward(from: 0);
            Future.delayed(const Duration(milliseconds: 600), () {
              if (mounted) setState(() => _isShaking = false);
            });
            _firstPin = '';
            return;
          }
          setState(() => _step = PinCreateStep.confirm);
        }
      }
    } else if (_step == PinCreateStep.confirm) {
      if (_confirmPin.length < 6) {
        _confirmPin += digit.toString();
        if (_confirmPin.length == 6) {
          if (_firstPin != _confirmPin) {
            setState(() {
              _error = 'الرمز غير متطابق';
              _step = PinCreateStep.mismatch;
              _isShaking = true;
            });
            _shakeController.forward(from: 0);
            Future.delayed(const Duration(milliseconds: 600), () {
              if (mounted) setState(() => _isShaking = false);
            });
            Future.delayed(const Duration(milliseconds: 1200), () {
              if (mounted) {
                setState(() {
                  _step = PinCreateStep.create;
                  _firstPin = '';
                  _confirmPin = '';
                });
              }
            });
          } else {
            _submitPin();
          }
        }
      }
    }
  }

  void _onDelete() {
    if (_step == PinCreateStep.create && _firstPin.isNotEmpty) {
      setState(() => _firstPin = _firstPin.substring(0, _firstPin.length - 1));
    } else if (_step == PinCreateStep.confirm && _confirmPin.isNotEmpty) {
      setState(
          () => _confirmPin = _confirmPin.substring(0, _confirmPin.length - 1));
    }
  }

  void _submitPin() {
    ref.read(authProvider.notifier).createPin(_firstPin);

    setState(() => _step = PinCreateStep.success);

    Future.delayed(const Duration(milliseconds: 1500), () {
      if (mounted) {
        ref.read(authProvider.notifier).setBiometricEnabled(false);
        Navigator.of(context).pushReplacementNamed('/biometric');
      }
    });
  }

  @override
  Widget build(BuildContext context) {
    final authState = ref.watch(authProvider);

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
                  if (_step == PinCreateStep.success)
                    _buildSuccessView()
                  else ...[
                    Icon(
                      _step == PinCreateStep.create
                          ? Icons.lock_outline
                          : Icons.lock,
                      size: 40,
                      color: const Color(0xFF2E7D32),
                    ),
                    const SizedBox(height: 16),
                    Text(
                      _step == PinCreateStep.create
                          ? 'أنشئ رمز PIN'
                          : 'أعد إدخال PIN للتأكيد',
                      style: const TextStyle(
                        fontSize: 22,
                        fontWeight: FontWeight.w700,
                        color: Color(0xFF212121),
                        fontFamily: 'NotoNaskhArabic',
                      ),
                    ),
                    const SizedBox(height: 8),
                    Text(
                      'أدخل 6 أرقام',
                      style: TextStyle(
                        fontSize: 14,
                        color: Colors.grey[500],
                        fontFamily: 'NotoNaskhArabic',
                      ),
                    ),
                    const SizedBox(height: 32),
                    AnimatedBuilder(
                      animation: _shakeAnimation,
                      builder: (context, child) {
                        return Transform.translate(
                          offset: Offset(_isShaking ? _shakeAnimation.value : 0, 0),
                          child: child,
                        );
                      },
                      child: PinDotIndicator(
                        filledDots: _step == PinCreateStep.create
                            ? _firstPin.length
                            : _confirmPin.length,
                      ),
                    ),
                    if (_error != null) ...[
                      const SizedBox(height: 16),
                      Text(
                        _error!,
                        style: const TextStyle(
                          color: Colors.red,
                          fontSize: 14,
                          fontFamily: 'NotoNaskhArabic',
                        ),
                      ),
                    ],
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

  Widget _buildSuccessView() {
    return Column(
      mainAxisAlignment: MainAxisAlignment.center,
      children: [
        Container(
          width: 100,
          height: 100,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: const Color(0xFF2E7D32).withValues(alpha: 0.1),
          ),
          child: const Icon(
            Icons.check_circle,
            size: 60,
            color: Color(0xFF2E7D32),
          ),
        ),
        const SizedBox(height: 24),
        const Text(
          'تم إنشاء الرمز بنجاح!',
          style: TextStyle(
            fontSize: 22,
            fontWeight: FontWeight.w700,
            color: Color(0xFF212121),
            fontFamily: 'NotoNaskhArabic',
          ),
        ),
        const SizedBox(height: 8),
        Text(
          'يجري الآن نقلك إلى الخطوة التالية...',
          style: TextStyle(
            fontSize: 14,
            color: Colors.grey[500],
            fontFamily: 'NotoNaskhArabic',
          ),
        ),
      ],
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


