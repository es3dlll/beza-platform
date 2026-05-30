import 'package:flutter/material.dart';
import '../../../core/theme/app_theme.dart';

class BiometricIcon extends StatelessWidget {
  final double size;
  final Color color;

  const BiometricIcon({
    super.key,
    this.size = 64,
    this.color = AppTheme.primary,
  });

  @override
  Widget build(BuildContext context) {
    return Container(
      width: size * 1.5,
      height: size * 1.5,
      decoration: BoxDecoration(
        gradient: LinearGradient(
          colors: [
            color.withValues(alpha: 0.1),
            color.withValues(alpha: 0.05),
          ],
          begin: Alignment.topLeft,
          end: Alignment.bottomRight,
        ),
        shape: BoxShape.circle,
        border: Border.all(
          color: color.withValues(alpha: 0.15),
          width: 2,
        ),
      ),
      child: Icon(
        Icons.fingerprint,
        size: size,
        color: color,
      ),
    );
  }
}
