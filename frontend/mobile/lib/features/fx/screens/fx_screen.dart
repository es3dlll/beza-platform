import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/api/api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../services/fx_service.dart';

class FxState {
  final List<Map<String, dynamic>> rates;
  final List<Map<String, dynamic>> quotes;
  final bool isLoading;
  final String? error;
  final int tabIndex;

  const FxState({
    this.rates = const [],
    this.quotes = const [],
    this.isLoading = false,
    this.error,
    this.tabIndex = 0,
  });

  FxState copyWith({
    List<Map<String, dynamic>>? rates,
    List<Map<String, dynamic>>? quotes,
    bool? isLoading,
    String? error,
    int? tabIndex,
  }) {
    return FxState(
      rates: rates ?? this.rates,
      quotes: quotes ?? this.quotes,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      tabIndex: tabIndex ?? this.tabIndex,
    );
  }
}

class FxNotifier extends StateNotifier<FxState> {
  final FxService _service;

  FxNotifier(this._service) : super(const FxState());

  Future<void> loadRates() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getRates();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(rates: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل أسعار الصرف');
    }
  }

  Future<void> loadQuotes() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getQuotes();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(quotes: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل عروض الأسعار');
    }
  }

  Future<Map<String, dynamic>?> createQuote({
    required String baseCurrency,
    required String quoteCurrency,
    required int amount,
    String? rateType,
    int? ttlSeconds,
  }) async {
    state = state.copyWith(isLoading: true);
    try {
      final result = await _service.createQuote(
        baseCurrency: baseCurrency,
        quoteCurrency: quoteCurrency,
        amount: amount,
        rateType: rateType,
        ttlSeconds: ttlSeconds,
      );
      final quote = result['data'] as Map<String, dynamic>?;
      if (quote != null) {
        state = state.copyWith(quotes: [...state.quotes, quote], isLoading: false);
      } else {
        state = state.copyWith(isLoading: false);
      }
      return quote;
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل إنشاء عرض السعر');
      return null;
    }
  }

  Future<bool> convert(String quoteId) async {
    try {
      await _service.convert(quoteId: quoteId);
      return true;
    } catch (e) {
      state = state.copyWith(error: 'فشل تنفيذ التحويل');
      return false;
    }
  }

  void setTab(int index) => state = state.copyWith(tabIndex: index);

  void clearError() => state = state.copyWith(error: null);
}

final fxProvider = StateNotifierProvider<FxNotifier, FxState>((ref) {
  final api = ApiClient();
  final service = FxService(api);
  return FxNotifier(service);
});

class FxScreen extends ConsumerStatefulWidget {
  const FxScreen({super.key});
  @override
  ConsumerState<FxScreen> createState() => _FxScreenState();
}

class _FxScreenState extends ConsumerState<FxScreen>
    with SingleTickerProviderStateMixin {
  late TabController _tabController;

  @override
  void initState() {
    super.initState();
    _tabController = TabController(length: 3, vsync: this);
    _tabController.addListener(() {
      final index = _tabController.index;
      ref.read(fxProvider.notifier).setTab(index);
      if (index == 2) {
        ref.read(fxProvider.notifier).loadQuotes();
      }
    });
    Future.microtask(() {
      ref.read(fxProvider.notifier).loadRates();
    });
  }

  @override
  void dispose() {
    _tabController.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(fxProvider);
    return Scaffold(
      appBar: AppBar(
        title: const Text('الصرافة'),
        bottom: TabBar(
          controller: _tabController,
          indicatorColor: Colors.white,
          labelColor: Colors.white,
          unselectedLabelColor: Colors.white70,
          tabs: const [
            Tab(text: 'الأسعار'),
            Tab(text: 'محول'),
            Tab(text: 'العروض'),
          ],
        ),
      ),
      body: state.error != null
          ? Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(state.error!, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.error)),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () {
                      ref.read(fxProvider.notifier).clearError();
                      ref.read(fxProvider.notifier).loadRates();
                    },
                    child: const Text('إعادة المحاولة'),
                  ),
                ],
              ),
            )
          : TabBarView(
              controller: _tabController,
              children: [
                _RatesTab(state: state, ref: ref),
                _ConverterTab(state: state, ref: ref),
                _QuotesTab(state: state, ref: ref),
              ],
            ),
    );
  }
}

class _RatesTab extends StatelessWidget {
  final FxState state;
  final WidgetRef ref;
  const _RatesTab({required this.state, required this.ref});

  @override
  Widget build(BuildContext context) {
    if (state.isLoading && state.rates.isEmpty) {
      return Center(
        child: Shimmer.fromColors(
          baseColor: AppTheme.shimmer,
          highlightColor: AppTheme.surfaceContainerLow,
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: 4,
            itemBuilder: (_, _) => Container(
              margin: const EdgeInsets.only(bottom: 8),
              height: 100,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ),
      );
    }
    if (state.rates.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
              child: const Icon(Icons.currency_exchange, size: 36, color: AppTheme.textSecondary),
            ),
            const SizedBox(height: 16),
            Text('لا توجد أسعار صرف', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: () => ref.read(fxProvider.notifier).loadRates(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: state.rates.length,
        itemBuilder: (context, index) {
          final rate = state.rates[index];
    return Container(
      margin: const EdgeInsets.only(bottom: 8),
      decoration: AppTheme.cardDecoration,
      child: Padding(
        padding: const EdgeInsets.all(16),
              child: Column(
                children: [
                  Row(
                    children: [
                      Expanded(
                        child: Text(
                          '${rate['base_currency'] ?? ''} / ${rate['quote_currency'] ?? ''}',
                          style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.bold, fontSize: 16),
                        ),
                      ),
                      if (rate['rate_type'] != null)
                        Container(
                          padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 2),
                          decoration: BoxDecoration(
                            color: AppTheme.primaryLight.withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            rate['rate_type'],
                            style: const TextStyle(fontFamily: 'Cairo', fontSize: 11, color: AppTheme.primary),
                          ),
                        ),
                    ],
                  ),
                  const SizedBox(height: 12),
                  Row(
                    children: [
                      _RateColumn(label: 'شراء', initialValue: rate['bid_rate']?.toString() ?? '-'),
                      _RateColumn(label: 'وسط', initialValue: rate['mid_rate']?.toString() ?? '-'),
                      _RateColumn(label: 'بيع', initialValue: rate['ask_rate']?.toString() ?? '-'),
                    ],
                  ),
                  if (rate['spread_pct'] != null) ...[
                    const SizedBox(height: 4),
                    Text(
                      'الفارق: ${rate['spread_pct']}%',
                      style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 11),
                    ),
                  ],
                  if (rate['source'] != null) ...[
                    const SizedBox(height: 2),
                    Text(
                      'المصدر: ${rate['source']}',
                      style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 11),
                    ),
                  ],
                ],
              ),
            ),
          );
        },
      ),
    );
  }
}

class _RateColumn extends StatelessWidget {
  final String label;
  final String initialValue;
  const _RateColumn({required this.label, required this.initialValue});

  @override
  Widget build(BuildContext context) {
    return Expanded(
      child: Column(
        children: [
          Text(label, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 12)),
          const SizedBox(height: 4),
          Text(initialValue, style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w600, fontSize: 14)),
        ],
      ),
    );
  }
}

class _ConverterTab extends ConsumerStatefulWidget {
  final FxState state;
  final WidgetRef ref;
  const _ConverterTab({required this.state, required this.ref});

  @override
  ConsumerState<_ConverterTab> createState() => _ConverterTabState();
}

class _ConverterTabState extends ConsumerState<_ConverterTab> {
  String baseCurrency = 'USD';
  String quoteCurrency = 'SYP';
  final amountCtrl = TextEditingController();
  Map<String, dynamic>? lastQuote;

  @override
  void dispose() {
    amountCtrl.dispose();
    super.dispose();
  }

  @override
  Widget build(BuildContext context) {
    final state = widget.state;
    return SingleChildScrollView(
      padding: const EdgeInsets.all(16),
      child: Container(
        decoration: AppTheme.cardDecoration,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Column(
            crossAxisAlignment: CrossAxisAlignment.start,
            children: [
              const Text('تحويل العملات', style: TextStyle(fontFamily: 'Cairo', fontSize: 18, fontWeight: FontWeight.bold)),
              const SizedBox(height: 16),
              DropdownButtonFormField<String>(
                initialValue: baseCurrency,
                decoration: const InputDecoration(
                  labelText: 'من',
                  prefixIcon: Icon(Icons.currency_exchange),
                ),
                items: const [
                  DropdownMenuItem(value: 'USD', child: Text('USD - دولار أمريكي')),
                  DropdownMenuItem(value: 'SYP', child: Text('SYP - ليرة سورية')),
                  DropdownMenuItem(value: 'EUR', child: Text('EUR - يورو')),
                  DropdownMenuItem(value: 'TRY', child: Text('TRY - ليرة تركية')),
                ],
                onChanged: (val) {
                  if (val != null) setState(() => baseCurrency = val);
                },
              ),
              const SizedBox(height: 12),
              DropdownButtonFormField<String>(
                initialValue: quoteCurrency,
                decoration: const InputDecoration(
                  labelText: 'إلى',
                  prefixIcon: Icon(Icons.currency_exchange),
                ),
                items: const [
                  DropdownMenuItem(value: 'SYP', child: Text('SYP - ليرة سورية')),
                  DropdownMenuItem(value: 'USD', child: Text('USD - دولار أمريكي')),
                  DropdownMenuItem(value: 'EUR', child: Text('EUR - يورو')),
                  DropdownMenuItem(value: 'TRY', child: Text('TRY - ليرة تركية')),
                ],
                onChanged: (val) {
                  if (val != null) setState(() => quoteCurrency = val);
                },
              ),
              const SizedBox(height: 12),
              TextField(
                controller: amountCtrl,
                decoration: const InputDecoration(
                  labelText: 'المبلغ',
                  prefixIcon: Icon(Icons.monetization_on_outlined),
                ),
                keyboardType: TextInputType.number,
              ),
              const SizedBox(height: 16),
              SizedBox(
                width: double.infinity,
                child: ElevatedButton(
                  onPressed: () async {
                    final amount = int.tryParse(amountCtrl.text) ?? 0;
                    if (amount <= 0) return;
                    final quote = await ref.read(fxProvider.notifier).createQuote(
                      baseCurrency: baseCurrency,
                      quoteCurrency: quoteCurrency,
                      amount: amount,
                    );
                    if (quote != null) {
                      setState(() => lastQuote = quote);
                    }
                  },
                  child: const Text('احصل على السعر'),
                ),
              ),
              if (state.isLoading)
                const Padding(
                  padding: EdgeInsets.only(top: 16),
                  child: Center(child: SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2))),
                ),
              if (lastQuote != null && !state.isLoading) ...[
                const SizedBox(height: 16),
                Container(
                  width: double.infinity,
                  padding: const EdgeInsets.all(16),
                  decoration: BoxDecoration(
                    color: AppTheme.primaryLight.withValues(alpha: 0.1),
                    borderRadius: BorderRadius.circular(12),
                  ),
                  child: Column(
                    children: [
                      Text(
                        '${NumberFormat('#,###').format(lastQuote!['amount_in_base'])} $baseCurrency',
                        style: const TextStyle(fontFamily: 'Cairo', fontSize: 14, color: AppTheme.textSecondary),
                      ),
                      const SizedBox(height: 8),
                      Text(
                        '= ${NumberFormat('#,###').format(lastQuote!['amount_in_quote'])} $quoteCurrency',
                        style: const TextStyle(
                          fontFamily: 'Cairo',
                          fontSize: 24,
                          fontWeight: FontWeight.bold,
                          color: AppTheme.primary,
                        ),
                      ),
                      if (lastQuote!['rate_used'] != null) ...[
                        const SizedBox(height: 4),
                        Text(
                          'سعر الصرف: ${lastQuote!['rate_used']}',
                          style: const TextStyle(fontFamily: 'Cairo', fontSize: 12, color: AppTheme.textSecondary),
                        ),
                      ],
                      if (lastQuote!['status'] == 'pending') ...[
                        const SizedBox(height: 12),
                        SizedBox(
                          width: double.infinity,
                          child: ElevatedButton(
                            onPressed: () async {
                              final success = await ref.read(fxProvider.notifier).convert(lastQuote!['id']);
                              if (success && context.mounted) {
                                ScaffoldMessenger.of(context).showSnackBar(
                                  const SnackBar(content: Text('تم تنفيذ التحويل بنجاح')),
                                );
                              }
                            },
                            child: const Text('تنفيذ التحويل'),
                          ),
                        ),
                      ],
                    ],
                  ),
                ),
              ],
            ],
          ),
        ),
      ),
    );
  }
}

class _QuotesTab extends StatelessWidget {
  final FxState state;
  final WidgetRef ref;
  const _QuotesTab({required this.state, required this.ref});

  @override
  Widget build(BuildContext context) {
    if (state.isLoading && state.quotes.isEmpty) {
      return Center(
        child: Shimmer.fromColors(
          baseColor: AppTheme.shimmer,
          highlightColor: AppTheme.surfaceContainerLow,
          child: ListView.builder(
            padding: const EdgeInsets.all(16),
            itemCount: 4,
            itemBuilder: (_, _) => Container(
              margin: const EdgeInsets.only(bottom: 8),
              height: 80,
              decoration: BoxDecoration(
                color: Colors.white,
                borderRadius: BorderRadius.circular(12),
              ),
            ),
          ),
        ),
      );
    }
    if (state.quotes.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(16),
              decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
              child: const Icon(Icons.receipt_long_outlined, size: 36, color: AppTheme.textSecondary),
            ),
            const SizedBox(height: 16),
            Text('لا توجد عروض أسعار', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
          ],
        ),
      );
    }
    return RefreshIndicator(
      onRefresh: () => ref.read(fxProvider.notifier).loadQuotes(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: state.quotes.length,
        itemBuilder: (context, index) {
          final q = state.quotes[index];
          return Container(
            margin: const EdgeInsets.only(bottom: 8),
            decoration: AppTheme.cardDecoration,
            child: ListTile(
              leading: CircleAvatar(
                backgroundColor: AppTheme.primaryLight.withValues(alpha: 0.2),
                child: Text(
                  '${q['base_currency'] ?? ''}/${q['quote_currency'] ?? ''}',
                  style: const TextStyle(fontFamily: 'Cairo', fontSize: 10, fontWeight: FontWeight.bold, color: AppTheme.primary),
                ),
              ),
              title: Text('${NumberFormat('#,###').format(q['amount_in_base'])} ${q['base_currency']}'),
              subtitle: Text('${NumberFormat('#,###').format(q['amount_in_quote'])} ${q['quote_currency']}'),
              trailing: Container(
                padding: const EdgeInsets.symmetric(horizontal: 8, vertical: 4),
                decoration: BoxDecoration(
                    color: q['status'] == 'completed'
                        ? AppTheme.successLight
                        : AppTheme.warningLight,
                  borderRadius: BorderRadius.circular(8),
                ),
                child: Text(
                  q['status'] == 'completed' ? 'منفذ' : 'معلق',
                  style: TextStyle(
                    fontFamily: 'Cairo',
                    fontSize: 12,
                    color: q['status'] == 'completed' ? AppTheme.success : AppTheme.warning,
                    fontWeight: FontWeight.w600,
                  ),
                ),
              ),
            ),
          );
        },
      ),
    );
  }
}
