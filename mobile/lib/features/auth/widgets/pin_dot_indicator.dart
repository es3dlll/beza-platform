import 'package:flutter/material.dart';
import '../../../core/theme/app_theme.dart';

class PinDotIndicator extends StatelessWidget {
  final int totalDots;
  final int filledDots;
  final bool hasError;

  const PinDotIndicator({
    super.key,
    this.totalDots = 6,
    this.filledDots = 0,
    this.hasError = false,
  });

  @override
  Widget build(BuildContext context) {
    return Directionality(
      textDirection: TextDirection.ltr,
      child: Row(
        mainAxisAlignment: MainAxisAlignment.center,
        children: List.generate(totalDots, (index) {
          final isFilled = index < filledDots;
          return AnimatedContainer(
            duration: const Duration(milliseconds: 150),
            margin: const EdgeInsets.symmetric(horizontal: 6),
            width: isFilled ? 14 : 12,
            height: isFilled ? 14 : 12,
            decoration: BoxDecoration(
              shape: BoxShape.circle,
              color: hasError
                  ? AppTheme.error
                  : isFilled
                      ? AppTheme.primary
                      : AppTheme.inputBorder,
              border: hasError
                  ? Border.all(color: AppTheme.error, width: 1.5)
                  : !isFilled
                      ? Border.all(color: AppTheme.divider, width: 2)
                      : null,
              boxShadow: isFilled
                  ? [
                      BoxShadow(
                        color: AppTheme.primary.withValues(alpha: 0.3),
                        blurRadius: 6,
                        offset: const Offset(0, 2),
                      ),
                    ]
                  : null,
            ),
          );
        }),
      ),
    );
  }
}
