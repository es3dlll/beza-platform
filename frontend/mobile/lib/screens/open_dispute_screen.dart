import 'package:flutter/material.dart';

class DisputeReason {
  final String key;
  final String label;

  const DisputeReason(this.key, this.label);
}

class OpenDisputeScreen extends StatefulWidget {
  final String orderId;
  final String productName;
  final String sellerName;

  const OpenDisputeScreen({
    super.key,
    required this.orderId,
    required this.productName,
    required this.sellerName,
  });

  @override
  State<OpenDisputeScreen> createState() => _OpenDisputeScreenState();
}

class _OpenDisputeScreenState extends State<OpenDisputeScreen> {
  final _formKey = GlobalKey<FormState>();
  String? _selectedReason;
  final _descriptionController = TextEditingController();
  final List<String> _documentUrls = [];
  final _urlController = TextEditingController();

  static const _reasons = [
    DisputeReason('product_not_as_described', 'المنتج غير مطابق للوصف'),
    DisputeReason('damaged', 'المنتج تالف'),
    DisputeReason('late_delivery', 'تأخير في التسليم'),
    DisputeReason('no_delivery', 'لم يتم التسليم'),
    DisputeReason('wrong_item', 'منتج خاطئ'),
    DisputeReason('seller_unresponsive', 'البائع غير متجاوب'),
    DisputeReason('other', 'سبب آخر'),
  ];

  @override
  void dispose() {
    _descriptionController.dispose();
    _urlController.dispose();
    super.dispose();
  }

  void _addDocumentUrl() {
    final url = _urlController.text.trim();
    if (url.isNotEmpty) {
      setState(() => _documentUrls.add(url));
      _urlController.clear();
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('فتح نزاع')),
      body: SingleChildScrollView(
        padding: const EdgeInsets.all(16),
        child: Form(
          key: _formKey,
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Card(
                child: Padding(
                  padding: const EdgeInsets.all(16),
                  child: Column(
                    crossAxisAlignment: CrossAxisAlignment.start,
                    children: [
                      Text(widget.productName, style: const TextStyle(fontSize: 18, fontWeight: FontWeight.bold)),
                      const SizedBox(height: 4),
                      Text('رقم الطلب: ${widget.orderId}'),
                      Text('البائع: ${widget.sellerName}'),
                    ],
                  ),
                ),
              ),
              const SizedBox(height: 16),
              const Text('سبب النزاع *', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              DropdownButtonFormField<String>(
                value: _selectedReason,
                decoration: InputDecoration(
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                  hintText: 'اختر السبب',
                ),
                items: _reasons.map((r) => DropdownMenuItem(value: r.key, child: Text(r.label))).toList(),
                onChanged: (v) => setState(() => _selectedReason = v),
                validator: (v) => v == null ? 'يرجى اختيار سبب' : null,
              ),
              const SizedBox(height: 16),
              const Text('شرح التفاصيل *', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              TextFormField(
                controller: _descriptionController,
                maxLines: 5,
                textAlign: TextAlign.right,
                decoration: InputDecoration(
                  hintText: 'اشرح تفاصيل المشكلة...',
                  border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                ),
                validator: (v) => v == null || v.trim().isEmpty ? 'يرجى إدخال وصف المشكلة' : null,
              ),
              const SizedBox(height: 16),
              const Text('المستندات الداعمة (اختياري)', style: TextStyle(fontWeight: FontWeight.bold)),
              const SizedBox(height: 8),
              Row(
                children: [
                  Expanded(
                    child: TextField(
                      controller: _urlController,
                      textAlign: TextAlign.right,
                      decoration: InputDecoration(
                        hintText: 'رابط صورة أو مستند...',
                        border: OutlineInputBorder(borderRadius: BorderRadius.circular(12)),
                      ),
                    ),
                  ),
                  const SizedBox(width: 8),
                  IconButton.filled(
                    onPressed: _addDocumentUrl,
                    icon: const Icon(Icons.add),
                  ),
                ],
              ),
              if (_documentUrls.isNotEmpty) ...[
                const SizedBox(height: 8),
                Wrap(
                  spacing: 8,
                  children: _documentUrls.asMap().entries.map((e) => Chip(
                    label: Text('مستند ${e.key + 1}'),
                    onDeleted: () => setState(() => _documentUrls.removeAt(e.key)),
                  )).toList(),
                ),
              ],
              const SizedBox(height: 24),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton.icon(
                  onPressed: () {
                    if (_formKey.currentState!.validate()) {
                      ScaffoldMessenger.of(context).showSnackBar(
                        const SnackBar(content: Text('تم تقديم النزاع بنجاح')),
                      );
                      Navigator.of(context).pop(true);
                    }
                  },
                  icon: const Icon(Icons.gavel),
                  label: const Text('تقديم النزاع'),
                  style: ElevatedButton.styleFrom(backgroundColor: Colors.red, foregroundColor: Colors.white, padding: const EdgeInsets.all(16)),
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
