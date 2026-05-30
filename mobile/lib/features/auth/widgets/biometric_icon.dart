import 'package:flutter/material.dart';

class BiometricIcon extends StatelessWidget {
  final double size;
  final Color color;
  final BiometricType type;

  const BiometricIcon({
    super.key,
    this.size = 48,
    this.color = const Color(0xFF2E7D32),
    this.type = BiometricType.fingerprint,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size,
      height: size,
      decoration: BoxDecoration(
        shape: BoxShape.circle,
        color: color.withValues(alpha: 0.1),
        border: Border.all(color: color.withValues(alpha: 0.3), width: 2),
      ),
      child: Icon(
        type == BiometricType.fingerprint
            ? Icons.fingerprint
            : Icons.face,
        color: color,
        size: size * 0.55,
      ),
    );
  }
}

enum BiometricType { fingerprint, face }
