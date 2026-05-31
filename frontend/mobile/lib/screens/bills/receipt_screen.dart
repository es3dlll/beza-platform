import 'package:flutter/material.dart';
import 'package:flutter/services.dart';
import '../../core/money.dart';

class ReceiptModel {
  final String reference;
  final String providerName;
  final String accountNumber;
  final int amountFils;
  final int? feeFils;
  final DateTime paidAt;
  final String status;

  ReceiptModel({
    required this.reference,
    required this.providerName,
    required this.accountNumber,
    required this.amountFils,
    this.feeFils,
    required this.paidAt,
    required this.status,
  });
}

class ReceiptScreen extends StatelessWidget {
  final ReceiptModel receipt;

  const ReceiptScreen({super.key, required this.receipt});

  @override
  Widget build(BuildContext context) {
    final amount = Money.fromFils(receipt.amountFils);
    final fee = receipt.feeFils != null ? Money.fromFils(receipt.feeFils!) : null;
    final total = fee != null ? Money.fromFils(receipt.amountFils + receipt.feeFils!) : amount;

    return Scaffold(
      appBar: AppBar(
        title: const Text('إيصال الدفع'),
        actions: [
          IconButton(
            icon: const Icon(Icons.share),
            onPressed: () {},
            tooltip: 'مشاركة',
          ),
        ],
      ),
      body: Center(
        child: Padding(
          padding: const EdgeInsets.all(24),
          child: Card(
            child: Padding(
              padding: const EdgeInsets.all(24),
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  const Icon(Icons.check_circle, color: Colors.green, size: 64),
                  const SizedBox(height: 12),
                  const Text('تم الدفع بنجاح', style: TextStyle(fontSize: 20, fontWeight: FontWeight.bold)),
                  const SizedBox(height: 24),
                  _receiptRow('المرجع', receipt.reference),
                  _receiptRow('المزود', receipt.providerName),
                  _receiptRow('الحساب', receipt.accountNumber),
                  _receiptRow('التاريخ', receipt.paidAt.toLocal().toString().split('.')[0]),
                  if (fee != null) _receiptRow('الرسوم', fee.format()),
                  const Divider(),
                  _receiptRow('الإجمالي', total.format()),
                  const SizedBox(height: 24),
                  Row(
                    mainAxisAlignment: MainAxisAlignment.spaceEvenly,
                    children: [
                      OutlinedButton.icon(
                        onPressed: () {},
                        icon: const Icon(Icons.save),
                        label: const Text('حفظ'),
                      ),
                      ElevatedButton.icon(
                        onPressed: () {
                          Clipboard.setData(ClipboardData(text: receipt.reference));
                          ScaffoldMessenger.of(context).showSnackBar(
                            const SnackBar(content: Text('تم نسخ المرجع')),
                          );
                        },
                        icon: const Icon(Icons.copy),
                        label: const Text('نسخ المرجع'),
                      ),
                    ],
                  ),
                ],
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _receiptRow(String label, String value) {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 4),
      child: Row(
        mainAxisAlignment: MainAxisAlignment.spaceBetween,
        children: [
          Text(label, style: const TextStyle(color: Colors.grey)),
          Text(value, style: const TextStyle(fontWeight: FontWeight.bold)),
        ],
      ),
    );
  }
}
