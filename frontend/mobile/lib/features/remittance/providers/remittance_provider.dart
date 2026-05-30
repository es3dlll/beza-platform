import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../../../core/api/api_client.dart';
import '../services/remittance_service.dart';

enum ActiveTab { newTransfer, orders }

class RemittanceState {
  final ActiveTab activeTab;
  final int currentStep;
  final List<Map<String, dynamic>> corridors;
  final Map<String, dynamic>? selectedCorridor;
  final List<Map<String, dynamic>> beneficiaries;
  final Map<String, dynamic>? selectedBeneficiary;
  final int sourceAmount;
  final String purposeCode;
  final String sourceOfFunds;
  final String senderFullName;
  final String senderPhone;
  final String payoutMethod;
  final String? payoutWalletId;
  final List<Map<String, dynamic>> orders;
  final Map<String, dynamic>? lastOrder;
  final bool isLoading;
  final String? error;
  final bool isSuccess;
  final String? referenceNumber;

  const RemittanceState({
    this.activeTab = ActiveTab.newTransfer,
    this.currentStep = 0,
    this.corridors = const [],
    this.selectedCorridor,
    this.beneficiaries = const [],
    this.selectedBeneficiary,
    this.sourceAmount = 0,
    this.purposeCode = 'FAMILY_SUPPORT',
    this.sourceOfFunds = '',
    this.senderFullName = '',
    this.senderPhone = '',
    this.payoutMethod = 'agent',
    this.payoutWalletId,
    this.orders = const [],
    this.lastOrder,
    this.isLoading = false,
    this.error,
    this.isSuccess = false,
    this.referenceNumber,
  });

  RemittanceState copyWith({
    ActiveTab? activeTab,
    int? currentStep,
    List<Map<String, dynamic>>? corridors,
    Object? selectedCorridor,
    List<Map<String, dynamic>>? beneficiaries,
    Object? selectedBeneficiary,
    int? sourceAmount,
    String? purposeCode,
    String? sourceOfFunds,
    String? senderFullName,
    String? senderPhone,
    String? payoutMethod,
    String? payoutWalletId,
    List<Map<String, dynamic>>? orders,
    Map<String, dynamic>? lastOrder,
    bool? isLoading,
    String? error,
    bool? isSuccess,
    String? referenceNumber,
  }) {
    return RemittanceState(
      activeTab: activeTab ?? this.activeTab,
      currentStep: currentStep ?? this.currentStep,
      corridors: corridors ?? this.corridors,
      selectedCorridor:
          selectedCorridor == null ? this.selectedCorridor : selectedCorridor as Map<String, dynamic>?,
      beneficiaries: beneficiaries ?? this.beneficiaries,
      selectedBeneficiary:
          selectedBeneficiary == null
              ? this.selectedBeneficiary
              : selectedBeneficiary as Map<String, dynamic>?,
      sourceAmount: sourceAmount ?? this.sourceAmount,
      purposeCode: purposeCode ?? this.purposeCode,
      sourceOfFunds: sourceOfFunds ?? this.sourceOfFunds,
      senderFullName: senderFullName ?? this.senderFullName,
      senderPhone: senderPhone ?? this.senderPhone,
      payoutMethod: payoutMethod ?? this.payoutMethod,
      payoutWalletId: payoutWalletId ?? this.payoutWalletId,
      orders: orders ?? this.orders,
      lastOrder: lastOrder ?? this.lastOrder,
      isLoading: isLoading ?? this.isLoading,
      error: error,
      isSuccess: isSuccess ?? this.isSuccess,
      referenceNumber: referenceNumber ?? this.referenceNumber,
    );
  }
}

class RemittanceNotifier extends StateNotifier<RemittanceState> {
  final RemittanceService _service;

  RemittanceNotifier(this._service) : super(const RemittanceState());

  void setActiveTab(ActiveTab tab) {
    state = state.copyWith(activeTab: tab);
    if (tab == ActiveTab.orders) {
      _loadOrders();
    }
  }

  Future<void> loadCorridors() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final raw = await _service.getCorridors();
      final corridors = raw.cast<Map<String, dynamic>>();
      state = state.copyWith(isLoading: false, corridors: corridors);
    } catch (_) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل الممرات');
    }
  }

  Future<void> loadBeneficiaries() async {
    try {
      final raw = await _service.getBeneficiaries();
      final beneficiaries = raw.cast<Map<String, dynamic>>();
      state = state.copyWith(beneficiaries: beneficiaries);
    } catch (_) {}
  }

  void selectCorridor(Map<String, dynamic> corridor) {
    state = state.copyWith(selectedCorridor: corridor, currentStep: 1);
  }

  void selectBeneficiary(Map<String, dynamic> beneficiary) {
    state = state.copyWith(selectedBeneficiary: beneficiary, currentStep: 2);
  }

  void setAmount(int amount) {
    state = state.copyWith(sourceAmount: amount);
  }

  void setPurposeCode(String code) {
    state = state.copyWith(purposeCode: code);
  }

  void setSourceOfFunds(String funds) {
    state = state.copyWith(sourceOfFunds: funds);
  }

  void setSenderInfo(String name, String phone) {
    state = state.copyWith(senderFullName: name, senderPhone: phone);
  }

  void setPayoutMethod(String method) {
    state = state.copyWith(payoutMethod: method);
  }

  void nextStep() {
    state = state.copyWith(currentStep: state.currentStep + 1);
  }

  void previousStep() {
    state = state.copyWith(currentStep: state.currentStep - 1);
  }

  void reset() {
    state = const RemittanceState();
  }

  Future<void> _loadOrders() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final raw = await _service.getOrders();
      final orders = raw.cast<Map<String, dynamic>>();
      state = state.copyWith(isLoading: false, orders: orders);
    } catch (_) {
      state = state.copyWith(isLoading: false, error: 'فشل تحميل الطلبات');
    }
  }

  Future<void> addBeneficiary(Map<String, dynamic> data) async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.createBeneficiary(data);
      await loadBeneficiaries();
      state = state.copyWith(
        isLoading: false,
        selectedBeneficiary: result,
      );
    } catch (_) {
      state = state.copyWith(isLoading: false, error: 'فشل إضافة المستفيد');
    }
  }

  Future<void> submitOrder() async {
    state = state.copyWith(isLoading: true, error: null);
    try {
      final result = await _service.createOrder({
        'corridor_id': state.selectedCorridor!['id'],
        'beneficiary_id': state.selectedBeneficiary!['id'],
        'source_amount': state.sourceAmount,
        'source_currency': state.selectedCorridor!['source_currency'],
        'payout_method': state.payoutMethod,
        if (state.payoutWalletId != null) 'payout_wallet_id': state.payoutWalletId,
        'purpose_code': state.purposeCode,
        'source_of_funds_declaration': state.sourceOfFunds,
        'sender_full_name': state.senderFullName,
        'sender_phone': state.senderPhone,
      });
      state = state.copyWith(
        isLoading: false,
        isSuccess: true,
        referenceNumber: result['reference_number'] as String?,
        lastOrder: result,
      );
    } catch (_) {
      state = state.copyWith(isLoading: false, error: 'فشل إرسال الحوالة');
    }
  }
}

final remittanceServiceProvider = Provider<RemittanceService>((ref) {
  return RemittanceService(ApiClient());
});

final remittanceProvider =
    StateNotifierProvider<RemittanceNotifier, RemittanceState>((ref) {
  return RemittanceNotifier(ref.watch(remittanceServiceProvider));
});
