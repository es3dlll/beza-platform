import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../services/wallet_service.dart';

class WalletState {
  final int balance;
  final String currency;
  final String? walletId;
  final List<Map<String, dynamic>> transactions;
  final bool isLoading;
  final String? error;

  const WalletState({
    this.balance = 0,
    this.currency = 'SYP',
    this.walletId,
    this.transactions = const [],
    this.isLoading = false,
    this.error,
  });

  WalletState copyWith({
    int? balance,
    String? currency,
    String? walletId,
    List<Map<String, dynamic>>? transactions,
    bool? isLoading,
    String? error,
  }) {
    return WalletState(
      balance: balance ?? this.balance,
      currency: currency ?? this.currency,
      walletId: walletId ?? this.walletId,
      transactions: transactions ?? this.transactions,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class WalletNotifier extends StateNotifier<WalletState> {
  final WalletService _service;

  WalletNotifier(this._service) : super(const WalletState());

  Future<void> loadWallet(String id) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getWallet(id);
      final data = result['data'] as Map<String, dynamic>;
      state = WalletState(
        balance: data['balance'] as int? ?? 0,
        currency: data['currency'] as String? ?? 'SYP',
        walletId: id,
        isLoading: false,
      );
    } catch (e) {
      state = state.copyWith(
        isLoading: false,
        error: 'فشل تحميل بيانات المحفظة',
      );
    }
  }

  Future<void> loadTransactions({int page = 1}) async {
    if (state.walletId == null) return;
    try {
      final result = await _service.getTransactions(state.walletId!, page: page);
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(
        transactions: page == 1 ? list : [...state.transactions, ...list],
      );
    } catch (_) {}
  }

  Future<bool> deposit(int amount, String reference) async {
    if (state.walletId == null) return false;
    try {
      await _service.deposit(
        walletId: state.walletId!,
        amount: amount,
        currency: state.currency,
        reference: reference,
      );
      await loadWallet(state.walletId!);
      return true;
    } catch (e) {
      state = state.copyWith(error: 'فشل الإيداع');
      return false;
    }
  }

  Future<bool> transfer(String toWalletId, int amount, String reference) async {
    if (state.walletId == null) return false;
    try {
      await _service.transfer(
        fromWalletId: state.walletId!,
        toWalletId: toWalletId,
        amount: amount,
        currency: state.currency,
        reference: reference,
      );
      await loadWallet(state.walletId!);
      return true;
    } catch (e) {
      state = state.copyWith(error: 'فشل التحويل');
      return false;
    }
  }
}

final walletProvider = StateNotifierProvider<WalletNotifier, WalletState>((ref) {
  final api = ApiClient();
  final service = WalletService(api);
  return WalletNotifier(service);
});
