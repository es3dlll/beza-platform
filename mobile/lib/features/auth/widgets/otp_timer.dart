import 'dart:async';
import 'package:flutter/material.dart';
import '../../../core/theme/app_theme.dart';

class OtpTimer extends StatefulWidget {
  final int initialSeconds;
  final VoidCallback onResend;
  final bool isResending;

  const OtpTimer({
    super.key,
    this.initialSeconds = 60,
    required this.onResend,
    this.isResending = false,
  });

  @override
  State<OtpTimer> createState() => _OtpTimerState();
}

class _OtpTimerState extends State<OtpTimer> {
  late int _secondsRemaining;
  Timer? _timer;
  bool _canResend = false;

  @override
  void initState() {
    super.initState();
    _secondsRemaining = widget.initialSeconds;
    _startTimer();
  }

  void _startTimer() {
    _canResend = false;
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_secondsRemaining > 0) {
        setState(() => _secondsRemaining--);
      } else {
        setState(() => _canResend = true);
        timer.cancel();
      }
    });
  }

  void _resend() {
    if (!_canResend) return;
    widget.onResend();
    setState(() {
      _secondsRemaining = widget.initialSeconds;
      _canResend = false;
    });
    _startTimer();
  }

  @override
  void dispose() {
    _timer?.cancel();
    super.dispose();
  }

  String get _timeText {
    final min = (_secondsRemaining ~/ 60).toString().padLeft(2, '0');
    final sec = (_secondsRemaining % 60).toString().padLeft(2, '0');
    return '$min:$sec';
  }

  @override
  Widget build(BuildContext context) {
    return GestureDetector(
      onTap: _canResend ? _resend : null,
      child: Container(
        padding: const EdgeInsets.symmetric(horizontal: 20, vertical: 10),
        decoration: BoxDecoration(
          color: _canResend
              ? AppTheme.primary.withValues(alpha: 0.08)
              : Colors.transparent,
          borderRadius: AppTheme.radiusFull,
        ),
        child: Row(
          mainAxisAlignment: MainAxisAlignment.center,
          mainAxisSize: MainAxisSize.min,
          children: [
            if (_canResend)
              const Icon(Icons.refresh, size: 16, color: AppTheme.primary),
            if (_canResend) const SizedBox(width: 6),
            Text(
              _canResend ? 'إعادة إرسال الرمز' : 'إعادة الإرسال بعد $_timeText',
              style: TextStyle(
                fontSize: 13,
                fontWeight: _canResend ? FontWeight.w600 : FontWeight.w400,
                color: _canResend ? AppTheme.primary : AppTheme.textSecondary,
              ),
            ),
            if (!_canResend) ...[
              const SizedBox(width: 6),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                decoration: BoxDecoration(
                  color: AppTheme.primary.withValues(alpha: 0.1),
                  borderRadius: AppTheme.radiusSm,
                ),
                child: Text(
                  _timeText,
                  style: const TextStyle(
                    fontSize: 13,
                    fontWeight: FontWeight.w600,
                    color: AppTheme.primary,
                    fontFamily: 'monospace',
                  ),
                ),
              ),
            ],
          ],
        ),
      ),
    );
  }
}
