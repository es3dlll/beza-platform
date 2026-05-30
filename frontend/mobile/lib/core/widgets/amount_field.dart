import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../theme/app_theme.dart';

class AmountField extends StatelessWidget {
  final TextEditingController? controller;
  final String? label;
  final String? currency;
  final String? errorText;
  final ValueChanged<String>? onChanged;

  const AmountField({
    super.key,
    this.controller,
    this.label,
    this.currency = 'SYP',
    this.errorText,
    this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      mainAxisSize: MainAxisSize.min,
      children: [
        if (label != null) ...[
          Text(
            label!,
            style: TextStyle(
              fontSize: 14,
              fontWeight: FontWeight.w500,
              color: AppTheme.textPrimary,
              fontFamily: 'Cairo',
            ),
          ),
          const SizedBox(height: 6),
        ],
        TextFormField(
          controller: controller,
          keyboardType: TextInputType.number,
          inputFormatters: [FilteringTextInputFormatter.digitsOnly],
          onChanged: onChanged,
          style: TextStyle(
            fontSize: 24,
            fontWeight: FontWeight.bold,
            color: AppTheme.textPrimary,
            fontFamily: 'Cairo',
          ),
          decoration: InputDecoration(
            prefixText: '$currency ',
            prefixStyle: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: AppTheme.accent,
              fontFamily: 'Cairo',
            ),
            hintText: '0',
            hintStyle: TextStyle(
              fontSize: 24,
              fontWeight: FontWeight.bold,
              color: AppTheme.textTertiary,
              fontFamily: 'Cairo',
            ),
            errorText: errorText,
            filled: true,
            fillColor: AppTheme.surface,
            contentPadding: const EdgeInsets.symmetric(
              horizontal: 16,
              vertical: 20,
            ),
            border: OutlineInputBorder(
              borderRadius: AppTheme.radiusMd,
              borderSide: BorderSide(color: AppTheme.inputBorder),
            ),
            enabledBorder: OutlineInputBorder(
              borderRadius: AppTheme.radiusMd,
              borderSide: BorderSide(color: AppTheme.inputBorder),
            ),
            focusedBorder: OutlineInputBorder(
              borderRadius: AppTheme.radiusMd,
              borderSide: BorderSide(color: AppTheme.primary, width: 1.5),
            ),
            errorBorder: OutlineInputBorder(
              borderRadius: AppTheme.radiusMd,
              borderSide: BorderSide(color: AppTheme.error),
            ),
          ),
        ),
      ],
    );
  }
}
