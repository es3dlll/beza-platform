import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/api/api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../services/transaction_service.dart';

String _arabicDigits(String s) {
  const a = '٠١٢٣٤٥٦٧٨٩';
  return s.split('').map((c) {
    final i = '0123456789'.indexOf(c);
    return i >= 0 ? a[i] : c;
  }).join();
}

String _formatAmount(int amount) {
  final value = (amount / 100).floor();
  return _arabicDigits(NumberFormat('#,##0').format(value));
}

String _dateGroup(DateTime date) {
  final now = DateTime.now();
  final today = DateTime(now.year, now.month, now.day);
  final d = DateTime(date.year, date.month, date.day);
  final diff = today.difference(d).inDays;
  if (diff == 0) return 'اليوم';
  if (diff == 1) return 'أمس';
  if (diff <= 7) return 'هذا الأسبوع';
  if (diff <= 14) return 'الأسبوع الماضي';
  if (diff <= 30) return 'هذا الشهر';
  return _arabicDigits(DateFormat('yyyy/MM').format(date));
}

class TransactionsScreen extends ConsumerStatefulWidget {
  const TransactionsScreen({super.key});

  @override
  ConsumerState<TransactionsScreen> createState() =>
      _TransactionsScreenState();
}

class _TransactionsScreenState extends ConsumerState<TransactionsScreen> {
  final _service = TransactionService(ApiClient());

  List<Transaction> _transactions = [];
  bool _isLoading = true;
  String? _error;
  String? _typeFilter;
  bool _sortNewest = true;

  @override
  void initState() {
    super.initState();
    _fetchData();
  }

  Future<void> _fetchData() async {
    setState(() {
      _isLoading = true;
      _error = null;
    });
    try {
      final wallets = await _service.getWallets();
      if (wallets.isEmpty) {
        setState(() {
          _transactions = [];
          _isLoading = false;
        });
        return;
      }
      final walletId = wallets.first['id'] as String;
      final result = await _service.getTransactions(
        walletId,
        type: _typeFilter,
      );
      final list = (result['data'] as List?) ?? [];
      final txs = list
          .map((e) => Transaction.fromJson(e as Map<String, dynamic>))
          .toList();
      txs.sort((a, b) => _sortNewest
          ? b.createdAt.compareTo(a.createdAt)
          : a.createdAt.compareTo(b.createdAt));
      setState(() {
        _transactions = txs;
        _isLoading = false;
      });
    } catch (e) {
      setState(() {
        _error = e.toString();
        _isLoading = false;
      });
    }
  }

  Map<String, List<Transaction>> _grouped() {
    final map = <String, List<Transaction>>{};
    for (final t in _transactions) {
      final key = _dateGroup(t.createdAt);
      map.putIfAbsent(key, () => []).add(t);
    }
    return map;
  }

  void _showFilterSheet() {
    final filters = [
      {'label': 'الكل', 'value': null},
      {'label': 'إيداع', 'value': 'deposit'},
      {'label': 'سحب', 'value': 'withdrawal'},
      {'label': 'تحويل', 'value': 'transfer'},
      {'label': 'دفع', 'value': 'payment'},
      {'label': 'رسوم', 'value': 'fee'},
      {'label': 'استرداد', 'value': 'refund'},
    ];
    showModalBottomSheet(
      context: context,
      shape: const RoundedRectangleBorder(
        borderRadius: BorderRadius.vertical(top: Radius.circular(20)),
      ),
      builder: (ctx) => Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text(
              'تصفية حسب النوع',
              style: TextStyle(fontSize: 18, fontWeight: FontWeight.bold),
            ),
            const SizedBox(height: 12),
            RadioGroup<String?>(
              groupValue: _typeFilter,
              onChanged: (v) {
                setState(() => _typeFilter = v);
                _fetchData();
                Navigator.pop(ctx);
              },
              child: Column(
                children: filters.map((f) => RadioListTile<String?>(
                  title: Text(f['label'] as String),
                  value: f['value'],
                  activeColor: AppTheme.primary,
                )).toList(),
              ),
            ),
            const SizedBox(height: 8),
          ],
        ),
      ),
    );
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(
        title: const Text('الحركات'),
        actions: [
          IconButton(
            icon: Icon(_sortNewest ? Icons.arrow_downward : Icons.arrow_upward),
            tooltip: 'ترتيب',
            onPressed: () {
              setState(() => _sortNewest = !_sortNewest);
              _fetchData();
            },
          ),
          IconButton(
            icon: const Icon(Icons.filter_list),
            onPressed: _showFilterSheet,
          ),
        ],
      ),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading) return _buildShimmer();
    if (_error != null) return _buildError();
    if (_transactions.isEmpty) return _buildEmpty();
    return _buildList();
  }

  Widget _buildShimmer() {
    return Shimmer.fromColors(
      baseColor: Colors.grey[300]!,
      highlightColor: Colors.grey[100]!,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: 8,
        itemBuilder: (_, index) => Card(
          margin: const EdgeInsets.only(bottom: 8),
          child: ListTile(
            leading: const CircleAvatar(child: Icon(Icons.circle)),
            title: Container(
              height: 14,
              width: 120,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(4),
              ),
            ),
            subtitle: Container(
              height: 12,
              width: 80,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(4),
              ),
            ),
            trailing: Container(
              height: 14,
              width: 60,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(4),
              ),
            ),
          ),
        ),
      ),
    );
  }

  Widget _buildError() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          const Icon(Icons.error_outline, size: 64, color: AppTheme.error),
          const SizedBox(height: 16),
          Text(
            _error ?? 'حدث خطأ أثناء تحميل الحركات',
            style: TextStyle(color: AppTheme.textSecondary),
          ),
          const SizedBox(height: 16),
          FilledButton.icon(
            onPressed: _fetchData,
            icon: const Icon(Icons.refresh),
            label: const Text('إعادة المحاولة'),
          ),
        ],
      ),
    );
  }

  Widget _buildEmpty() {
    return Center(
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Icon(Icons.receipt_long, size: 80, color: Colors.grey[300]),
          const SizedBox(height: 16),
          Text(
            'لا توجد حركات حتى الآن',
            style: TextStyle(
              fontSize: 16,
              color: AppTheme.textSecondary,
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildList() {
    final grouped = _grouped();
    final keys = grouped.keys.toList();

    return RefreshIndicator(
      onRefresh: _fetchData,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: keys.length,
        itemBuilder: (context, sectionIndex) {
          final key = keys[sectionIndex];
          final items = grouped[key]!;
          return Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              Padding(
                padding: const EdgeInsets.symmetric(vertical: 8, horizontal: 4),
                child: Text(
                  key,
                  style: const TextStyle(
                    fontSize: 14,
                    fontWeight: FontWeight.bold,
                    color: AppTheme.textSecondary,
                  ),
                ),
              ),
              ...items.map((tx) => _TransactionTile(tx: tx)),
            ],
          );
        },
      ),
    );
  }
}

class _TransactionTile extends StatelessWidget {
  final Transaction tx;
  const _TransactionTile({required this.tx});

  IconData _icon() {
    switch (tx.type) {
      case 'deposit':
        return Icons.arrow_downward;
      case 'withdrawal':
        return Icons.arrow_upward;
      case 'transfer':
        return Icons.swap_horiz;
      case 'payment':
        return Icons.payment;
      case 'fee':
        return Icons.remove_circle_outline;
      case 'refund':
        return Icons.undo;
      default:
        return Icons.receipt_long;
    }
  }

  Color _iconColor() {
    switch (tx.type) {
      case 'deposit':
      case 'refund':
        return Colors.green;
      case 'withdrawal':
        return Colors.red;
      case 'transfer':
        return Colors.blue;
      case 'payment':
        return Colors.orange;
      case 'fee':
        return Colors.grey;
      default:
        return AppTheme.textSecondary;
    }
  }

  String _label() {
    switch (tx.type) {
      case 'deposit':
        return 'إيداع';
      case 'withdrawal':
        return 'سحب';
      case 'transfer':
        return 'تحويل';
      case 'payment':
        return 'دفع';
      case 'fee':
        return 'رسوم';
      case 'refund':
        return 'استرداد';
      default:
        return tx.type;
    }
  }

  Color _statusColor() {
    switch (tx.status) {
      case 'completed':
        return Colors.green;
      case 'pending':
        return Colors.orange;
      case 'failed':
        return Colors.red;
      case 'reversed':
        return Colors.grey;
      default:
        return Colors.grey;
    }
  }

  String _statusLabel() {
    switch (tx.status) {
      case 'completed':
        return 'مكتملة';
      case 'pending':
        return 'قيد الانتظار';
      case 'failed':
        return 'فاشلة';
      case 'reversed':
        return 'ملغية';
      default:
        return tx.status;
    }
  }

  @override
  Widget build(BuildContext context) {
    final isCredit = tx.type == 'deposit' || tx.type == 'refund';
    return Card(
      margin: const EdgeInsets.only(bottom: 8),
      child: ListTile(
        leading: CircleAvatar(
          backgroundColor: _iconColor().withValues(alpha: 0.1),
          child: Icon(_icon(), color: _iconColor(), size: 20),
        ),
        title: Row(
          children: [
            Text(
              tx.description ?? _label(),
              style: const TextStyle(fontWeight: FontWeight.w500),
            ),
            const SizedBox(width: 8),
            Container(
              padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
              decoration: BoxDecoration(
                color: _statusColor().withValues(alpha: 0.15),
                borderRadius: BorderRadius.circular(8),
              ),
              child: Text(
                _statusLabel(),
                style: TextStyle(
                  fontSize: 10,
                  color: _statusColor(),
                  fontWeight: FontWeight.w600,
                ),
              ),
            ),
          ],
        ),
        subtitle: Text(
          _arabicDigits(DateFormat('yyyy/MM/dd HH:mm').format(tx.createdAt)),
        ),
        trailing: Text(
          '${isCredit ? '+ ' : '- '}${_formatAmount(tx.amount)} ل.س',
          style: TextStyle(
            color: isCredit ? Colors.green : Colors.red,
            fontWeight: FontWeight.w600,
          ),
        ),
      ),
    );
  }
}
