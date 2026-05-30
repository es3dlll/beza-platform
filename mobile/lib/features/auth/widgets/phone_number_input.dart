import 'package:flutter/material.dart';
import '../../../core/theme/app_theme.dart';

class PhoneNumberInput extends StatelessWidget {
  final TextEditingController controller;
  final String? errorText;
  final void Function(String)? onChanged;

  const PhoneNumberInput({
    super.key,
    required this.controller,
    this.errorText,
    this.onChanged,
  });

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Container(
          decoration: BoxDecoration(
            color: AppTheme.surface,
            borderRadius: AppTheme.radiusMd,
            border: Border.all(
              color: errorText != null ? AppTheme.error : AppTheme.inputBorder,
              width: errorText != null ? 1.5 : 1,
            ),
            boxShadow: AppTheme.shadowMd,
          ),
          child: Row(
            children: [
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 16),
                decoration: BoxDecoration(
                  border: Border(
                    left: BorderSide(color: AppTheme.divider), // RTL
                  ),
                ),
                child: Row(
                  mainAxisSize: MainAxisSize.min,
                  children: [
                    const Icon(Icons.flag, size: 18, color: AppTheme.primary),
                    const SizedBox(width: 6),
                    const Text(
                      '963+',
                      style: TextStyle(
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                        color: AppTheme.textPrimary,
                      ),
                    ),
                    const SizedBox(width: 4),
                    Icon(Icons.keyboard_arrow_down, size: 18, color: AppTheme.textTertiary),
                  ],
                ),
              ),
              Expanded(
                  child: TextField(
                  controller: controller,
                  keyboardType: TextInputType.phone,
                  textDirection: TextDirection.ltr,
                  onChanged: onChanged,
                  textAlign: TextAlign.left,
                  style: const TextStyle(
                    fontSize: 18,
                    fontWeight: FontWeight.w500,
                    color: AppTheme.textPrimary,
                    letterSpacing: 1,
                  ),
                  decoration: InputDecoration(
                    border: InputBorder.none,
                    hintText: '9XXXXXXXX',
                    hintStyle: TextStyle(
                      color: AppTheme.textTertiary,
                      fontSize: 18,
                      fontWeight: FontWeight.w400,
                    ),
                    contentPadding: const EdgeInsets.symmetric(horizontal: 12, vertical: 16),
                  ),
                ),
              ),
            ],
          ),
        ),
        if (errorText != null) ...[
          const SizedBox(height: 8),
          Padding(
            padding: const EdgeInsets.only(right: 4),
            child: Row(
              children: [
                const Icon(Icons.error_outline, size: 14, color: AppTheme.error),
                const SizedBox(width: 6),
                Text(
                  errorText!,
                  style: const TextStyle(
                    fontSize: 12,
                    color: AppTheme.error,
                    fontWeight: FontWeight.w500,
                  ),
                ),
              ],
            ),
          ),
        ],
      ],
    );
  }
}
