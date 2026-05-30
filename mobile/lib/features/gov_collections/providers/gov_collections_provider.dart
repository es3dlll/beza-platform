import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../services/gov_collections_service.dart';

class GovCollectionsState {
  final List<Map<String, dynamic>> providers;
  final List<Map<String, dynamic>> payments;
  final bool isLoading;
  final String? error;
  final int selectedTab;

  const GovCollectionsState({
    this.providers = const [],
    this.payments = const [],
    this.isLoading = false,
    this.error,
    this.selectedTab = 0,
  });

  GovCollectionsState copyWith({
    List<Map<String, dynamic>>? providers,
    List<Map<String, dynamic>>? payments,
    bool? isLoading,
    String? error,
    int? selectedTab,
  }) {
    return GovCollectionsState(
      providers: providers ?? this.providers,
      payments: payments ?? this.payments,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      selectedTab: selectedTab ?? this.selectedTab,
    );
  }
}

class GovCollectionsNotifier extends StateNotifier<GovCollectionsState> {
  final GovCollectionsService _service;

  GovCollectionsNotifier(this._service) : super(const GovCollectionsState());

  Future<void> loadProviders() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getProviders();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(providers: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل مقدمي الخدمات');
    }
  }

  Future<void> loadHistory() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getHistory();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(payments: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل سجل الدفعات');
    }
  }

  Future<Map<String, dynamic>?> inquire({
    required String providerCode,
    required String referenceNumber,
  }) async {
    try {
      final result = await _service.inquire(
        providerCode: providerCode,
        referenceNumber: referenceNumber,
      );
      return result['data'] as Map<String, dynamic>?;
    } catch (e) {
      return null;
    }
  }

  Future<Map<String, dynamic>?> pay({
    required String paymentId,
  }) async {
    try {
      final result = await _service.pay(paymentId: paymentId);
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

final govCollectionsProvider = StateNotifierProvider<GovCollectionsNotifier, GovCollectionsState>((ref) {
  final api = ApiClient();
  final service = GovCollectionsService(api);
  return GovCollectionsNotifier(service);
});
