import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:go_router/go_router.dart';
import 'package:intl/intl.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/api/api_client.dart';
import '../../../core/api/api_endpoints.dart';
import '../../../core/theme/app_theme.dart';

class WalletScreen extends ConsumerStatefulWidget {
  const WalletScreen({super.key});

  @override
  ConsumerState<WalletScreen> createState() => _WalletScreenState();
}

class _WalletScreenState extends ConsumerState<WalletScreen> {
  final ApiClient _client = ApiClient();

  bool _isLoading = true;
  String? _error;
  Map<String, dynamic>? _wallet;
  List<Map<String, dynamic>> _transactions = [];
  bool _isBalanceVisible = true;

  @override
  void initState() {
    super.initState();
    _loadData();
  }

  String _arabicDigits(String input) {
    const arabic = ['٠', '١', '٢', '٣', '٤', '٥', '٦', '٧', '٨', '٩'];
    return String.fromCharCodes(input.runes.map((r) {
      if (r >= 0x30 && r <= 0x39) return arabic[r - 0x30].codeUnitAt(0);
      return r;
    }));
  }

  String _formatBalance(int amount) {
    return _arabicDigits(NumberFormat('#,###').format(amount));
  }

  String _formatDate(String dateStr) {
    try {
      final dt = DateTime.parse(dateStr);
      return _arabicDigits(DateFormat('yyyy/MM/dd').format(dt));
    } catch (_) {
      return dateStr;
    }
  }

  Future<void> _loadData() async {
    if (!mounted) return;
    setState(() { _isLoading = true; _error = null; });
    try {
      final response = await _client.get(ApiEndpoints.wallets);
      final data = response.data as Map<String, dynamic>;
      final list = (data['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      if (list.isEmpty) {
        if (mounted) setState(() { _wallet = null; _transactions = []; _isLoading = false; });
        return;
      }
      _wallet = list.first;
      await _fetchTransactions();
      if (mounted) setState(() => _isLoading = false);
    } catch (e) {
      if (mounted) setState(() { _isLoading = false; _error = e.toString(); });
    }
  }

  Future<void> _fetchTransactions() async {
    if (_wallet == null) return;
    try {
      final response = await _client.get(ApiEndpoints.walletTransactions(_wallet!['id'] as String), queryParameters: {'page': 1, 'per_page': 10});
      final data = response.data as Map<String, dynamic>;
      _transactions = (data['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
    } catch (_) { _transactions = []; }
  }

  Future<void> _onRefresh() async { setState(() => _error = null); await _loadData(); }

  Future<void> _createWallet() async {
    if (!mounted) return;
    setState(() { _isLoading = true; _error = null; });
    try {
      final response = await _client.post(ApiEndpoints.wallets, data: {'currency': 'SYP'});
      final data = response.data as Map<String, dynamic>;
      _wallet = data['data'] as Map<String, dynamic>?;
      if (_wallet != null) await _fetchTransactions();
      if (mounted) setState(() => _isLoading = false);
    } catch (e) {
      if (mounted) setState(() { _isLoading = false; _error = 'فشل إنشاء المحفظة'; });
    }
  }

  void _showDepositDialog() {
    showModalBottomSheet(
      context: context,
      builder: (ctx) => _AmountSheet(
        title: 'إيداع',
        icon: Icons.add_circle,
        hint: 'أدخل المبلغ',
        buttonLabel: 'تأكيد الإيداع',
        color: AppTheme.success,
        isLoading: _isLoading,
        onSubmit: (amount) { Navigator.pop(ctx); _performDeposit(amount); },
      ),
    );
  }

  Future<void> _performDeposit(int amount) async {
    if (_wallet == null || !mounted) return;
    setState(() => _isLoading = true);
    try {
      await _client.post(ApiEndpoints.walletDeposit(_wallet!['id'] as String), data: {'amount': amount, 'currency': 'SYP', 'description': 'إيداع في المحفظة'});
      await _loadData();
    } catch (e) {
      if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('فشل الإيداع'))); setState(() => _isLoading = false); }
    }
  }

  void _showWithdrawDialog() {
    showModalBottomSheet(
      context: context,
      builder: (ctx) => _AmountSheet(
        title: 'سحب',
        icon: Icons.remove_circle,
        hint: 'أدخل المبلغ',
        buttonLabel: 'تأكيد السحب',
        color: AppTheme.error,
        isLoading: _isLoading,
        onSubmit: (amount) { Navigator.pop(ctx); _performWithdraw(amount); },
      ),
    );
  }

  Future<void> _performWithdraw(int amount) async {
    if (_wallet == null || !mounted) return;
    setState(() => _isLoading = true);
    try {
      await _client.post(ApiEndpoints.walletWithdraw(_wallet!['id'] as String), data: {'amount': amount, 'currency': 'SYP', 'description': 'سحب من المحفظة'});
      await _loadData();
    } catch (e) {
      if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('فشل السحب'))); setState(() => _isLoading = false); }
    }
  }

  void _showTransferDialog() {
    final walletCtrl = TextEditingController();
    final amountCtrl = TextEditingController();
    showModalBottomSheet(
      context: context,
      isScrollControlled: true,
      builder: (ctx) => Padding(
        padding: EdgeInsets.only(bottom: MediaQuery.of(ctx).viewInsets.bottom),
        child: _TransferSheet(
          walletCtrl: walletCtrl,
          amountCtrl: amountCtrl,
          isLoading: _isLoading,
          onSubmit: () {
            final amount = int.tryParse(amountCtrl.text);
            final toWallet = walletCtrl.text.trim();
            if (toWallet.isEmpty || amount == null || amount <= 0) return;
            Navigator.pop(ctx);
            _performTransfer(toWallet, amount);
          },
        ),
      ),
    );
  }

  Future<void> _performTransfer(String toWalletId, int amount) async {
    if (_wallet == null || !mounted) return;
    setState(() => _isLoading = true);
    try {
      await _client.post(ApiEndpoints.walletTransfer(_wallet!['id'] as String), data: {'recipient_wallet_id': toWalletId, 'amount': amount, 'currency': 'SYP', 'description': 'تحويل'});
      await _loadData();
    } catch (e) {
      if (mounted) { ScaffoldMessenger.of(context).showSnackBar(const SnackBar(content: Text('فشل التحويل'))); setState(() => _isLoading = false); }
    }
  }

  @override
  Widget build(BuildContext context) {
    return Scaffold(
      appBar: AppBar(title: const Text('المحفظة')),
      body: _buildBody(),
    );
  }

  Widget _buildBody() {
    if (_isLoading && _wallet == null) return _buildShimmer();
    if (_error != null && _wallet == null) return _buildError();
    if (_wallet == null) return _buildNoWallet();
    return RefreshIndicator(
      onRefresh: _onRefresh,
      child: SingleChildScrollView(
        physics: const AlwaysScrollableScrollPhysics(),
        padding: const EdgeInsets.fromLTRB(16, 4, 16, 24),
        child: Column(
          children: [
            _buildBalanceCard(),
            const SizedBox(height: 22),
            _buildQuickActions(),
            const SizedBox(height: 28),
            _buildTransactionsHeader(),
            const SizedBox(height: 14),
            if (_transactions.isEmpty) _buildEmptyTransactions()
            else ..._transactions.map(_buildTransactionItem),
          ],
        ),
      ),
    );
  }

  Widget _buildShimmer() {
    return Shimmer.fromColors(
      baseColor: Colors.grey[300]!,
      highlightColor: Colors.grey[100]!,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          children: [
            Container(width: double.infinity, height: 190, decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(22))),
            const SizedBox(height: 20),
            Row(children: List.generate(3, (_) => Expanded(child: Container(height: 88, margin: const EdgeInsets.symmetric(horizontal: 4), decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18))))),
            ),
          ],
        ),
      ),
    );
  }

  Widget _buildError() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(color: AppTheme.errorLight, shape: BoxShape.circle),
              child: const Icon(Icons.error_outline, size: 40, color: AppTheme.error),
            ),
            const SizedBox(height: 20),
            Text(_error!, style: const TextStyle(fontFamily: 'Cairo', fontSize: 16, color: AppTheme.textSecondary), textAlign: TextAlign.center),
            const SizedBox(height: 24),
            ElevatedButton.icon(onPressed: _onRefresh, icon: const Icon(Icons.refresh, size: 18), label: const Text('إعادة المحاولة')),
          ],
        ),
      ),
    );
  }

  Widget _buildNoWallet() {
    return Center(
      child: Padding(
        padding: const EdgeInsets.all(32),
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(24),
              decoration: BoxDecoration(color: AppTheme.primary.withValues(alpha: 0.08), shape: BoxShape.circle),
              child: Icon(Icons.account_balance_wallet_outlined, size: 56, color: AppTheme.primary.withValues(alpha: 0.5)),
            ),
            const SizedBox(height: 20),
            const Text('لا توجد محفظة مالية بعد', style: TextStyle(fontFamily: 'Cairo', fontSize: 20, fontWeight: FontWeight.bold, color: AppTheme.textPrimary)),
            const SizedBox(height: 10),
            const Text('قم بإنشاء محفظتك للبدء في استخدام الخدمات المالية', style: TextStyle(fontFamily: 'Cairo', fontSize: 14, color: AppTheme.textSecondary), textAlign: TextAlign.center),
            const SizedBox(height: 32),
            ElevatedButton.icon(onPressed: _createWallet, icon: const Icon(Icons.add, size: 18), label: const Text('إنشاء محفظة')),
          ],
        ),
      ),
    );
  }

  Widget _buildBalanceCard() {
    final balance = (_wallet!['balance'] as int?) ?? 0;
    final available = (_wallet!['available_balance'] as int?) ?? balance;
    return Container(
      width: double.infinity,
      padding: const EdgeInsets.all(22),
      decoration: BoxDecoration(
        gradient: AppTheme.cardGradient,
        borderRadius: BorderRadius.circular(22),
        boxShadow: [BoxShadow(color: AppTheme.primary.withValues(alpha: 0.3), blurRadius: 24, offset: const Offset(0, 10))],
      ),
      child: Column(
        crossAxisAlignment: CrossAxisAlignment.start,
        children: [
          Row(
            children: [
              GestureDetector(
                onTap: () => setState(() => _isBalanceVisible = !_isBalanceVisible),
                child: Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(20)),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Icon(_isBalanceVisible ? Icons.visibility : Icons.visibility_off, color: Colors.white70, size: 16),
                      const SizedBox(width: 6),
                      Text(_isBalanceVisible ? 'إخفاء' : 'إظهار', style: const TextStyle(fontFamily: 'Cairo', color: Colors.white70, fontSize: 11)),
                    ],
                  ),
                ),
              ),
              const Spacer(),
              if (_wallet!['currency'] != null)
                Container(
                  padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 6),
                  decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.15), borderRadius: BorderRadius.circular(20)),
                  child: Row(
                    mainAxisSize: MainAxisSize.min,
                    children: [
                      Text(_wallet!['currency'] as String, style: const TextStyle(fontFamily: 'Cairo', color: Colors.white70, fontSize: 11)),
                    ],
                  ),
                ),
            ],
          ),
          const SizedBox(height: 20),
          const Text('الرصيد الكلي', style: TextStyle(fontFamily: 'Cairo', color: Colors.white60, fontSize: 13)),
          const SizedBox(height: 4),
          Text(
            _isBalanceVisible ? _formatBalance(balance) : '••••••••',
            style: const TextStyle(fontFamily: 'Cairo', color: Colors.white, fontSize: 36, fontWeight: FontWeight.bold, height: 1.1),
          ),
          const SizedBox(height: 10),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 8),
            decoration: BoxDecoration(color: Colors.white.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)),
            child: Text(
              'الرصيد المتاح: ${_isBalanceVisible ? '${_formatBalance(available)} ل.س' : '••••••••'}',
              style: const TextStyle(fontFamily: 'Cairo', color: Colors.white70, fontSize: 12),
            ),
          ),
        ],
      ),
    );
  }

  Widget _buildQuickActions() {
    return Row(
      children: [
        _buildActionChip(icon: Icons.add_circle_outline, label: 'إيداع', color: AppTheme.success, onTap: _showDepositDialog),
        const SizedBox(width: 12),
        _buildActionChip(icon: Icons.remove_circle_outline, label: 'سحب', color: AppTheme.error, onTap: _showWithdrawDialog),
        const SizedBox(width: 12),
        _buildActionChip(icon: Icons.swap_horiz, label: 'تحويل', color: AppTheme.info, onTap: _showTransferDialog),
      ],
    );
  }

  Widget _buildActionChip({required IconData icon, required String label, required Color color, required VoidCallback onTap}) {
    return Expanded(
      child: InkWell(
        onTap: onTap,
        borderRadius: BorderRadius.circular(18),
        child: Container(
          padding: const EdgeInsets.symmetric(vertical: 18),
          decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(18), boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.04), blurRadius: 10, offset: const Offset(0, 3))]),
          child: Column(
            children: [
              Container(
                padding: const EdgeInsets.all(12),
                decoration: BoxDecoration(color: color.withValues(alpha: 0.1), shape: BoxShape.circle),
                child: Icon(icon, color: color, size: 24),
              ),
              const SizedBox(height: 8),
              Text(label, style: const TextStyle(fontFamily: 'Cairo', fontSize: 12, fontWeight: FontWeight.w600, color: AppTheme.textPrimary)),
            ],
          ),
        ),
      ),
    );
  }

  Widget _buildTransactionsHeader() {
    return Row(
      mainAxisAlignment: MainAxisAlignment.spaceBetween,
      children: [
        Row(
          children: [
            Container(width: 4, height: 20, decoration: BoxDecoration(gradient: AppTheme.primaryGradient, borderRadius: BorderRadius.circular(2))),
            const SizedBox(width: 10),
            const Text('آخر الحركات', style: TextStyle(fontFamily: 'Cairo', fontSize: 18, fontWeight: FontWeight.bold, color: AppTheme.textPrimary)),
          ],
        ),
        TextButton(
          onPressed: () => context.push('/transactions'),
          child: const Text('عرض الكل', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.primary, fontWeight: FontWeight.w500)),
        ),
      ],
    );
  }

  Widget _buildEmptyTransactions() {
    return Padding(
      padding: const EdgeInsets.symmetric(vertical: 40),
      child: Center(
        child: Column(
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
              child: Icon(Icons.receipt_long_outlined, size: 36, color: AppTheme.textSecondary.withValues(alpha: 0.5)),
            ),
            const SizedBox(height: 14),
            const Text('لا توجد حركات بعد', style: TextStyle(fontFamily: 'Cairo', fontSize: 15, color: AppTheme.textSecondary)),
          ],
        ),
      ),
    );
  }

  Widget _buildTransactionItem(Map<String, dynamic> tx) {
    final type = tx['type'] as String? ?? '';
    final config = _txTypeConfig[type] ?? const _TxConfig(Icons.receipt, '', Colors.grey);
    final amount = tx['amount'] as int? ?? 0;
    final isCredit = type == 'deposit' || type == 'refund';
    final dateStr = tx['created_at'] as String? ?? '';
    final status = tx['status'] as String? ?? 'completed';
    final description = tx['description'] as String? ?? config.label;
    final statusColor = _statusColors[status] ?? Colors.grey;
    final statusLabel = switch (status) { 'completed' => 'مكتمل', 'pending' => 'معلق', 'failed' => 'فشل', 'cancelled' => 'ملغي', _ => status };

    return Container(
      margin: const EdgeInsets.only(bottom: 10),
      padding: const EdgeInsets.symmetric(horizontal: 14, vertical: 12),
      decoration: BoxDecoration(color: Colors.white, borderRadius: BorderRadius.circular(16), boxShadow: [BoxShadow(color: Colors.black.withValues(alpha: 0.03), blurRadius: 8, offset: const Offset(0, 2))]),
      child: Row(
        children: [
          Container(
            padding: const EdgeInsets.all(10),
            decoration: BoxDecoration(color: config.color.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(12)),
            child: Icon(config.icon, color: config.color, size: 20),
          ),
          const SizedBox(width: 12),
          Expanded(
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              children: [
                Row(
                  children: [
                    Flexible(child: Text(description, style: const TextStyle(fontFamily: 'Cairo', fontSize: 14, fontWeight: FontWeight.w500, color: AppTheme.textPrimary))),
                    const SizedBox(width: 6),
                    Container(
                      padding: const EdgeInsets.symmetric(horizontal: 6, vertical: 2),
                      decoration: BoxDecoration(color: statusColor.withValues(alpha: 0.1), borderRadius: BorderRadius.circular(6)),
                      child: Text(statusLabel, style: TextStyle(fontFamily: 'Cairo', fontSize: 10, color: statusColor, fontWeight: FontWeight.w500)),
                    ),
                  ],
                ),
                const SizedBox(height: 4),
                Text(_formatDate(dateStr), style: const TextStyle(fontFamily: 'Cairo', fontSize: 12, color: AppTheme.textTertiary)),
              ],
            ),
          ),
          const SizedBox(width: 12),
          Text(
            '${isCredit ? '+' : '-'} ${_formatBalance(amount)}',
            style: TextStyle(fontFamily: 'Cairo', fontSize: 15, fontWeight: FontWeight.bold, color: isCredit ? AppTheme.success : AppTheme.error),
          ),
        ],
      ),
    );
  }

  static const Map<String, _TxConfig> _txTypeConfig = {
    'deposit': _TxConfig(Icons.arrow_circle_up, 'إيداع', Colors.green),
    'withdraw': _TxConfig(Icons.arrow_circle_down, 'سحب', Colors.red),
    'withdrawal': _TxConfig(Icons.arrow_circle_down, 'سحب', Colors.red),
    'transfer': _TxConfig(Icons.swap_horiz, 'تحويل', Color(0xFF3B82F6)),
    'payment': _TxConfig(Icons.receipt_long, 'دفع', Color(0xFFF59E0B)),
    'bill': _TxConfig(Icons.receipt_long, 'فاتورة', Color(0xFFF59E0B)),
    'refund': _TxConfig(Icons.replay, 'استرداد', Color(0xFF14B8A6)),
  };

  static const Map<String, Color> _statusColors = {
    'completed': Colors.green, 'pending': Color(0xFFF59E0B), 'failed': Colors.red, 'cancelled': Colors.grey,
  };
}

class _TxConfig {
  final IconData icon; final String label; final Color color;
  const _TxConfig(this.icon, this.label, this.color);
}

class _AmountSheet extends StatelessWidget {
  final String title; final IconData icon; final String hint; final String buttonLabel; final Color color; final bool isLoading; final void Function(int) onSubmit;

  const _AmountSheet({required this.title, required this.icon, required this.hint, required this.buttonLabel, required this.color, required this.isLoading, required this.onSubmit});

  @override
  Widget build(BuildContext context) {
    final ctrl = TextEditingController();
    return Padding(
      padding: const EdgeInsets.fromLTRB(24, 16, 24, 32),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(width: 40, height: 4, decoration: BoxDecoration(color: AppTheme.divider, borderRadius: BorderRadius.circular(2))),
          const SizedBox(height: 24),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(color: color.withValues(alpha: 0.1), shape: BoxShape.circle),
            child: Icon(icon, color: color, size: 32),
          ),
          const SizedBox(height: 16),
          Text(title, style: const TextStyle(fontFamily: 'Cairo', fontSize: 22, fontWeight: FontWeight.bold, color: AppTheme.textPrimary)),
          const SizedBox(height: 20),
          TextField(
            controller: ctrl,
            keyboardType: TextInputType.number,
            textAlign: TextAlign.center,
            decoration: InputDecoration(
              hintText: hint,
              prefixText: 'ل.س ',
              prefixStyle: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary),
            ),
          ),
          const SizedBox(height: 24),
          ElevatedButton(
            onPressed: () {
              final amount = int.tryParse(ctrl.text);
              if (amount == null || amount <= 0) return;
              onSubmit(amount);
            },
            child: Text(buttonLabel),
          ),
        ],
      ),
    );
  }
}

class _TransferSheet extends StatelessWidget {
  final TextEditingController walletCtrl; final TextEditingController amountCtrl; final bool isLoading; final VoidCallback onSubmit;

  const _TransferSheet({required this.walletCtrl, required this.amountCtrl, required this.isLoading, required this.onSubmit});

  @override
  Widget build(BuildContext context) {
    return Padding(
      padding: const EdgeInsets.fromLTRB(24, 16, 24, 32),
      child: Column(
        mainAxisSize: MainAxisSize.min,
        children: [
          Container(width: 40, height: 4, decoration: BoxDecoration(color: AppTheme.divider, borderRadius: BorderRadius.circular(2))),
          const SizedBox(height: 24),
          Container(
            padding: const EdgeInsets.all(16),
            decoration: BoxDecoration(color: AppTheme.info.withValues(alpha: 0.1), shape: BoxShape.circle),
            child: const Icon(Icons.swap_horiz, color: AppTheme.info, size: 32),
          ),
          const SizedBox(height: 16),
          const Text('تحويل', style: TextStyle(fontFamily: 'Cairo', fontSize: 22, fontWeight: FontWeight.bold, color: AppTheme.textPrimary)),
          const SizedBox(height: 20),
          TextField(
            controller: walletCtrl,
            decoration: const InputDecoration(labelText: 'رقم المحفظة المستهدفة', hintText: 'أدخل رقم المحفظة'),
          ),
          const SizedBox(height: 14),
          TextField(
            controller: amountCtrl,
            keyboardType: TextInputType.number,
            decoration: const InputDecoration(labelText: 'المبلغ (ل.س)', hintText: 'أدخل المبلغ', prefixText: 'ل.س '),
          ),
          const SizedBox(height: 24),
          ElevatedButton(onPressed: onSubmit, child: const Text('تأكيد التحويل')),
        ],
      ),
    );
  }
}
