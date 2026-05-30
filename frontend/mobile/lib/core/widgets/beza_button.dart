import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

enum BezaButtonSize { sm, md, lg }

class BezaButton extends StatelessWidget {
  final String label;
  final VoidCallback? onPressed;
  final BezaButtonSize size;
  final bool isLoading;
  final bool expanded;
  final Color? backgroundColor;
  final Color? textColor;

  const BezaButton({
    super.key,
    required this.label,
    this.onPressed,
    this.size = BezaButtonSize.md,
    this.isLoading = false,
    this.expanded = true,
    this.backgroundColor,
    this.textColor,
  });

  @override
  Widget build(BuildContext context) {
    final (double height, EdgeInsets padding, double fontSize) = switch (size) {
      BezaButtonSize.sm => (36.0, const EdgeInsets.symmetric(horizontal: 16), 14),
      BezaButtonSize.md => (48.0, const EdgeInsets.symmetric(horizontal: 24), 16),
      BezaButtonSize.lg => (56.0, const EdgeInsets.symmetric(horizontal: 32), 18),
    };

    final btn = ElevatedButton(
      onPressed: isLoading ? null : onPressed,
      style: ElevatedButton.styleFrom(
        backgroundColor: backgroundColor ?? AppTheme.primary,
        foregroundColor: textColor ?? AppTheme.textOnPrimary,
        disabledBackgroundColor: AppTheme.primary.withValues(alpha: 0.5),
        disabledForegroundColor: AppTheme.textOnPrimary.withValues(alpha: 0.7),
        minimumSize: expanded ? Size.fromHeight(height) : Size(120, height),
        padding: padding,
        shape: RoundedRectangleBorder(borderRadius: AppTheme.radiusMd),
        elevation: 0,
        textStyle: TextStyle(
          fontSize: fontSize,
          fontWeight: FontWeight.w600,
          fontFamily: 'Cairo',
        ),
      ),
      child: isLoading
          ? SizedBox(
              height: 20,
              width: 20,
              child: CircularProgressIndicator(
                strokeWidth: 2,
                color: textColor ?? AppTheme.textOnPrimary,
              ),
            )
          : Text(label),
    );

    if (expanded) return btn;
    return btn;
  }
}
