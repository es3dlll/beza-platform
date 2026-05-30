import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../services/agent_service.dart';

class AgentState {
  final Map<String, dynamic>? profile;
  final List<Map<String, dynamic>> transactions;
  final bool isLoading;
  final bool isSubmitting;
  final String? error;
  final bool isRegistered;

  const AgentState({
    this.profile,
    this.transactions = const [],
    this.isLoading = false,
    this.isSubmitting = false,
    this.error,
    this.isRegistered = false,
  });

  AgentState copyWith({
    Map<String, dynamic>? profile,
    List<Map<String, dynamic>>? transactions,
    bool? isLoading,
    bool? isSubmitting,
    String? error,
    bool? isRegistered,
  }) {
    return AgentState(
      profile: profile ?? this.profile,
      transactions: transactions ?? this.transactions,
      isLoading: isLoading ?? this.isLoading,
      isSubmitting: isSubmitting ?? this.isSubmitting,
      error: error,
      isRegistered: isRegistered ?? this.isRegistered,
    );
  }
}

class AgentNotifier extends StateNotifier<AgentState> {
  final AgentService _service;

  AgentNotifier(this._service) : super(const AgentState());

  Future<void> loadProfile() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.getMyProfile();
      final data = result['data'] as Map<String, dynamic>?;
      if (data != null && data['id'] != null) {
        state = state.copyWith(
          profile: data,
          isRegistered: true,
          isLoading: false,
        );
      } else {
        state = state.copyWith(isRegistered: false, isLoading: false);
      }
    } catch (e) {
      state = state.copyWith(isRegistered: false, isLoading: false, error: 'فشل تحميل الملف الشخصي');
    }
  }

  Future<void> loadTransactions() async {
    try {
      final result = await _service.getTransactions();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(transactions: list);
    } catch (e) {
      state = state.copyWith(error: 'فشل تحميل المعاملات');
    }
  }

  Future<bool> register(Map<String, dynamic> data) async {
    state = state.copyWith(isSubmitting: true, error: null);
    try {
      final result = await _service.register(data);
      final profile = result['data'] as Map<String, dynamic>?;
      state = state.copyWith(
        profile: profile,
        isRegistered: true,
        isSubmitting: false,
      );
      return true;
    } catch (e) {
      state = state.copyWith(
        isSubmitting: false,
        error: 'فشل التسجيل كوكيل',
      );
      return false;
    }
  }
}

final agentProvider = StateNotifierProvider<AgentNotifier, AgentState>((ref) {
  final api = ApiClient();
  final service = AgentService(api);
  return AgentNotifier(service);
});
