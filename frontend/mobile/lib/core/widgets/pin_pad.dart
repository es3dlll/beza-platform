import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

class PinPad extends StatelessWidget {
  final int pinLength;
  final String pin;
  final ValueChanged<String> onDigitPressed;
  final VoidCallback onDelete;
  final VoidCallback? onSubmit;

  const PinPad({
    super.key,
    this.pinLength = 6,
    required this.pin,
    required this.onDigitPressed,
    required this.onDelete,
    this.onSubmit,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      mainAxisSize: MainAxisSize.min,
      children: [
        // Dots
        Row(
          mainAxisAlignment: MainAxisAlignment.center,
          children: List.generate(pinLength, (i) {
            final filled = i < pin.length;
            return Container(
              margin: const EdgeInsets.symmetric(horizontal: 8),
              width: 14,
              height: 14,
              decoration: BoxDecoration(
                shape: BoxShape.circle,
                color: filled ? AppTheme.primary : AppTheme.surfaceContainerHigh,
                border: filled ? null : Border.all(color: AppTheme.inputBorder),
              ),
            );
          }),
        ),
        const SizedBox(height: 32),
        // Numpad
        ...List.generate(3, (row) {
          return Padding(
            padding: const EdgeInsets.symmetric(vertical: 4),
            child: Row(
              mainAxisAlignment: MainAxisAlignment.center,
              children: List.generate(3, (col) {
                final digit = row * 3 + col + 1;
                return _NumpadButton(
                  digit: '$digit',
                  onTap: () => onDigitPressed('$digit'),
                );
              }),
            ),
          );
        }),
        // Last row: empty, 0, delete
        Padding(
          padding: const EdgeInsets.symmetric(vertical: 4),
          child: Row(
            mainAxisAlignment: MainAxisAlignment.center,
            children: [
              const SizedBox(width: 80),
              _NumpadButton(
                digit: '0',
                onTap: () => onDigitPressed('0'),
              ),
              SizedBox(
                width: 80,
                height: 64,
                child: IconButton(
                  onPressed: onDelete,
                  icon: Icon(Icons.backspace_outlined,
                      color: AppTheme.textSecondary),
                ),
              ),
            ],
          ),
        ),
        if (onSubmit != null) ...[
          const SizedBox(height: 24),
          SizedBox(
            width: double.infinity,
            child: ElevatedButton(
              onPressed: pin.length == pinLength ? onSubmit : null,
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.primary,
                foregroundColor: AppTheme.textOnPrimary,
                disabledBackgroundColor: AppTheme.primary.withValues(alpha: 0.4),
                padding: const EdgeInsets.symmetric(vertical: 16),
                shape: RoundedRectangleBorder(
                  borderRadius: AppTheme.radiusMd,
                ),
                elevation: 0,
              ),
              child: Text(
                'تأكيد',
                style: TextStyle(
                  fontSize: 16,
                  fontWeight: FontWeight.w600,
                  fontFamily: 'Cairo',
                ),
              ),
            ),
          ),
        ],
      ],
    );
  }
}

class _NumpadButton extends StatelessWidget {
  final String digit;
  final VoidCallback onTap;

  const _NumpadButton({
    required this.digit,
    required this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    return SizedBox(
      width: 80,
      height: 64,
      child: TextButton(
        onPressed: onTap,
        style: TextButton.styleFrom(
          foregroundColor: AppTheme.textPrimary,
          shape: RoundedRectangleBorder(borderRadius: AppTheme.radiusMd),
        ),
        child: Text(
          digit,
          style: const TextStyle(
            fontSize: 24,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
    );
  }
}
