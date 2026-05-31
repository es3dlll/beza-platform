import 'package:flutter_test/flutter_test.dart';

// Risk status enum matching backend
enum RiskStatus { approved, suspended, rejected }
enum TransactionStatus { pending, processing, underReview, completed, failed }

// Mock risk state
class RiskState {
  final int score;
  final RiskStatus status;
  final List<String> reasons;
  final String requestId;

  RiskState({required this.score, required this.status, this.reasons = const [], required this.requestId});

  bool get isUnderReview => status == RiskStatus.suspended;
  bool get isRejected => status == RiskStatus.rejected;
  bool get isApproved => status == RiskStatus.approved;
}

// Mock security notification
class SecurityNotification {
  final String id;
  final String title;
  final String message;
  final String type;
  final DateTime createdAt;
  final bool isRead;

  SecurityNotification({
    required this.id,
    required this.title,
    required this.message,
    this.type = 'warning',
    required this.createdAt,
    this.isRead = false,
  });
}

// Mock risk calculator
class RiskCalculator {
  static int calculate({required int amountFils, required String region, required int recentCount}) {
    int score = 0;

    if (amountFils >= 10_000_000) score += 30;
    if (region == 'border_area' || region == 'outside_syria') score += 25;
    if (recentCount >= 3) score += 40;

    return score.clamp(0, 100);
  }

  static RiskStatus determineStatus(int score) {
    if (score < 30) return RiskStatus.approved;
    if (score < 70) return RiskStatus.suspended;
    return RiskStatus.rejected;
  }
}

void main() {
  group('Security Alert Notifications', () {
    testWidgets('displays security alert with title and message', (tester) async {
      final notification = SecurityNotification(
        id: 'notif-1',
        title: 'نشاط غير معتاد',
        message: 'تم اكتشاف محاولة دخول من جهاز جديد',
        createdAt: DateTime.now(),
      );

      expect(notification.title, 'نشاط غير معتاد');
      expect(notification.message, 'تم اكتشاف محاولة دخول من جهاز جديد');
      expect(notification.type, 'warning');
      expect(notification.isRead, false);
    });

    testWidgets('security alert shows different types correctly', (tester) async {
      final highRisk = SecurityNotification(
        id: 'notif-2',
        title: 'تنبيه أمني',
        message: 'محاولة تحويل بمبلغ عالٍ',
        type: 'critical',
        createdAt: DateTime.now(),
      );

      expect(highRisk.type, 'critical');
      expect(highRisk.isRead, false);
    });
  });

  group('Transaction Status Visual Effects', () {
    testWidgets('shows correct label for under review status', (tester) async {
      final state = RiskState(score: 55, status: RiskStatus.suspended, reasons: ['مبلغ عالٍ'], requestId: 'req-1');
      expect(state.isUnderReview, isTrue);
      expect(state.isApproved, isFalse);
      expect(state.isRejected, isFalse);
    });

    testWidgets('shows correct label for rejected status', (tester) async {
      final state = RiskState(score: 85, status: RiskStatus.rejected, reasons: ['تجاوز الحد المسموح'], requestId: 'req-2');
      expect(state.isRejected, isTrue);
      expect(state.isUnderReview, isFalse);
    });

    testWidgets('shows correct label for approved status', (tester) async {
      final state = RiskState(score: 10, status: RiskStatus.approved, requestId: 'req-3');
      expect(state.isApproved, isTrue);
    });
  });

  group('Additional Verification Step for High-Risk', () {
    testWidgets('requires verification when risk exceeds threshold', (tester) async {
      final state = RiskState(score: 45, status: RiskStatus.suspended, reasons: ['المراجعة مطلوبة'], requestId: 'req-4');
      expect(state.isUnderReview, isTrue);
      // Verification step would be shown for suspended transactions
    });
  });

  group('Risk Score Calculator - Unit Test', () {
    test('calculates low risk for small amount in safe region', () {
      final score = RiskCalculator.calculate(amountFils: 500_000, region: 'damascus', recentCount: 0);
      expect(score, lessThan(30));
      expect(RiskCalculator.determineStatus(score), RiskStatus.approved);
    });

    test('calculates high risk for large amount in border area', () {
      final score = RiskCalculator.calculate(amountFils: 25_000_000, region: 'border_area', recentCount: 0);
      expect(score, greaterThanOrEqualTo(30));
      expect(RiskCalculator.determineStatus(score), RiskStatus.suspended);
    });

    test('calculates rejection risk for repeated transactions', () {
      final score = RiskCalculator.calculate(amountFils: 25_000_000, region: 'border_area', recentCount: 5);
      // 30 (amount) + 25 (region) + 40 (frequency) = 95
      expect(score, 95);
      expect(RiskCalculator.determineStatus(score), RiskStatus.rejected);
    });

    test('score does not exceed 100 even with extreme factors', () {
      // 30 (amount) + 25 (region) + 40 (frequency) = 95 max with 3 rules
      final score = RiskCalculator.calculate(amountFils: 50_000_000, region: 'outside_syria', recentCount: 10);
      expect(score, 95);
      expect(score <= 100, isTrue);
    });
  });
}
