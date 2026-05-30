import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';
import '../../auth/providers/auth_provider.dart';

class SettingsScreen extends ConsumerWidget {
  const SettingsScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    final auth = ref.watch(authProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('الإعدادات')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _MenuGroup(
            title: 'الأمان',
            items: [
              _MenuItem(
                icon: Icons.lock_outline,
                label: 'تغيير رمز PIN',
                onTap: () => context.push('/settings/change-pin'),
              ),
              _MenuItem(
                icon: Icons.fingerprint,
                label: 'البصمة',
                trailing: Switch(
                  value: auth.isBiometricEnabled,
                  activeThumbColor: AppTheme.primary,
                  onChanged: (v) {
                    if (v) {
                      ref.read(authProvider.notifier).enableBiometric();
                    } else {
                      ref.read(authProvider.notifier).skipBiometric();
                    }
                  },
                ),
              ),
              _MenuItem(
                icon: Icons.phone_outlined,
                label: 'التحقق من رقم الهاتف',
                onTap: () => context.push('/settings/verify-phone'),
              ),
            ],
          ),
          _MenuGroup(
            title: 'التطبيق',
            items: [
              _MenuItem(
                icon: Icons.language,
                label: 'اللغة',
                trailing: const Text('العربية', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
              ),
              _MenuItem(
                icon: Icons.info_outline,
                label: 'حول التطبيق',
                onTap: () => context.push('/settings/about'),
              ),
            ],
          ),
          _MenuGroup(
            title: 'الإجراءات',
            items: [
              _MenuItem(
                icon: Icons.logout,
                label: 'تسجيل الخروج',
                iconColor: AppTheme.error,
                textColor: AppTheme.error,
                onTap: () async {
                  final confirmed = await showDialog<bool>(
                    context: context,
                    builder: (_) => AlertDialog(
                      title: const Text('تسجيل الخروج'),
                      content: const Text('هل أنت متأكد من تسجيل الخروج؟'),
                      actions: [
                        TextButton(onPressed: () => Navigator.pop(context, false), child: const Text('إلغاء')),
                        TextButton(onPressed: () => Navigator.pop(context, true), child: const Text('تأكيد', style: TextStyle(color: AppTheme.error))),
                      ],
                    ),
                  );
                  if (confirmed == true) {
                    ref.read(authProvider.notifier).logout();
                  }
                },
              ),
            ],
          ),
        ],
      ),
    );
  }
}

class _MenuGroup extends StatelessWidget {
  final String title;
  final List<_MenuItem> items;

  const _MenuGroup({required this.title, required this.items});

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        Padding(
          padding: const EdgeInsets.only(right: 4, top: 16, bottom: 10),
          child: Row(
            children: [
              Container(
                width: 4,
                height: 20,
                decoration: BoxDecoration(
                  gradient: AppTheme.primaryGradient,
                  borderRadius: BorderRadius.circular(2),
                ),
              ),
              const SizedBox(width: 10),
              Text(title, style: const TextStyle(fontFamily: 'Cairo', fontSize: 16, fontWeight: FontWeight.bold, color: AppTheme.textPrimary)),
            ],
          ),
        ),
        Container(
          decoration: AppTheme.cardDecoration,
          child: Column(
            children: items.asMap().entries.map((entry) {
              final i = entry.key;
              final item = entry.value;
              return InkWell(
                onTap: item.onTap,
                borderRadius: AppTheme.radiusMd,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                  decoration: i != items.length - 1 ? AppTheme.dividerDecoration : null,
                  child: ListTile(
                    leading: Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: (item.iconColor ?? AppTheme.primary).withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(item.icon, color: item.iconColor ?? AppTheme.primary, size: 22),
                    ),
                    title: Text(
                      item.label,
                      style: TextStyle(
                        fontFamily: 'Cairo',
                        fontSize: 15,
                        fontWeight: FontWeight.w500,
                        color: item.textColor ?? AppTheme.textPrimary,
                      ),
                    ),
                    trailing: item.trailing ?? (item.onTap != null ? const Icon(Icons.chevron_left, color: AppTheme.textTertiary) : null),
                  ),
                ),
              );
            }).toList(),
          ),
        ),
      ],
    );
  }
}

class _MenuItem {
  final IconData icon;
  final String label;
  final VoidCallback? onTap;
  final Widget? trailing;
  final Color? iconColor;
  final Color? textColor;

  const _MenuItem({
    required this.icon,
    required this.label,
    this.onTap,
    this.trailing,
    this.iconColor,
    this.textColor,
  });
}
