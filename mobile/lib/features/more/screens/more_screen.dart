import 'package:flutter/material.dart';
import 'package:go_router/go_router.dart';
import '../../../core/theme/app_theme.dart';

class MoreScreen extends StatelessWidget {
  const MoreScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('المزيد')),
      body: ListView(
        padding: const EdgeInsets.all(16),
        children: [
          _MenuGroup(
            title: 'الحساب',
            items: [
              _MenuItem(icon: Icons.person_outline, label: 'الملف الشخصي', route: '/profile'),
              _MenuItem(icon: Icons.credit_card, label: 'بطاقاتي', route: '/cards'),
              _MenuItem(icon: Icons.notifications_outlined, label: 'الإشعارات', route: '/notifications'),
            ],
          ),
          _MenuGroup(
            title: 'الخدمات المالية',
            items: [
              _MenuItem(icon: Icons.savings, label: 'أهداف الادخار', route: '/savings'),
              _MenuItem(icon: Icons.star, label: 'نقاط الولاء', route: '/loyalty'),
              _MenuItem(icon: Icons.currency_exchange, label: 'صرافة', route: '/fx'),
              _MenuItem(icon: Icons.send_to_mobile, label: 'حوالات', route: '/remittance'),
              _MenuItem(icon: Icons.receipt_long, label: 'فواتير', route: '/bills'),
              _MenuItem(icon: Icons.store, label: 'تجار', route: '/merchant'),
              _MenuItem(icon: Icons.person_pin, label: 'وكيل', route: '/agent'),
              _MenuItem(icon: Icons.account_balance, label: 'تحصيلات حكومية', route: '/gov-collections'),
              _MenuItem(icon: Icons.business, label: 'رواتب', route: '/payroll'),
            ],
          ),
          _MenuGroup(
            title: 'تمويل وتطوير',
            items: [
              _MenuItem(icon: Icons.school, label: 'تعليم', route: '/education'),
              _MenuItem(icon: Icons.volunteer_activism, label: 'إغاثي', route: '/humanitarian'),
              _MenuItem(icon: Icons.trending_up, label: 'تمويل', route: '/financing'),
            ],
          ),
          _MenuGroup(
            title: 'تقنية',
            items: [
              _MenuItem(icon: Icons.api, label: 'خدمات مصرفية مفتوحة', route: '/open-finance'),
            ],
          ),
          _MenuGroup(
            title: 'الدعم',
            items: [
              _MenuItem(icon: Icons.headset_mic, label: 'خدمة العملاء', route: ''),
              _MenuItem(icon: Icons.settings, label: 'الإعدادات', route: '/settings'),
              _MenuItem(icon: Icons.info_outline, label: 'حول التطبيق', route: ''),
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
            children: items.map((item) {
              return InkWell(
                onTap: item.route.isNotEmpty ? () => context.push(item.route) : null,
                borderRadius: item.route.isNotEmpty ? AppTheme.radiusMd : null,
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 4, vertical: 2),
                  decoration: item != items.last ? AppTheme.dividerDecoration : null,
                  child: ListTile(
                    leading: Container(
                      padding: const EdgeInsets.all(10),
                      decoration: BoxDecoration(
                        color: AppTheme.primary.withValues(alpha: 0.1),
                        borderRadius: BorderRadius.circular(12),
                      ),
                      child: Icon(item.icon, color: AppTheme.primary, size: 22),
                    ),
                    title: Text(item.label, style: const TextStyle(fontFamily: 'Cairo', fontSize: 15, fontWeight: FontWeight.w500, color: AppTheme.textPrimary)),
                    trailing: const Icon(Icons.chevron_left, color: AppTheme.textTertiary),
                    onTap: null,
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
  final String route;
  const _MenuItem({required this.icon, required this.label, required this.route});
}
