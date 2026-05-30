import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../services/merchant_service.dart';

class MerchantState {
  final Map<String, dynamic>? profile;
  final List<Map<String, dynamic>> stores;
  final List<Map<String, dynamic>> payments;
  final String? qrCode;
  final bool isLoading;
  final bool isSubmitting;
  final String? error;
  final bool isRegistered;
  final int selectedTab;

  const MerchantState({
    this.profile,
    this.stores = const [],
    this.payments = const [],
    this.qrCode,
    this.isLoading = false,
    this.isSubmitting = false,
    this.error,
    this.isRegistered = false,
    this.selectedTab = 0,
  });

  MerchantState copyWith({
    Map<String, dynamic>? profile,
    List<Map<String, dynamic>>? stores,
    List<Map<String, dynamic>>? payments,
    String? qrCode,
    bool? isLoading,
    bool? isSubmitting,
    String? error,
    bool? isRegistered,
    int? selectedTab,
  }) {
    return MerchantState(
      profile: profile ?? this.profile,
      stores: stores ?? this.stores,
      payments: payments ?? this.payments,
      qrCode: qrCode ?? this.qrCode,
      isLoading: isLoading ?? this.isLoading,
      isSubmitting: isSubmitting ?? this.isSubmitting,
      error: error,
      isRegistered: isRegistered ?? this.isRegistered,
      selectedTab: selectedTab ?? this.selectedTab,
    );
  }
}

class MerchantNotifier extends StateNotifier<MerchantState> {
  final MerchantService _service;

  MerchantNotifier(this._service) : super(const MerchantState());

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

  Future<void> loadStores() async {
    try {
      final result = await _service.getStores();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(stores: list);
    } catch (e) {
      state = state.copyWith(error: 'فشل تحميل المتاجر');
    }
  }

  Future<void> loadPayments() async {
    try {
      final result = await _service.getPayments();
      final list = (result['data'] as List?)?.cast<Map<String, dynamic>>() ?? [];
      state = state.copyWith(payments: list);
    } catch (e) {
      state = state.copyWith(error: 'فشل تحميل المدفوعات');
    }
  }

  Future<void> loadQrCode() async {
    try {
      final result = await _service.generateQr();
      final code = result['qr_code'] as String? ?? result['data']?['qr_code'] as String?;
      state = state.copyWith(qrCode: code);
    } catch (e) {
      state = state.copyWith(error: 'فشل تحميل رمز QR');
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
        error: 'فشل التسجيل كتاجر',
      );
      return false;
    }
  }

  Future<bool> createStore(Map<String, dynamic> data) async {
    try {
      await _service.createStore(data);
      await loadStores();
      return true;
    } catch (e) {
      state = state.copyWith(error: 'فشل إنشاء المتجر');
      return false;
    }
  }

  void setTab(int index) {
    state = state.copyWith(selectedTab: index);
  }
}

final merchantProvider = StateNotifierProvider<MerchantNotifier, MerchantState>((ref) {
  final api = ApiClient();
  final service = MerchantService(api);
  return MerchantNotifier(service);
});
