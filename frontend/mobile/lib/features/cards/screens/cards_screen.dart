import 'package:flutter/material.dart';
import 'package:flutter_riverpod/flutter_riverpod.dart';
import 'package:shimmer/shimmer.dart';
import '../../../core/api/api_client.dart';
import '../../../core/theme/app_theme.dart';
import '../models/card_model.dart';
import '../services/cards_service.dart';

class CardsState {
  final List<CardModel> cards;
  final bool isLoading;
  final String? error;

  const CardsState({
    this.cards = const [],
    this.isLoading = false,
    this.error,
  });

  CardsState copyWith({
    List<CardModel>? cards,
    bool? isLoading,
    String? error,
  }) {
    return CardsState(
      cards: cards ?? this.cards,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class CardsNotifier extends StateNotifier<CardsState> {
  final CardsService _service;

  CardsNotifier(this._service) : super(const CardsState());

  Future<void> loadCards() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final cards = await _service.getCards();
      state = CardsState(cards: cards);
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: e.toString().replaceFirst('Exception: ', ''),
      );
    }
  }

  Future<void> activateCard(String id) async {
    try {
      final updated = await _service.activateCard(id);
      state = state.copyWith(
        cards: state.cards.map((c) => c.id == id ? updated : c).toList(),
      );
    } catch (e) {
      state = state.copyWith(error: e.toString().replaceFirst('Exception: ', ''));
    }
  }

  Future<void> suspendCard(String id, {String? reason}) async {
    try {
      final updated = await _service.suspendCard(id, reason: reason);
      state = state.copyWith(
        cards: state.cards.map((c) => c.id == id ? updated : c).toList(),
      );
    } catch (e) {
      state = state.copyWith(error: e.toString().replaceFirst('Exception: ', ''));
    }
  }

  Future<void> cancelCard(String id) async {
    try {
      final updated = await _service.cancelCard(id);
      state = state.copyWith(
        cards: state.cards.map((c) => c.id == id ? updated : c).toList(),
      );
    } catch (e) {
      state = state.copyWith(error: e.toString().replaceFirst('Exception: ', ''));
    }
  }

  Future<void> createCard({
    String? cardType,
    required String cardholderName,
    String? currency,
    bool? isVirtual,
  }) async {
    try {
      final newCard = await _service.createCard(
        cardType: cardType,
        cardholderName: cardholderName,
        currency: currency,
        isVirtual: isVirtual,
      );
      state = state.copyWith(cards: [...state.cards, newCard]);
    } catch (e) {
      state = state.copyWith(error: e.toString().replaceFirst('Exception: ', ''));
    }
  }

  Future<void> updateLimits(String id, {int? daily, int? weekly, int? monthly, int? single}) async {
    try {
      final updated = await _service.updateLimits(id, daily: daily, weekly: weekly, monthly: monthly, single: single);
      state = state.copyWith(
        cards: state.cards.map((c) => c.id == id ? updated : c).toList(),
      );
    } catch (e) {
      state = state.copyWith(error: e.toString().replaceFirst('Exception: ', ''));
    }
  }

  Future<void> updateSettings(String id, {bool? international, bool? atm, bool? contactless, bool? ecommerce}) async {
    try {
      final updated = await _service.updateSettings(id, international: international, atm: atm, contactless: contactless, ecommerce: ecommerce);
      state = state.copyWith(
        cards: state.cards.map((c) => c.id == id ? updated : c).toList(),
      );
    } catch (e) {
      state = state.copyWith(error: e.toString().replaceFirst('Exception: ', ''));
    }
  }
}

final cardsProvider =
    StateNotifierProvider<CardsNotifier, CardsState>((ref) {
  final api = ApiClient();
  final service = CardsService(api);
  return CardsNotifier(service);
});

class CardsScreen extends ConsumerStatefulWidget {
  const CardsScreen({super.key});

  @override
  ConsumerState<CardsScreen> createState() => _CardsScreenState();
}

class _CardsScreenState extends ConsumerState<CardsScreen> {
  @override
  void initState() {
    super.initState();
    Future.microtask(() => ref.read(cardsProvider.notifier).loadCards());
  }

  @override
  Widget build(BuildContext context) {
    final state = ref.watch(cardsProvider);

    return Scaffold(
      appBar: AppBar(title: const Text('البطاقات')),
      floatingActionButton: FloatingActionButton(
        onPressed: () => _showCreateCardDialog(context),
        backgroundColor: AppTheme.primary,
        child: const Icon(Icons.add, color: Colors.white),
      ),
      body: _buildBody(state),
    );
  }

  Widget _buildBody(CardsState state) {
    if (state.isLoading && state.cards.isEmpty) {
      return const _ShimmerList();
    }

    if (state.error != null && state.cards.isEmpty) {
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
              Text(
                state.error!,
                style: const TextStyle(fontFamily: 'Cairo', fontSize: 16, color: AppTheme.textSecondary),
                textAlign: TextAlign.center,
              ),
              const SizedBox(height: 24),
              ElevatedButton.icon(
                onPressed: () => ref.read(cardsProvider.notifier).loadCards(),
                icon: const Icon(Icons.refresh, size: 18),
                label: const Text('إعادة المحاولة'),
              ),
            ],
          ),
        ),
      );
    }

    if (state.cards.isEmpty) {
      return Center(
        child: Column(
          mainAxisSize: MainAxisSize.min,
          children: [
            Container(
              padding: const EdgeInsets.all(20),
              decoration: BoxDecoration(color: AppTheme.surfaceContainerLow, shape: BoxShape.circle),
              child: Icon(Icons.credit_card_off, size: 40, color: AppTheme.textSecondary.withValues(alpha: 0.5)),
            ),
            const SizedBox(height: 20),
            const Text(
              'لا توجد بطاقات بعد',
              style: TextStyle(fontFamily: 'Cairo', fontSize: 20, fontWeight: FontWeight.bold, color: AppTheme.textPrimary),
            ),
            const SizedBox(height: 10),
            const Text(
              'اضغط على + لإنشاء بطاقة جديدة',
              style: TextStyle(fontFamily: 'Cairo', fontSize: 14, color: AppTheme.textSecondary),
            ),
          ],
        ),
      );
    }

    return RefreshIndicator(
      onRefresh: () => ref.read(cardsProvider.notifier).loadCards(),
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: state.cards.length + (state.isLoading ? 1 : 0),
        itemBuilder: (context, index) {
          if (index == state.cards.length) {
            return const Center(child: CircularProgressIndicator());
          }
          return _CardItem(
            card: state.cards[index],
            onTap: () => _showCardOptions(context, state.cards[index]),
          );
        },
      ),
    );
  }

  void _showCreateCardDialog(BuildContext context) {
    final nameController = TextEditingController();
    String? selectedType;
    bool isVirtual = false;
    String? selectedCurrency;

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          title: const Text('إنشاء بطاقة جديدة'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: nameController,
                  decoration: const InputDecoration(
                    labelText: 'اسم حامل البطاقة',
                    hintText: 'الاسم حسب الهوية',
                  ),
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  decoration: const InputDecoration(labelText: 'نوع البطاقة'),
                  items: const [
                    DropdownMenuItem(value: 'virtual', child: Text('افتراضية')),
                    DropdownMenuItem(value: 'prepaid', child: Text('مسبقة الدفع')),
                    DropdownMenuItem(value: 'debit', child: Text('خصم مباشر')),
                  ],
                  onChanged: (v) => selectedType = v,
                ),
                const SizedBox(height: 16),
                DropdownButtonFormField<String>(
                  decoration: const InputDecoration(labelText: 'العملة'),
                  initialValue: 'SYP',
                  items: const [
                    DropdownMenuItem(value: 'SYP', child: Text('ل.س')),
                    DropdownMenuItem(value: 'USD', child: Text('دولار')),
                  ],
                  onChanged: (v) => selectedCurrency = v,
                ),
                const SizedBox(height: 16),
                SwitchListTile(
                  title: const Text('بطاقة افتراضية'),
                  value: isVirtual,
                  activeThumbColor: AppTheme.primary,
                  onChanged: (v) => isVirtual = v,
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('إلغاء'),
            ),
            ElevatedButton(
              onPressed: () {
                if (nameController.text.trim().isEmpty) return;
                ref.read(cardsProvider.notifier).createCard(
                      cardType: selectedType,
                      cardholderName: nameController.text.trim(),
                      currency: selectedCurrency,
                      isVirtual: isVirtual,
                    );
                Navigator.pop(ctx);
              },
              child: const Text('إنشاء'),
            ),
          ],
        ),
      ),
    );
  }

  void _showCardOptions(BuildContext context, CardModel card) {
    showModalBottomSheet(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: SafeArea(
          child: Padding(
            padding: const EdgeInsets.all(16),
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                Container(
                  width: 40,
                  height: 4,
                  decoration: BoxDecoration(
                    color: Colors.grey[300],
                    borderRadius: BorderRadius.circular(2),
                  ),
                ),
                const SizedBox(height: 16),
                Text(
                  '**** ${card.cardNumberLast4}',
                  style: const TextStyle(
                    fontFamily: 'Cairo',
                    fontSize: 18,
                    fontWeight: FontWeight.bold,
                  ),
                ),
                Text(card.cardholderName,
                    style: const TextStyle(fontFamily: 'Cairo', color: AppTheme.textSecondary)),
                const SizedBox(height: 16),
                const Divider(),
                if (card.status == 'pending' || card.status == 'suspended')
                  ListTile(
                    leading: const Icon(Icons.check_circle_outline,
                        color: Colors.green),
                    title: const Text('تفعيل البطاقة'),
                    onTap: () {
                      ref.read(cardsProvider.notifier).activateCard(card.id);
                      Navigator.pop(ctx);
                    },
                  ),
                if (card.status == 'active')
                  ListTile(
                    leading: const Icon(Icons.pause_circle_outline,
                        color: Colors.orange),
                    title: const Text('تعليق البطاقة'),
                    onTap: () {
                      Navigator.pop(ctx);
                      _showSuspendDialog(context, card.id);
                    },
                  ),
                if (card.status != 'cancelled')
                  ListTile(
                    leading: const Icon(Icons.cancel_outlined,
                        color: AppTheme.error),
                    title: const Text('إلغاء البطاقة'),
                    onTap: () {
                      Navigator.pop(ctx);
                      _showCancelConfirm(context, card.id);
                    },
                  ),
                ListTile(
                  leading: const Icon(Icons.tune, color: AppTheme.primary),
                  title: const Text('إدارة الحدود'),
                  onTap: () {
                    Navigator.pop(ctx);
                    _showLimitsDialog(context, card);
                  },
                ),
                ListTile(
                  leading: const Icon(Icons.settings, color: AppTheme.primary),
                  title: const Text('الإعدادات'),
                  onTap: () {
                    Navigator.pop(ctx);
                    _showSettingsDialog(context, card);
                  },
                ),
              ],
            ),
          ),
        ),
      ),
    );
  }

  void _showSuspendDialog(BuildContext context, String cardId) {
    final reasonController = TextEditingController();
    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          title: const Text('تعليق البطاقة'),
          content: TextField(
            controller: reasonController,
            decoration: const InputDecoration(
              labelText: 'سبب التعليق (اختياري)',
              hintText: 'أدخل سبب التعليق',
            ),
            maxLines: 2,
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('إلغاء'),
            ),
            ElevatedButton(
              onPressed: () {
                ref.read(cardsProvider.notifier).suspendCard(
                      cardId,
                      reason: reasonController.text.isNotEmpty
                          ? reasonController.text
                          : null,
                    );
                Navigator.pop(ctx);
              },
              child: const Text('تعليق'),
            ),
          ],
        ),
      ),
    );
  }

  void _showCancelConfirm(BuildContext context, String cardId) {
    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          title: const Text('تأكيد إلغاء البطاقة'),
          content: const Text(
            'هل أنت متأكد من إلغاء هذه البطاقة؟ لا يمكن التراجع عن هذا الإجراء.',
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('تراجع'),
            ),
            ElevatedButton(
              style: ElevatedButton.styleFrom(
                backgroundColor: AppTheme.error,
              ),
              onPressed: () {
                ref.read(cardsProvider.notifier).cancelCard(cardId);
                Navigator.pop(ctx);
              },
              child: const Text('إلغاء البطاقة'),
            ),
          ],
        ),
      ),
    );
  }

  void _showLimitsDialog(BuildContext context, CardModel card) {
    final dailyController =
        TextEditingController(text: card.dailyLimit.toString());
    final weeklyController =
        TextEditingController(text: card.weeklyLimit.toString());
    final monthlyController =
        TextEditingController(text: card.monthlyLimit.toString());
    final singleController =
        TextEditingController(text: card.singleTxnLimit.toString());

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: AlertDialog(
          title: const Text('تعديل الحدود'),
          content: SingleChildScrollView(
            child: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                TextField(
                  controller: dailyController,
                  decoration: const InputDecoration(
                    labelText: 'الحد اليومي',
                    hintText: 'قيمة الحد اليومي',
                  ),
                  keyboardType: TextInputType.number,
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: weeklyController,
                  decoration: const InputDecoration(
                    labelText: 'الحد الأسبوعي',
                    hintText: 'قيمة الحد الأسبوعي',
                  ),
                  keyboardType: TextInputType.number,
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: monthlyController,
                  decoration: const InputDecoration(
                    labelText: 'الحد الشهري',
                    hintText: 'قيمة الحد الشهري',
                  ),
                  keyboardType: TextInputType.number,
                ),
                const SizedBox(height: 12),
                TextField(
                  controller: singleController,
                  decoration: const InputDecoration(
                    labelText: 'حد المعاملة الواحدة',
                    hintText: 'قيمة الحد للمعاملة الواحدة',
                  ),
                  keyboardType: TextInputType.number,
                ),
              ],
            ),
          ),
          actions: [
            TextButton(
              onPressed: () => Navigator.pop(ctx),
              child: const Text('إلغاء'),
            ),
            ElevatedButton(
              onPressed: () {
                ref.read(cardsProvider.notifier).updateLimits(
                  card.id,
                  daily: int.tryParse(dailyController.text),
                  weekly: int.tryParse(weeklyController.text),
                  monthly: int.tryParse(monthlyController.text),
                  single: int.tryParse(singleController.text),
                );
                Navigator.pop(ctx);
              },
              child: const Text('حفظ'),
            ),
          ],
        ),
      ),
    );
  }

  void _showSettingsDialog(BuildContext context, CardModel card) {
    bool international = card.internationalEnabled;
    bool atm = card.atmEnabled;
    bool contactless = card.contactlessEnabled;
    bool ecommerce = card.ecommerceEnabled;

    showDialog(
      context: context,
      builder: (ctx) => Directionality(
        textDirection: TextDirection.rtl,
        child: StatefulBuilder(
          builder: (context, setState) => AlertDialog(
            title: const Text('إعدادات البطاقة'),
            content: Column(
              mainAxisSize: MainAxisSize.min,
              children: [
                SwitchListTile(
                  title: const Text('المعاملات الدولية'),
                  value: international,
                  activeThumbColor: AppTheme.primary,
                  onChanged: (v) => setState(() => international = v),
                ),
                SwitchListTile(
                  title: const Text('سحب من الصراف الآلي'),
                  value: atm,
                  activeThumbColor: AppTheme.primary,
                  onChanged: (v) => setState(() => atm = v),
                ),
                SwitchListTile(
                  title: const Text('الدفع اللاتلامسي'),
                  value: contactless,
                  activeThumbColor: AppTheme.primary,
                  onChanged: (v) => setState(() => contactless = v),
                ),
                SwitchListTile(
                  title: const Text('التسوق عبر الإنترنت'),
                  value: ecommerce,
                  activeThumbColor: AppTheme.primary,
                  onChanged: (v) => setState(() => ecommerce = v),
                ),
              ],
            ),
            actions: [
              TextButton(
                onPressed: () => Navigator.pop(ctx),
                child: const Text('إلغاء'),
              ),
              ElevatedButton(
                onPressed: () {
                  ref.read(cardsProvider.notifier).updateSettings(
                    card.id,
                    international: international,
                    atm: atm,
                    contactless: contactless,
                    ecommerce: ecommerce,
                  );
                  Navigator.pop(ctx);
                },
                child: const Text('حفظ'),
              ),
            ],
          ),
        ),
      ),
    );
  }
}

class _CardItem extends StatelessWidget {
  final CardModel card;
  final VoidCallback onTap;

  const _CardItem({required this.card, required this.onTap});

  Color _statusColor(String status) {
    switch (status) {
      case 'active':
        return Colors.green;
      case 'suspended':
        return Colors.orange;
      case 'cancelled':
        return Colors.red;
      default:
        return Colors.grey;
    }
  }

  String _statusText(String status) {
    switch (status) {
      case 'active':
        return 'نشطة';
      case 'suspended':
        return 'موقوفة';
      case 'cancelled':
        return 'ملغاة';
      case 'pending':
        return 'قيد الانتظار';
      default:
        return status;
    }
  }

  String _typeText(String type) {
    switch (type) {
      case 'virtual':
        return 'افتراضية';
      case 'prepaid':
        return 'مسبقة الدفع';
      case 'debit':
        return 'خصم مباشر';
      default:
        return type;
    }
  }

  @override
  Widget build(BuildContext context) {
    return Container(
      margin: const EdgeInsets.only(bottom: 12),
      decoration: AppTheme.cardDecoration,
      child: InkWell(
        onTap: onTap,
        borderRadius: AppTheme.radiusLg,
        child: Padding(
          padding: const EdgeInsets.all(16),
          child: Row(
            children: [
              Container(
                width: 52,
                height: 52,
                decoration: BoxDecoration(
                  color: AppTheme.primary.withValues(alpha: 0.1),
                  borderRadius: BorderRadius.circular(12),
                ),
                child: const Icon(Icons.credit_card, color: AppTheme.primary),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Text(
                      '**** ${card.cardNumberLast4}',
                      style: const TextStyle(
                        fontFamily: 'Cairo',
                        fontSize: 16,
                        fontWeight: FontWeight.w600,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Text(
                      card.cardholderName,
                      style: const TextStyle(
                        fontFamily: 'Cairo',
                        fontSize: 13,
                        color: AppTheme.textSecondary,
                      ),
                    ),
                    const SizedBox(height: 4),
                    Row(
                      children: [
                        Text(
                          '${card.expiryMonth}/${card.expiryYear}',
                          style: const TextStyle(
                            fontFamily: 'Cairo',
                            fontSize: 12,
                            color: AppTheme.textSecondary,
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: _statusColor(card.status)
                                .withValues(alpha: 0.1),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            _statusText(card.status),
                            style: TextStyle(
                              fontFamily: 'Cairo',
                              fontSize: 11,
                              color: _statusColor(card.status),
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                        const SizedBox(width: 8),
                        Container(
                          padding: const EdgeInsets.symmetric(
                            horizontal: 8,
                            vertical: 2,
                          ),
                          decoration: BoxDecoration(
                            color: AppTheme.accent.withValues(alpha: 0.15),
                            borderRadius: BorderRadius.circular(8),
                          ),
                          child: Text(
                            _typeText(card.cardType),
                            style: const TextStyle(
                              fontFamily: 'Cairo',
                              fontSize: 11,
                              color: AppTheme.accent,
                              fontWeight: FontWeight.w500,
                            ),
                          ),
                        ),
                      ],
                    ),
                  ],
                ),
              ),
              const Icon(Icons.chevron_left, color: AppTheme.textSecondary),
            ],
          ),
        ),
      ),
    );
  }
}

class _ShimmerList extends StatelessWidget {
  const _ShimmerList();

  @override
  Widget build(BuildContext context) {
    return Shimmer.fromColors(
      baseColor: AppTheme.shimmer,
      highlightColor: Colors.grey[100]!,
      child: ListView.builder(
        padding: const EdgeInsets.all(16),
        itemCount: 4,
        itemBuilder: (context, index) => Container(
          margin: const EdgeInsets.only(bottom: 12),
          padding: const EdgeInsets.all(16),
          decoration: BoxDecoration(
            color: Colors.white,
            borderRadius: BorderRadius.circular(16),
          ),
          child: Row(
            children: [
              Container(
                width: 52,
                height: 52,
                decoration: BoxDecoration(
                  color: Colors.white,
                  borderRadius: BorderRadius.circular(12),
                ),
              ),
              const SizedBox(width: 16),
              Expanded(
                child: Column(
                  crossAxisAlignment: CrossAxisAlignment.start,
                  children: [
                    Container(
                      width: 100,
                      height: 14,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                    const SizedBox(height: 8),
                    Container(
                      width: 140,
                      height: 12,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                    const SizedBox(height: 8),
                    Container(
                      width: 80,
                      height: 12,
                      decoration: BoxDecoration(
                        color: Colors.white,
                        borderRadius: BorderRadius.circular(4),
                      ),
                    ),
                  ],
                ),
              ),
            ],
          ),
        ),
      ),
    );
  }
}
