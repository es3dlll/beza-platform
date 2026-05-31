import 'package:flutter/material.dart';
import '../../core/money.dart';

enum RecurrenceType { monthly, quarterly, yearly }

class ScheduleSetupModel {
  final String providerId;
  final String providerName;
  final String accountNumber;
  final int amountFils;
  final RecurrenceType recurrence;
  final int recurrenceDay;
  final DateTime nextExecutionDate;

  ScheduleSetupModel({
    required this.providerId,
    required this.providerName,
    required this.accountNumber,
    required this.amountFils,
    required this.recurrence,
    required this.recurrenceDay,
    required this.nextExecutionDate,
  });
}

class ScheduleSetupScreen extends StatefulWidget {
  final String providerId;
  final String providerName;
  final String accountNumber;
  final int amountFils;

  const ScheduleSetupScreen({
    super.key,
    required this.providerId,
    required this.providerName,
    required this.accountNumber,
    required this.amountFils,
  });

  @override
  State<ScheduleSetupScreen> createState() => _ScheduleSetupScreenState();
}

class _ScheduleSetupScreenState extends State<ScheduleSetupScreen> {
  RecurrenceType _recurrence = RecurrenceType.monthly;
  int _recurrenceDay = DateTime.now().day.clamp(1, 28);

  DateTime get _nextDate {
    final now = DateTime.now();
    return DateTime(now.year, now.month + 1, _recurrenceDay).clamp(
      DateTime(2000),
      DateTime(2100),
    );
  }

  @override
  Widget build(BuildContext context) {
    final amount = Money.fromFils(widget.amountFils);

    return Scaffold(
      appBar: AppBar(title: const Text('جدولة الدفع')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            Card(
              child: Padding(
                padding: const EdgeInsets.all(16),
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text('المزود: ${widget.providerName}'),
                    Text('الحساب: ${widget.accountNumber}'),
                    Text('المبلغ: ${amount.format()}'),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            const Text('اختيار التكرار', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            ...RecurrenceType.values.map((r) => RadioListTile<RecurrenceType>(
              title: Text(_recurrenceLabel(r)),
              value: r,
              groupValue: _recurrence,
              onChanged: (v) => setState(() => _recurrence = v!),
            )),
            const SizedBox(height: 16),
            Row(
              children: [
                const Text('يوم الشهر: '),
                Expanded(
                  child: Slider(
                    value: _recurrenceDay.toDouble(),
                    min: 1,
                    max: 28,
                    divisions: 27,
                    label: _recurrenceDay.toString(),
                    onChanged: (v) => setState(() => _recurrenceDay = v.round()),
                  ),
                ),
                Text('$_recurrenceDay'),
              ],
            ),
            const SizedBox(height: 8),
            Text('تاريخ التنفيذ التالي: ${_nextDate.toLocal().toString().split(' ')[0]}'),
            const SizedBox(height: 8),
            Text(
              'ملخص: سيتم خصم ${amount.format()} ${_recurrenceLabel(_recurrence)} من محفظتك تلقائياً.',
              style: const TextStyle(fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 16),
            const Divider(),
            const Text(
              'بموافقتي، أوضح أن هذه جدولة دفع تلقائي. يمكنني إلغاؤها في أي وقت.',
              style: TextStyle(fontSize: 12, color: Colors.grey),
            ),
            const SizedBox(height: 24),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton.icon(
                onPressed: () {},
                icon: const Icon(Icons.check),
                label: const Text('تأكيد الجدولة'),
                style: ElevatedButton.styleFrom(padding: const EdgeInsets.all(16)),
              ),
            ),
          ],
        ),
      ),
    );
  }

  String _recurrenceLabel(RecurrenceType r) {
    switch (r) {
      case RecurrenceType.monthly: return 'شهري';
      case RecurrenceType.quarterly: return 'ربع سنوي';
      case RecurrenceType.yearly: return 'سنوي';
    }
  }
}
