import 'package:flutter/material.dart';
import '../../core/money.dart';

class BillDetailModel {
  final String id;
  final String providerName;
  final String accountNumber;
  final int amountFils;
  final DateTime dueDate;
  final String status;
  final int? feeFils;

  BillDetailModel({
    required this.id,
    required this.providerName,
    required this.accountNumber,
    required this.amountFils,
    required this.dueDate,
    required this.status,
    this.feeFils,
  });

  bool get isOverdue => status == 'pending' && dueDate.isBefore(DateTime.now());
  bool get canBePaid => status == 'pending' || status == 'overdue';
}

class BillDetailScreen extends StatelessWidget {
  final BillDetailModel bill;

  const BillDetailScreen({super.key, required this.bill});

  @override
  Widget build(BuildContext context) {
    final amount = Money.fromFils(bill.amountFils);
    final fee = bill.feeFils != null ? Money.fromFils(bill.feeFils!) : null;
    final total = fee != null ? Money.fromFils(bill.amountFils + bill.feeFils!) : amount;

    return Scaffold(
      appBar: AppBar(title: Text(bill.providerName)),
      body: Padding(
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
                    Text('رقم الحساب: ${bill.accountNumber}', style: const TextStyle(fontSize: 16)),
                    const SizedBox(height: 8),
                    Text('تاريخ الاستحقاق: ${bill.dueDate.toLocal().toString().split(' ')[0]}'),
                    const SizedBox(height: 8),
                    if (bill.isOverdue)
                      const Text('فاتورة متأخرة', style: TextStyle(color: Colors.red, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 8),
                    Text('المبلغ: ${amount.format()}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                    if (fee != null) ...[
                      const SizedBox(height: 4),
                      Text('الرسوم: ${fee.format()}', style: const TextStyle(color: Colors.orange)),
                    ],
                    const Divider(),
                    Text('الإجمالي: ${total.format()}', style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                  ],
                ),
              ),
            ),
            const Spacer(),
            if (bill.canBePaid) ...[
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () {},
                  icon: const Icon(Icons.payment),
                  label: const Text('دفع الآن'),
                  style: ElevatedButton.styleFrom(padding: const EdgeInsets.all(16)),
                ),
              ),
              const SizedBox(height: 12),
              SizedBox(
                width: double.infinity,
                child: OutlinedButton.icon(
                  onPressed: () {},
                  icon: const Icon(Icons.schedule),
                  label: const Text('جدولة الدفع'),
                  style: OutlinedButton.styleFrom(padding: const EdgeInsets.all(16)),
                ),
              ),
            ],
            if (!bill.canBePaid)
              const Center(child: Text('تم دفع هذه الفاتورة', style: TextStyle(color: Colors.green, fontSize: 16))),
          ],
        ),
      ),
    );
  }
}
