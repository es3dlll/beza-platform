import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../services/bills_service.dart';

class BillsState {
  final List<Map<String, dynamic>> providers;
  final List<Map<String, dynamic>> bills;
  final bool isLoading;
  final String? error;
  final int selectedTab;

  const BillsState({
    this.providers = const [],
    this.bills = const [],
    this.isLoading = false,
    this.error,
    this.selectedTab = 0,
  });

  BillsState copyWith({
    List<Map<String, dynamic>>? providers,
    List<Map<String, dynamic>>? bills,
    bool? isLoading,
    String? error,
    int? selectedTab,
  }) {
    return BillsState(
      providers: providers ?? this.providers,
      bills: bills ?? this.bills,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      selectedTab: selectedTab ?? this.selectedTab,
    );
  }
}

class BillsNotifier extends StateNotifier<BillsState> {
  final BillsService _service;

  BillsNotifier(this._service) : super(const BillsState());

  Future<void> loadProviders() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getProviders();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(providers: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل الخدمات');
    }
  }

  Future<void> loadHistory() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getHistory();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(bills: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل الفواتير');
    }
  }

  Future<Map<String, dynamic>?> inquiry({
    required String billProviderId,
    required String accountNumber,
  }) async {
    try {
      final result = await _service.inquiry(
        billProviderId: billProviderId,
        accountNumber: accountNumber,
      );
      return result['data'] as Map<String, dynamic>?;
    } catch (e) {
      return null;
    }
  }

  Future<Map<String, dynamic>?> pay({
    required String billPaymentId,
    int? amount,
  }) async {
    try {
      final result = await _service.pay(
        billPaymentId: billPaymentId,
        amount: amount,
      );
      await loadHistory();
      return result['data'] as Map<String, dynamic>?;
    } catch (e) {
      return null;
    }
  }

  void setTab(int index) {
    state = state.copyWith(selectedTab: index);
  }
}

final billsProvider = StateNotifierProvider<BillsNotifier, BillsState>((ref) {
  final api = ApiClient();
  final service = BillsService(api);
  return BillsNotifier(service);
});
