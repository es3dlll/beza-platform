import 'dart:async';
import 'package:flutter/material.dart';

class OtpTimer extends StatefulWidget {
  final int initialSeconds;
  final VoidCallback? onTimerEnd;
  final TextStyle? textStyle;

  const OtpTimer({
    super.key,
    this.initialSeconds = 300,
    this.onTimerEnd,
    this.textStyle,
  });

  @override
  State<OtpTimer> createState() => _OtpTimerState();
}

class _OtpTimerState extends State<OtpTimer> {
  late int _secondsRemaining;
  late Timer _timer;
  bool _isRunning = true;

  @override
  void initState() {
    super.initState();
    _secondsRemaining = widget.initialSeconds;
    _startTimer();
  }

  void _startTimer() {
    _timer = Timer.periodic(const Duration(seconds: 1), (timer) {
      if (_secondsRemaining > 0) {
        setState(() => _secondsRemaining--);
      } else {
        _isRunning = false;
        _timer.cancel();
        widget.onTimerEnd?.call();
      }
    });
  }

  void reset({int? seconds}) {
    _timer.cancel();
    setState(() {
      _secondsRemaining = seconds ?? widget.initialSeconds;
      _isRunning = true;
    });
    _startTimer();
  }

  @override
  void dispose() {
    _timer.cancel();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final minutes = (_secondsRemaining ~/ 60).toString().padLeft(2, '0');
    final seconds = (_secondsRemaining % 60).toString().padLeft(2, '0');

    final color = _secondsRemaining <= 30
        ? Colors.red
        : const Color(0xFF2E7D32);

    return Text(
      '$minutes:$seconds',
      style: widget.textStyle ??
          TextStyle(
            fontSize: 18,
            fontWeight: FontWeight.w600,
            color: color,
            fontFamily: 'NotoNaskhArabic',
          ),
    );
  }
}
