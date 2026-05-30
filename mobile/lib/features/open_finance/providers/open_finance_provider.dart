import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../services/open_finance_service.dart';

class OpenFinanceState {
  final List<Map<String, dynamic>> apps;
  final List<Map<String, dynamic>> consents;
  final bool isLoading;
  final String? error;
  final int selectedTab;

  const OpenFinanceState({
    this.apps = const [],
    this.consents = const [],
    this.isLoading = false,
    this.error,
    this.selectedTab = 0,
  });

  OpenFinanceState copyWith({
    List<Map<String, dynamic>>? apps,
    List<Map<String, dynamic>>? consents,
    bool? isLoading,
    String? error,
    int? selectedTab,
  }) {
    return OpenFinanceState(
      apps: apps ?? this.apps,
      consents: consents ?? this.consents,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      selectedTab: selectedTab ?? this.selectedTab,
    );
  }
}

class OpenFinanceNotifier extends StateNotifier<OpenFinanceState> {
  final OpenFinanceService _service;

  OpenFinanceNotifier(this._service) : super(const OpenFinanceState());

  Future<void> loadApps() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getApps();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(apps: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل التطبيقات');
    }
  }

  Future<void> loadConsents() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getConsents();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(consents: list, isLoading: false);
    } catch (e) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل التصاريح');
    }
  }

  Future<Map<String, dynamic>?> registerApp({
    required String name,
    String? description,
    required List<String> redirectUris,
    required List<String> scopes,
  }) async {
    try {
      final result = await _service.registerApp(
        name: name,
        description: description,
        redirectUris: redirectUris,
        scopes: scopes,
      );
      await loadApps();
      return result['data'] as Map<String, dynamic>?;
    } catch (e) {
      state = state.copyWith(error: 'فشل تسجيل التطبيق');
      return null;
    }
  }

  Future<Map<String, dynamic>?> createConsent({
    required String appId,
    required List<String> scopes,
  }) async {
    try {
      final result = await _service.createConsent(appId: appId, scopes: scopes);
      await loadConsents();
      return result['data'] as Map<String, dynamic>?;
    } catch (e) {
      state = state.copyWith(error: 'فشل إنشاء التصريح');
      return null;
    }
  }

  Future<bool> revokeConsent(String id) async {
    try {
      await _service.revokeConsent(id);
      await loadConsents();
      return true;
    } catch (e) {
      state = state.copyWith(error: 'فشل إلغاء التصريح');
      return false;
    }
  }

  void setTab(int index) {
    state = state.copyWith(selectedTab: index);
  }

  void clearError() {
    state = state.copyWith(error: null);
  }
}

final openFinanceProvider = StateNotifierProvider<OpenFinanceNotifier, OpenFinanceState>((ref) {
  final api = ApiClient();
  final service = OpenFinanceService(api);
  return OpenFinanceNotifier(service);
});
