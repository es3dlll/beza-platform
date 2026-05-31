import 'package:flutter/material.dart';
import '../core/money.dart';

enum EscrowStatus { initiated, funded, shipped, delivered, released, disputed, refunded }

class OrderModel {
  final String id;
  final String productName;
  final String sellerName;
  final int amountFils;
  final int feeFils;
  final EscrowStatus status;
  final DateTime createdAt;
  final String? trackingCode;

  OrderModel({
    required this.id,
    required this.productName,
    required this.sellerName,
    required this.amountFils,
    required this.feeFils,
    required this.status,
    required this.createdAt,
    this.trackingCode,
  });

  int get totalFils => amountFils + feeFils;

  String get statusLabel {
    switch (status) {
      case EscrowStatus.initiated: return 'قيد الإنشاء';
      case EscrowStatus.funded: return 'محجوزة';
      case EscrowStatus.shipped: return 'تم الشحن';
      case EscrowStatus.delivered: return 'تم التسليم';
      case EscrowStatus.released: return 'تم الدفع';
      case EscrowStatus.disputed: return 'في النزاع';
      case EscrowStatus.refunded: return 'مستردة';
    }
  }

  Color get statusColor {
    switch (status) {
      case EscrowStatus.initiated: return Colors.grey;
      case EscrowStatus.funded: return Colors.blue;
      case EscrowStatus.shipped: return Colors.orange;
      case EscrowStatus.delivered: return Colors.teal;
      case EscrowStatus.released: return Colors.green;
      case EscrowStatus.disputed: return Colors.red;
      case EscrowStatus.refunded: return Colors.purple;
    }
  }
}

class OrderTrackingScreen extends StatelessWidget {
  final OrderModel order;

  const OrderTrackingScreen({super.key, required this.order});

  @override
  Widget build(BuildContext context) {
    final amount = Money.fromFils(order.amountFils);
    final fee = Money.fromFils(order.feeFils);
    final total = Money.fromFils(order.totalFils);

    final steps = [
      ('تم الإنشاء', order.createdAt, order.status.index >= EscrowStatus.initiated.index),
      ('تم الحجز', order.createdAt.add(const Duration(hours: 1)), order.status.index >= EscrowStatus.funded.index),
      ('تم الشحن', order.createdAt.add(const Duration(days: 1)), order.status.index >= EscrowStatus.shipped.index),
      ('تم التسليم', order.createdAt.add(const Duration(days: 3)), order.status.index >= EscrowStatus.delivered.index),
      ('تم الإفراج', order.createdAt.add(const Duration(days: 4)), order.status.index >= EscrowStatus.released.index),
    ];

    return Scaffold(
      appBar: AppBar(title: const Text('تتبع الطلب')),
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
                    Text(order.productName, style: const TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                    const SizedBox(height: 8),
                    Text('البائع: ${order.sellerName}'),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                          decoration: BoxDecoration(
                            color: order.statusColor.withOpacity(0.15),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(order.statusLabel, style: TextStyle(color: order.statusColor, fontWeight: FontWeight.bold)),
                        ),
                        if (order.trackingCode != null) ...[
                          const SizedBox(width: 12),
                          Text('رمز التتبع: ${order.trackingCode}', style: const TextStyle(fontSize: 12, color: Colors.grey)),
                        ],
                      ],
                    ),
                    const Divider(),
                    Text('المبلغ: ${amount.format()}', style: const TextStyle(fontSize: 16)),
                    Text('الرسوم: ${fee.format()}', style: const TextStyle(color: Colors.orange)),
                    Text('الإجمالي: ${total.format()}', style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                  ],
                ),
              ),
            ),
            const SizedBox(height: 16),
            const Text('حالة الطلب', style: TextStyle(fontSize: 16, fontWeight: FontWeight.bold)),
            const SizedBox(height: 8),
            Expanded(
              child: ListView.builder(
                itemCount: steps.length,
                itemBuilder: (ctx, i) {
                  final step = steps[i];
                  return ListTile(
                    leading: CircleAvatar(
                      backgroundColor: step.$3 ? Colors.green : Colors.grey.shade300,
                      child: Icon(step.$3 ? Icons.check : Icons.circle_outlined, color: Colors.white, size: 20),
                    ),
                    title: Text(step.$1),
                    subtitle: Text('${step.$2.toLocal()}'.split('.')[0]),
                  );
                },
              ),
            ),
            if (order.status == EscrowStatus.disputed)
              Padding(
                padding: const EdgeInsets.only(top: 16),
                child: SizedBox(
                  width: double.infinity,
                  child: ElevatedButton.icon(
                    onPressed: () {},
                    icon: const Icon(Icons.support_agent),
                    label: const Text('متابعة النزاع'),
                    style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white, padding: const EdgeInsets.all(16)),
                  ),
                ),
              ),
            if (order.status == EscrowStatus.funded)
              Padding(
                padding: const EdgeInsets.only(top: 16),
                child: SizedBox(
                  width: double.infinity,
                  child: OutlinedButton.icon(
                    onPressed: () {},
                    icon: const Icon(Icons.warning_amber),
                    label: const Text('فتح نزاع'),
                    style: OutlinedButton.styleFrom(foregroundColor: Colors.red, padding: const EdgeInsets.all(16)),
                  ),
                ),
              ),
          ],
        ),
      ),
    );
  }
}
