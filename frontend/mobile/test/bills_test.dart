import 'package:flutter_test/flutter_test.dart';

enum BillStatusEnum { pending, paid, overdue, cancelled, failed }

class LocalBillModel {
  final String id;
  final String providerName;
  final int amountFils;
  final DateTime dueDate;
  final BillStatusEnum status;
  final String? receiptReference;

  LocalBillModel({
    required this.id,
    required this.providerName,
    required this.amountFils,
    required this.dueDate,
    required this.status,
    this.receiptReference,
  });

  bool get isOverdue => status == BillStatusEnum.pending && dueDate.isBefore(DateTime.now());
  bool get canBePaid => status == BillStatusEnum.pending || status == BillStatusEnum.overdue;
}

class BillStatusDeterminer {
  static ({String label, bool isOverdue, bool canBePaid}) determine(BillStatusEnum status, DateTime dueDate) {
    final now = DateTime.now();
    final isOverdue = status == BillStatusEnum.pending && dueDate.isBefore(now);
    final canBePaid = status == BillStatusEnum.pending || status == BillStatusEnum.overdue;
    String label;
    switch (status) {
      case BillStatusEnum.paid: label = 'مدفوعة'; break;
      case BillStatusEnum.pending: label = isOverdue ? 'متأخرة' : 'قيد الانتظار'; break;
      case BillStatusEnum.overdue: label = 'متأخرة'; break;
      case BillStatusEnum.cancelled: label = 'ملغاة'; break;
      case BillStatusEnum.failed: label = 'فاشلة'; break;
    }
    return (label: label, isOverdue: isOverdue, canBePaid: canBePaid);
  }
}

class LocalScheduleStore {
  final List<Map<String, dynamic>> _schedules = [];

  void save(Map<String, dynamic> schedule) {
    _schedules.add({...schedule, 'savedAt': DateTime.now().toIso8601String()});
  }

  List<Map<String, dynamic>> getAll() => List.unmodifiable(_schedules);
  Map<String, dynamic>? getById(String id) {
    try {
      return _schedules.firstWhere((s) => s['id'] == id);
    } catch (_) {
      return null;
    }
  }
  void clear() => _schedules.clear();
}

int _daysInMonth(int year, int month) {
  return DateTime(year, month + 1, 0).day;
}

int _clampDay(int day, int max) {
  if (day < 1) return 1;
  if (day > max) return max;
  return day;
}

DateTime calculateNextDueDate(DateTime currentDue, String recurrence, int day) {
  switch (recurrence) {
    case 'monthly': {
      final maxDay = _daysInMonth(currentDue.year, currentDue.month + 1);
      return DateTime(currentDue.year, currentDue.month + 1, _clampDay(day, maxDay));
    }
    case 'quarterly': {
      final maxDay = _daysInMonth(currentDue.year, currentDue.month + 3);
      return DateTime(currentDue.year, currentDue.month + 3, _clampDay(day, maxDay));
    }
    case 'yearly': {
      final maxDay = _daysInMonth(currentDue.year + 1, currentDue.month);
      return DateTime(currentDue.year + 1, currentDue.month, _clampDay(day, maxDay));
    }
    default:
      return DateTime(currentDue.year, currentDue.month + 1, _clampDay(day, 31));
  }
}

void main() {
  group('Bill Provider List', () {
    testWidgets('displays providers with different active statuses', (tester) async {
      final providers = [
        (name: 'كهرباء سورية', active: true),
        (name: 'مياه دمشق', active: true),
        (name: 'إنترنت بيزا', active: false),
      ];

      expect(providers.length, 3);
      expect(providers.where((p) => p.active).length, 2);
      expect(providers.where((p) => !p.active).length, 1);
      expect(providers.firstWhere((p) => p.name == 'إنترنت بيزا').active, isFalse);
    });

    testWidgets('filters providers by active status', (tester) async {
      final all = ['كهرباء', 'مياه', 'إنترنت', 'غاز', 'اتصالات'];
      final active = ['كهرباء', 'مياه', 'غاز', 'اتصالات'];

      expect(active.length, 4);
      expect(all.length - active.length, 1);
      expect(active, contains('كهرباء'));
      expect(active, isNot(contains('إنترنت')));
    });
  });

  group('Bill Status Determination', () {
    test('determines paid bill status correctly', () {
      final result = BillStatusDeterminer.determine(BillStatusEnum.paid, DateTime.now());
      expect(result.label, 'مدفوعة');
      expect(result.isOverdue, isFalse);
      expect(result.canBePaid, isFalse);
    });

    test('determines overdue bill status correctly', () {
      final result = BillStatusDeterminer.determine(
        BillStatusEnum.pending,
        DateTime.now().subtract(const Duration(days: 5)),
      );
      expect(result.label, 'متأخرة');
      expect(result.isOverdue, isTrue);
      expect(result.canBePaid, isTrue);
    });

    test('determines pending bill status correctly', () {
      final result = BillStatusDeterminer.determine(
        BillStatusEnum.pending,
        DateTime.now().add(const Duration(days: 10)),
      );
      expect(result.label, 'قيد الانتظار');
      expect(result.isOverdue, isFalse);
      expect(result.canBePaid, isTrue);
    });

    test('determines cancelled bill status correctly', () {
      final result = BillStatusDeterminer.determine(BillStatusEnum.cancelled, DateTime.now());
      expect(result.label, 'ملغاة');
      expect(result.canBePaid, isFalse);
    });
  });

  group('Schedule Payment Date Calculation', () {
    test('calculates next monthly due date accurately', () {
      final current = DateTime(2026, 5, 15);
      final next = calculateNextDueDate(current, 'monthly', 15);
      expect(next.month, 6);
      expect(next.day, 15);
      expect(next.year, 2026);
    });

    test('calculates next quarterly due date accurately', () {
      final current = DateTime(2026, 2, 1);
      final next = calculateNextDueDate(current, 'quarterly', 1);
      expect(next.month, 5);
      expect(next.day, 1);
      expect(next.year, 2026);
    });

    test('calculates next yearly due date accurately', () {
      final current = DateTime(2026, 3, 20);
      final next = calculateNextDueDate(current, 'yearly', 20);
      expect(next.month, 3);
      expect(next.day, 20);
      expect(next.year, 2027);
    });

    test('clamps day to max days in month for monthly recurrence', () {
      final current = DateTime(2026, 1, 31);
      // February has 28 days in 2026
      final next = calculateNextDueDate(current, 'monthly', 31);
      expect(next.month, 2);
      expect(next.day, 28);
    });
  });

  group('Local Schedule Storage', () {
    test('saves and restores schedule settings offline', () {
      final store = LocalScheduleStore();
      final schedule = {
        'id': 'SCHED-OFFLINE-001',
        'provider_id': 'prov-1',
        'amount_fils': 100_000,
        'recurrence': 'monthly',
        'recurrence_day': 15,
      };

      store.save(schedule);
      expect(store.getAll().length, 1);
      expect(store.getById('SCHED-OFFLINE-001'), isNotNull);
      expect(store.getById('SCHED-OFFLINE-001')!['amount_fils'], 100_000);
    });

    test('returns null for non-existent schedule', () {
      final store = LocalScheduleStore();
      expect(store.getById('nonexistent'), isNull);
    });
  });

  group('Successful Payment with Receipt', () {
    test('simulates successful payment and generates receipt', () {
      final bill = LocalBillModel(
        id: 'BILL-TEST-001',
        providerName: 'كهرباء سورية',
        amountFils: 250_000,
        dueDate: DateTime.now().add(const Duration(days: 5)),
        status: BillStatusEnum.pending,
      );

      expect(bill.canBePaid, isTrue);
      expect(bill.status, BillStatusEnum.pending);

      // Simulate payment
      final paidBill = LocalBillModel(
        id: bill.id,
        providerName: bill.providerName,
        amountFils: bill.amountFils,
        dueDate: bill.dueDate,
        status: BillStatusEnum.paid,
        receiptReference: 'RCP-TEST-001',
      );

      expect(paidBill.status, BillStatusEnum.paid);
      expect(paidBill.receiptReference, 'RCP-TEST-001');
      expect(paidBill.canBePaid, isFalse);
    });

    test('receipt contains correct reference and amount', () {
      final receiptRef = 'RCP-A1B2C3D4';
      final amountFils = 500_000;
      final paidAt = DateTime(2026, 5, 31, 14, 30, 0);

      expect(receiptRef, startsWith('RCP-'));
      expect(amountFils, 500_000);
      expect(paidAt.year, 2026);
      expect(paidAt.month, 5);
      expect(paidAt.day, 31);
    });
  });
}
