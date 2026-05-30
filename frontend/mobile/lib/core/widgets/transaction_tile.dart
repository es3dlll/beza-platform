import 'package:flutter/material.dart';
import '../theme/app_theme.dart';

enum TransactionType { credit, debit, pending, failed }

class TransactionTile extends StatelessWidget {
  final String title;
  final String? subtitle;
  final String amount;
  final String? currency;
  final TransactionType type;
  final DateTime? timestamp;
  final VoidCallback? onTap;

  const TransactionTile({
    super.key,
    required this.title,
    this.subtitle,
    required this.amount,
    this.currency = 'SYP',
    this.type = TransactionType.debit,
    this.timestamp,
    this.onTap,
  });

  @override
  Widget build(BuildContext context) {
    final (IconData icon, Color iconColor) = switch (type) {
      TransactionType.credit => (Icons.arrow_downward, AppTheme.success),
      TransactionType.debit => (Icons.arrow_upward, AppTheme.error),
      TransactionType.pending => (Icons.schedule, AppTheme.warning),
      TransactionType.failed => (Icons.close, AppTheme.error),
    };

    final amountColor = switch (type) {
      TransactionType.credit => AppTheme.success,
      TransactionType.debit => AppTheme.error,
      TransactionType.pending => AppTheme.warning,
      TransactionType.failed => AppTheme.error,
    };

    final amountPrefix = switch (type) {
      TransactionType.credit => '+',
      TransactionType.debit => '-',
      TransactionType.pending => '',
      TransactionType.failed => '',
    };

    return ListTile(
      onTap: onTap,
      leading: Container(
        width: 44,
        height: 44,
        decoration: BoxDecoration(
          color: iconColor.withValues(alpha: 0.1),
          borderRadius: AppTheme.radiusMd,
        ),
        child: Icon(icon, color: iconColor, size: 20),
      ),
      title: Text(
        title,
        style: TextStyle(
          fontSize: 14,
          fontWeight: FontWeight.w500,
          color: AppTheme.textPrimary,
          fontFamily: 'Cairo',
        ),
      ),
      subtitle: subtitle != null || timestamp != null
          ? Text(
              [
                if (subtitle != null) subtitle!,
                if (timestamp != null)
                  _formatTimestamp(timestamp!),
              ].join(' · '),
              style: TextStyle(
                fontSize: 12,
                color: AppTheme.textTertiary,
                fontFamily: 'Cairo',
              ),
            )
          : null,
      trailing: Column(
        mainAxisAlignment: MainAxisAlignment.center,
        crossAxisAlignment: CrossAxisAlignment.end,
        children: [
          Text(
            '$amountPrefix$amount',
            style: TextStyle(
              fontSize: 15,
              fontWeight: FontWeight.w600,
              color: amountColor,
              fontFamily: 'Cairo',
            ),
          ),
          Text(
            currency!,
            style: TextStyle(
              fontSize: 11,
              color: AppTheme.textTertiary,
              fontFamily: 'Cairo',
            ),
          ),
        ],
      ),
      contentPadding: const EdgeInsets.symmetric(horizontal: 0, vertical: 2),
    );
  }

  String _formatTimestamp(DateTime dt) {
    final now = DateTime.now();
    final diff = now.difference(dt);

    if (diff.inMinutes < 60) return 'منذ ${diff.inMinutes} د';
    if (diff.inHours < 24) return 'منذ ${diff.inHours} س';
    if (diff.inDays < 7) return 'منذ ${diff.inDays} ي';
    return '${dt.day}/${dt.month}/${dt.year}';
  }
}
