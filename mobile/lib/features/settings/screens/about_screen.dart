import 'package:flutter/material.dart';
import '../../../core/theme/app_theme.dart';

class AboutScreen extends StatelessWidget {
  const AboutScreen({super.key});

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('حول التطبيق')),
      body: ListView(
        padding: const EdgeInsets.all(24),
        children: [
          const SizedBox(height: 24),
          Center(
            child: Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(
                gradient: AppTheme.primaryGradient,
                borderRadius: BorderRadius.circular(20),
              ),
              child: const Icon(Icons.account_balance, size: 48, color: Colors.white),
            ),
          ),
          const SizedBox(height: 20),
          const Center(
            child: Text(
              'Beza Finance',
              style: TextStyle(fontFamily: 'Cairo', fontSize: 24, fontWeight: FontWeight.bold, color: AppTheme.textPrimary),
            ),
          ),
          const SizedBox(height: 8),
          const Center(
            child: Text(
              'منصة الخدمات المالية الرقمية',
              style: TextStyle(fontFamily: 'Cairo', fontSize: 15, color: AppTheme.textSecondary),
            ),
          ),
          const SizedBox(height: 32),
          _infoRow('الإصدار', '1.0.0'),
          const Divider(height: 20),
          _infoRow('بيئة التشغيل', 'التطوير'),
          const Divider(height: 20),
          _infoRow('آخر تحديث', '2026'),
          const SizedBox(height: 32),
          const Text(
            'تقدم Beza Finance حلولاً مالية شاملة تشمل المحافظ الرقمية، والتحويلات، والصرافة، ودفع الفواتير، والتمويل، والتكافل، وغيرها من الخدمات المالية المبتكرة.',
            style: TextStyle(fontFamily: 'Cairo', fontSize: 14, color: AppTheme.textSecondary, height: 1.6),
            textAlign: TextAlign.center,
          ),
        ],
      ),
    );
  }

  Widget _infoRow(String label, String value) {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Text(label, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 14)),
        Text(value, style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w600, fontSize: 14, color: AppTheme.textPrimary)),
      ],
    );
  }
}
