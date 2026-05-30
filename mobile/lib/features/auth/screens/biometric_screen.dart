import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../widgets/auth_button.dart';
import '../widgets/biometric_icon.dart';
import '../providers/auth_provider.dart';

class BiometricScreen extends ConsumerWidget {
  const BiometricScreen({super.key});

  @override
  Widget build(BuildContext context, WidgetRef ref) {
    return Scaffold(
      backgroundColor: Colors.white,
      appBar: AppBar(
        backgroundColor: Colors.transparent,
        elevation: 0,
        leading: IconButton(
          icon: const Icon(Icons.arrow_back, color: Color(0xFF212121)),
          onPressed: () => Navigator.of(context).pop(),
        ),
      ),
      body: SafeArea(
        child: Padding(
          padding: const EdgeInsets.symmetric(horizontal: 28),
          child: Column(
            children: [
              const Spacer(flex: 2),
              Container(
                width: 120,
                height: 120,
                decoration: BoxDecoration(
                  shape: BoxShape.circle,
                  gradient: const LinearGradient(
                    colors: [Color(0xFF2E7D32), Color(0xFF1B5E20)],
                  ),
                  boxShadow: [
                    BoxShadow(
                      color: const Color(0xFF2E7D32).withValues(alpha: 0.3),
                      blurRadius: 20,
                      offset: const Offset(0, 8),
                    ),
                  ],
                ),
                child: const Icon(
                  Icons.fingerprint,
                  size: 56,
                  color: Colors.white,
                ),
              ),
              const SizedBox(height: 32),
              const Text(
                'فعّل البصمة لتسجيل الدخول السريع',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 22,
                  fontWeight: FontWeight.w700,
                  color: Color(0xFF212121),
                  fontFamily: 'NotoNaskhArabic',
                ),
              ),
              const SizedBox(height: 12),
              Text(
                'استخدم بصمة إصبعك أو Face ID لتسجيل الدخول بسرعة وأمان دون الحاجة إلى إدخال PIN في كل مرة',
                textAlign: TextAlign.center,
                style: TextStyle(
                  fontSize: 14,
                  fontWeight: FontWeight.w400,
                  color: Colors.grey[600],
                  fontFamily: 'NotoNaskhArabic',
                  height: 1.5,
                ),
              ),
              const SizedBox(height: 40),
              _buildFeatureRow(
                Icons.security,
                'محمي بتقنية التشفير الكامل',
              ),
              const SizedBox(height: 16),
              _buildFeatureRow(
                Icons.speed,
                'دخول فوري بنقرة واحدة',
              ),
              const SizedBox(height: 16),
              _buildFeatureRow(
                Icons.phonelink_lock,
                'بياناتك آمنة على جهازك فقط',
              ),
              const Spacer(flex: 2),
              AuthButton(
                label: 'تفعيل',
                onPressed: () async {
                  await ref
                      .read(authProvider.notifier)
                      .setBiometricEnabled(true);
                  if (context.mounted) {
                    Navigator.of(context)
                        .pushReplacementNamed('/home');
                  }
                },
              ),
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                height: 56,
                child: TextButton(
                  onPressed: () async {
                    await ref
                        .read(authProvider.notifier)
                        .setBiometricEnabled(false);
                    if (context.mounted) {
                      Navigator.of(context)
                          .pushReplacementNamed('/home');
                    }
                  },
                  child: const Text(
                    'تخطي',
                    style: TextStyle(
                      color: Color(0xFF757575),
                      fontSize: 16,
                      fontWeight: FontWeight.w600,
                      fontFamily: 'NotoNaskhArabic',
                    ),
                  ),
                ),
              ),
              const Spacer(flex: 1),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildFeatureRow(IconData icon, String text) {
    return Row(
      children: [
        Container(
          width: 44,
          height: 44,
          decoration: BoxDecoration(
            shape: BoxShape.circle,
            color: const Color(0xFFE8F5E9),
          ),
          child: Icon(icon, size: 22, color: const Color(0xFF2E7D32)),
        ),
        const SizedBox(width: 16),
        Text(
          text,
          style: const TextStyle(
            fontSize: 15,
            fontWeight: FontWeight.w500,
            color: Color(0xFF424242),
            fontFamily: 'NotoNaskhArabic',
          ),
        ),
      ],
    );
  }
}
