import 'dart:ui' as ui show TextDirection;
import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:intl/intl.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/api/api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../services/loyalty_service.dart';

class LoyaltyState {
  final Map<String, dynamic>? pointsData;
  final List<Map<String, dynamic>> history;
  final List<Map<String, dynamic>> rewards;
  final List<Map<String, dynamic>> tiers;
  final int? cashbackAmount;
  final int? transactionAmount;
  final String? merchantCategory;
  final bool isLoading;
  final String? error;

  const LoyaltyState({
    this.pointsData,
    this.history = const [],
    this.rewards = const [],
    this.tiers = const [],
    this.cashbackAmount,
    this.transactionAmount,
    this.merchantCategory,
    this.isLoading = false,
    this.error,
  });

  LoyaltyState copyWith({
    Map<String, dynamic>? pointsData,
    List<Map<String, dynamic>>? history,
    List<Map<String, dynamic>>? rewards,
    List<Map<String, dynamic>>? tiers,
    int? cashbackAmount,
    int? transactionAmount,
    String? merchantCategory,
    bool? isLoading,
    String? error,
  }) {
    return LoyaltyState(
      pointsData: pointsData ?? this.pointsData,
      history: history ?? this.history,
      rewards: rewards ?? this.rewards,
      tiers: tiers ?? this.tiers,
      cashbackAmount: cashbackAmount,
      transactionAmount: transactionAmount ?? this.transactionAmount,
      merchantCategory: merchantCategory ?? this.merchantCategory,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class LoyaltyNotifier extends StateNotifier<LoyaltyState> {
  final LoyaltyService _service;

  LoyaltyNotifier(this._service) : super(const LoyaltyState());

  Future<void> loadAll() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final pointsResult = await _service.getPoints();
      final historyResult = await _service.getPointsHistory();
      final rewardsResult = await _service.getRewards();
      final tiersResult = await _service.getTiers();

      state = state.copyWith(
        pointsData: pointsResult['data'] as Map<String, dynamic>?,
        history: (historyResult['data'] as List?)?.cast<Map<String, dynamic>>() ?? [],
        rewards: (rewardsResult['data'] as List?)?.cast<Map<String, dynamic>>() ?? [],
        tiers: (tiersResult['data'] as List?)?.cast<Map<String, dynamic>>() ?? [],
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل بيانات الولاء');
    }
  }

  Future<bool> redeemReward(String rewardId) async {
    try {
      await _service.redeem(rewardId: rewardId);
      await loadAll();
      return true;
    } catch (e) {
      state = state.copyWith(error: 'فشل استبدال النقاط');
      return false;
    }
  }

  Future<void> calculateCashback({
    required int transactionAmount,
    String? merchantCategory,
  }) async {
    state = state.copyWith(
      isLoading: true,
      transactionAmount: transactionAmount,
      merchantCategory: merchantCategory,
    );
    try {
      final result = await _service.calculateCashback(
        transactionAmount: transactionAmount,
        merchantCategory: merchantCategory,
      );
      final cashback = (result['data'] as Map<String, dynamic>?)?['cashback_amount'] as int?;
      state = state.copyWith(cashbackAmount: cashback, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل حساب الكاش باك');
    }
  }

  void clearError() => state = state.copyWith(error: null);
}

final loyaltyProvider = StateNotifierProvider<LoyaltyNotifier, LoyaltyState>((ref) {
  final api = ApiClient();
  final service = LoyaltyService(api);
  return LoyaltyNotifier(service);
});

class LoyaltyScreen extends ConsumerStatefulWidget {
  const LoyaltyScreen({super.key});
  @override
  ConsumerState<LoyaltyScreen> createState() => _LoyaltyScreenState();
}

class _LoyaltyScreenState extends ConsumerState<LoyaltyScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() {
      ref.read(loyaltyProvider.notifier).loadAll();
    });
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(loyaltyProvider);
    return Scaffold(
      appBar: AppBar(title: const Text('نقاط الولاء')),
      body: state.error != null
          ? Center(
              child: Column(
                mainAxisSize: MainAxisSize.min,
                children: [
                  Text(state.error!, style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.error)),
                  const SizedBox(height: 16),
                  ElevatedButton(
                    onPressed: () {
                      ref.read(loyaltyProvider.notifier).clearError();
                      ref.read(loyaltyProvider.notifier).loadAll();
                    },
                    child: const Text('إعادة المحاولة'),
                  ),
                ],
              ),
            )
          : state.isLoading && state.pointsData == null
               ? Center(
                    child: Shimmer.fromColors(
                      baseColor: AppTheme.shimmer,
                      highlightColor: AppTheme.surfaceContainerLow,
                      child: Padding(
                        padding: const EdgeInsets.all(16),
                        child: Column(
                          children: List.generate(4, (_) => Container(
                            margin: const EdgeInsets.only(bottom: 12),
                            height: 100,
                            decoration: BoxDecoration(
                              color: Colors.white,
                              borderRadius: BorderRadius.circular(12),
                            ),
                          )),
                        ),
                      ),
                    ),
                  )
              : RefreshIndicator(
                  onRefresh: () => ref.read(loyaltyProvider.notifier).loadAll(),
                  child: SingleChildScrollView(
                    physics: const AlwaysScrollableScrollPhysics(),
                    padding: const EdgeInsets.all(16),
                    child: Column(
                      crossAxisAlignment: CrossAxisAlignment.start,
                      children: [
                        _PointsHeader(state: state),
                        const SizedBox(height: 24),
                        _CashbackCalculator(state: state, ref: ref),
                        const SizedBox(height: 24),
                        const Text('المكافآت', style: TextStyle(fontFamily: 'Cairo', fontSize: 18, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 12),
                        state.rewards.isEmpty
                            ? const Center(
                                child: Text('لا توجد مكافآت متاحة', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
                              )
                            : _RewardsGrid(state: state, ref: ref),
                        const SizedBox(height: 24),
                        const Text('السجل', style: TextStyle(fontFamily: 'Cairo', fontSize: 18, fontWeight: FontWeight.bold)),
                        const SizedBox(height: 12),
                        state.history.isEmpty
                            ? const Center(
                                child: Text('لا يوجد سجل', style: TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
                              )
                            : _HistoryList(state: state),
                      ],
                    ),
                  ),
                ),
    );
  }
}

class _PointsHeader extends StatelessWidget {
  final LoyaltyState state;
  const _PointsHeader({required this.state});

  @override
  Widget build(BuildContext context) {
    final points = state.pointsData;
    final balance = points?['balance'] as int? ?? 0;
    final lifetimeEarned = points?['lifetime_earned'] as int? ?? 0;
    final lifetimeRedeemed = points?['lifetime_redeemed'] as int? ?? 0;
    final tier = points?['tier_level'] as String? ?? '';
    final currentTierIndex = state.tiers.indexWhere((t) => t['level'] == tier);
    final nextTier = currentTierIndex >= 0 && currentTierIndex < state.tiers.length - 1
        ? state.tiers[currentTierIndex + 1]
        : null;
    final nextTierPoints = nextTier?['min_points'] as int? ?? 0;
    final progress = nextTier != null && nextTierPoints > 0
        ? (balance / nextTierPoints).clamp(0.0, 1.0)
        : 1.0;

    return Container(
      decoration: AppTheme.cardDecoration,
      child: Container(
        width: double.infinity,
        padding: const EdgeInsets.all(24),
        decoration: BoxDecoration(
          borderRadius: BorderRadius.circular(12),
          gradient: const LinearGradient(
            colors: [AppTheme.primary, AppTheme.primaryDark],
            begin: Alignment.topLeft,
            end: Alignment.bottomRight,
          ),
        ),
        child: Column(
          children: [
            const Text('رصيد النقاط', style: TextStyle(fontFamily: 'Cairo', color: Colors.white70, fontSize: 14)),
            const SizedBox(height: 8),
            Text(
              NumberFormat('#,###').format(balance),
              style: const TextStyle(
                fontFamily: 'Cairo',
                color: Colors.white,
                fontSize: 40,
                fontWeight: FontWeight.bold,
              ),
            ),
            const SizedBox(height: 4),
            Text('نقطة', style: const TextStyle(fontFamily: 'Cairo', color: Colors.white70, fontSize: 16)),
            if (tier.isNotEmpty) ...[
              const SizedBox(height: 12),
              Container(
                padding: const EdgeInsets.symmetric(horizontal: 16, vertical: 6),
                decoration: BoxDecoration(
                  color: Colors.white.withValues(alpha: 0.2),
                  borderRadius: BorderRadius.circular(20),
                ),
                child: Text(tier, style: const TextStyle(fontFamily: 'Cairo', color: Colors.white, fontWeight: FontWeight.w600)),
              ),
            ],
            if (nextTier != null) ...[
              const SizedBox(height: 16),
              Row(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                      Text('التقدم إلى ${nextTier['name_ar'] ?? nextTier['level'] ?? ''}',
                          style: const TextStyle(fontFamily: 'Cairo', color: Colors.white70, fontSize: 12)),
                      Text('${(progress * 100).toInt()}%',
                          style: const TextStyle(fontFamily: 'Cairo', color: Colors.white70, fontSize: 12)),
                ],
              ),
              const SizedBox(height: 4),
              ClipRRect(
                borderRadius: BorderRadius.circular(4),
                child: LinearProgressIndicator(
                  value: progress,
                  backgroundColor: Colors.white.withValues(alpha: 0.3),
                  valueColor: const AlwaysStoppedAnimation<Color>(AppTheme.accent),
                  minHeight: 6,
                ),
              ),
            ],
            const SizedBox(height: 16),
            Row(
              mainAxisAlignment: MainAxisAlignment.spaceAround,
              children: [
                _MiniStat(label: 'المكتسب', value: NumberFormat('#,###').format(lifetimeEarned)),
                _MiniStat(label: 'المستبدل', value: NumberFormat('#,###').format(lifetimeRedeemed)),
              ],
            ),
          ],
        ),
      ),
    );
  }
}

class _MiniStat extends StatelessWidget {
  final String label;
  final String value;
  const _MiniStat({required this.label, required this.value});

  @override
  Widget build(BuildContext context) {
    return Column(
      children: [
        Text(value, style: const TextStyle(fontFamily: 'Cairo', color: Colors.white, fontWeight: FontWeight.bold, fontSize: 16)),
        Text(label, style: const TextStyle(fontFamily: 'Cairo', color: Colors.white70, fontSize: 12)),
      ],
    );
  }
}

class _CashbackCalculator extends StatelessWidget {
  final LoyaltyState state;
  final WidgetRef ref;
  const _CashbackCalculator({required this.state, required this.ref});

  @override
  Widget build(BuildContext context) {
    final amountCtrl = TextEditingController(
      text: state.transactionAmount?.toString() ?? '',
    );
    return Container(
      decoration: AppTheme.cardDecoration,
      child: Padding(
        padding: const EdgeInsets.all(16),
        child: Column(
          crossAxisAlignment: CrossAxisAlignment.start,
          children: [
            const Text('حاسبة الكاش باك', style: TextStyle(fontFamily: 'Cairo', fontSize: 16, fontWeight: FontWeight.bold)),
            const SizedBox(height: 12),
            TextField(
              controller: amountCtrl,
              decoration: const InputDecoration(
                labelText: 'مبلغ المعاملة (ل.س)',
                prefixIcon: Icon(Icons.monetization_on_outlined),
              ),
              keyboardType: TextInputType.number,
            ),
            const SizedBox(height: 12),
            SizedBox(
              width: double.infinity,
              child: ElevatedButton(
                onPressed: () {
                  final amount = int.tryParse(amountCtrl.text) ?? 0;
                  if (amount > 0) {
                    ref.read(loyaltyProvider.notifier).calculateCashback(
                      transactionAmount: amount,
                    );
                  }
                },
                child: const Text('احسب الكاش باك'),
              ),
            ),
            if (state.isLoading)
              const Padding(
                padding: EdgeInsets.only(top: 12),
                child: Center(child: SizedBox(width: 24, height: 24, child: CircularProgressIndicator(strokeWidth: 2))),
              )
            else if (state.cashbackAmount != null)
              Padding(
                padding: const EdgeInsets.only(top: 12),
                child: Row(
                  children: [
                    const Text('الكاش باك المتوقع: ', style: TextStyle(fontFamily: 'Cairo', fontSize: 16)),
                    Text(
                      '${NumberFormat('#,###').format(state.cashbackAmount)} ل.س',
                      style: const TextStyle(
                        fontFamily: 'Cairo',
                        fontSize: 18,
                        fontWeight: FontWeight.bold,
                        color: AppTheme.primary,
                      ),
                    ),
                  ],
                ),
              ),
          ],
        ),
      ),
    );
  }
}

class _RewardsGrid extends StatelessWidget {
  final LoyaltyState state;
  final WidgetRef ref;
  const _RewardsGrid({required this.state, required this.ref});

  @override
  Widget build(BuildContext context) {
    return GridView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      gridDelegate: const SliverGridDelegateWithFixedCrossAxisCount(
        crossAxisCount: 2,
        childAspectRatio: 0.85,
        crossAxisSpacing: 12,
        mainAxisSpacing: 12,
      ),
      itemCount: state.rewards.length,
      itemBuilder: (context, index) {
        final reward = state.rewards[index];
        return Container(
          decoration: AppTheme.cardDecoration,
          child: Padding(
            padding: const EdgeInsets.all(12),
            child: Column(
              crossAxisAlignment: CrossAxisAlignment.start,
              mainAxisAlignment: MainAxisAlignment.spaceBetween,
              children: [
                Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Icon(
                      reward['type'] == 'voucher' ? Icons.card_giftcard : Icons.star,
                      color: AppTheme.primary,
                      size: 28,
                    ),
                    const SizedBox(height: 8),
                    Text(
                      reward['name_ar'] ?? reward['name'] ?? '',
                      style: const TextStyle(fontFamily: 'Cairo', fontWeight: FontWeight.w600, fontSize: 13),
                      maxLines: 2,
                      overflow: TextOverflow.ellipsis,
                    ),
                    const SizedBox(height: 4),
                    if (reward['description_ar'] != null)
                      Text(
                        reward['description_ar'],
                        style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary, fontSize: 11),
                        maxLines: 2,
                        overflow: TextOverflow.ellipsis,
                      ),
                  ],
                ),
                Column(
                  children: [
                    Text(
                      '${NumberFormat('#,###').format(reward['points_cost'])} نقطة',
                      style: const TextStyle(
                        fontFamily: 'Cairo',
                        color: AppTheme.primary,
                        fontWeight: FontWeight.bold,
                        fontSize: 14,
                      ),
                    ),
                    const SizedBox(height: 4),
                    SizedBox(
                      width: double.infinity,
                      child: OutlinedButton(
                        onPressed: reward['stock'] != 0
                            ? () => _confirmRedeem(context, reward)
                            : null,
                        child: Text(
                          reward['stock'] != 0 ? 'استبدال' : 'نفد',
                          style: const TextStyle(fontFamily: 'Cairo', fontSize: 12),
                        ),
                      ),
                    ),
                  ],
                ),
              ],
            ),
          ),
        );
      },
    );
  }

  void _confirmRedeem(BuildContext context, Map<String, dynamic> reward) {
    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: ui.TextDirection.rtl,
        child: AlertDialog(
          title: const Text('تأكيد الاستبدال'),
          content: Text('هل أنت متأكد من استبدال ${reward['name_ar'] ?? reward['name'] ?? ''} بـ ${NumberFormat('#,###').format(reward['points_cost'])} نقطة؟'),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('إلغاء'),
            ),
            ElevatedButton(
              onPressed: () async {
                final success = await ref.read(loyaltyProvider.notifier).redeemReward(reward['id']);
                if (ctx.mounted) Navigator.pop(ctx);
                if (success && context.mounted) {
                  ScaffoldMessenger.of(context).showSnackBar(
                    const SnackBar(content: Text('تم استبدال النقاط بنجاح')),
                  );
                }
              },
              child: const Text('تأكيد'),
            ),
          ],
        ),
      ),
    );
  }
}

class _HistoryList extends StatelessWidget {
  final LoyaltyState state;
  const _HistoryList({required this.state});

  @override
  Widget build(BuildContext context) {
    return ListView.builder(
      shrinkWrap: true,
      physics: const NeverScrollableScrollPhysics(),
      itemCount: state.history.length,
      itemBuilder: (context, index) {
        final h = state.history[index];
        final points = h['points'] as int? ?? 0;
        final isEarned = points > 0;
        return Container(
          margin: const EdgeInsets.only(bottom: 8),
          decoration: AppTheme.cardDecoration,
          child: ListTile(
            leading: CircleAvatar(
              backgroundColor: isEarned ? AppTheme.successLight : AppTheme.errorLight,
              child: Icon(
                isEarned ? Icons.arrow_downward : Icons.arrow_upward,
                color: isEarned ? AppTheme.success : AppTheme.error,
                size: 20,
              ),
            ),
            title: Text(h['description'] ?? ''),
            subtitle: Text(DateFormat('yyyy/MM/dd HH:mm').format(
              DateTime.tryParse(h['created_at'] ?? '') ?? DateTime.now(),
            )),
            trailing: Text(
              '${isEarned ? '+' : ''}${NumberFormat('#,###').format(points)}',
              style: TextStyle(
                fontFamily: 'Cairo',
                fontWeight: FontWeight.bold,
                color: isEarned ? AppTheme.success : AppTheme.error,
              ),
            ),
          ),
        );
      },
    );
  }
}
