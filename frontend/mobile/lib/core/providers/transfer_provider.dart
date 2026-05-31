import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../money.dart';
import '../../services/transfer_service.dart';
import '../../config/app_config.dart';

enum TransferStep { recipient, amount, confirmation }

class TransferState {
  final TransferStep currentStep;
  final String? recipientEmail;
  final String? recipientName;
  final String? recipientWalletId;
  final Money? amount;
  final Money? fee;
  final Money? netAmount;
  final Money? balance;
  final bool isLookingUpRecipient;
  final bool isProcessingTransfer;
  final String? recipientError;
  final String? amountError;
  final String? transferError;
  final bool isSuccess;
  final String? transactionId;

  const TransferState({
    this.currentStep = TransferStep.recipient,
    this.recipientEmail,
    this.recipientName,
    this.recipientWalletId,
    this.amount,
    this.fee,
    this.netAmount,
    this.balance,
    this.isLookingUpRecipient = false,
    this.isProcessingTransfer = false,
    this.recipientError,
    this.amountError,
    this.transferError,
    this.isSuccess = false,
    this.transactionId,
  });

  TransferState copyWith({
    TransferStep? currentStep,
    String? recipientEmail,
    String? recipientName,
    String? recipientWalletId,
    Money? amount,
    Money? fee,
    Money? netAmount,
    Money? balance,
    bool? isLookingUpRecipient,
    bool? isProcessingTransfer,
    String? recipientError,
    String? amountError,
    String? transferError,
    bool? isSuccess,
    String? transactionId,
    bool clearRecipientError = false,
    bool clearAmountError = false,
    bool clearTransferError = false,
  }) {
    return TransferState(
      currentStep: currentStep ?? this.currentStep,
      recipientEmail: recipientEmail ?? this.recipientEmail,
      recipientName: recipientName ?? this.recipientName,
      recipientWalletId: recipientWalletId ?? this.recipientWalletId,
      amount: amount ?? this.amount,
      fee: fee ?? this.fee,
      netAmount: netAmount ?? this.netAmount,
      balance: balance ?? this.balance,
      isLookingUpRecipient: isLookingUpRecipient ?? this.isLookingUpRecipient,
      isProcessingTransfer: isProcessingTransfer ?? this.isProcessingTransfer,
      recipientError: clearRecipientError ? null : (recipientError ?? this.recipientError),
      amountError: clearAmountError ? null : (amountError ?? this.amountError),
      transferError: clearTransferError ? null : (transferError ?? this.transferError),
      isSuccess: isSuccess ?? this.isSuccess,
      transactionId: transactionId ?? this.transactionId,
    );
  }
}

class TransferNotifier extends StateNotifier<TransferState> {
  final TransferService _transferService;

  TransferNotifier(this._transferService) : super(const TransferState());

  static const int _minAmountFils = 1000;
  static const int _maxAmountFils = 100000000000;
  static const int _feeFils = 0;

  void setRecipientEmail(String email) {
    state = state.copyWith(
      recipientEmail: email.trim(),
      clearRecipientError: true,
    );
  }

  Future<void> lookupRecipient() async {
    final email = state.recipientEmail;
    if (email == null || email.trim().isEmpty) {
      state = state.copyWith(recipientError: 'يرجى إدخال البريد الإلكتروني للمستلم');
      return;
    }

    final emailRegex = RegExp(r'^[a-zA-Z0-9._%+-]+@[a-zA-Z0-9.-]+\.[a-zA-Z]{2,}$');
    if (!emailRegex.hasMatch(email.trim())) {
      state = state.copyWith(recipientError: 'صيغة البريد الإلكتروني غير صحيحة');
      return;
    }

    state = state.copyWith(
      isLookingUpRecipient: true,
      clearRecipientError: true,
    );

    final result = await _transferService.lookupRecipient(email.trim());

    if (result.success && result.data != null) {
      final data = result.data!;
      state = state.copyWith(
        recipientName: data['name'] as String?,
        recipientWalletId: data['wallet_id'] as String?,
        isLookingUpRecipient: false,
        currentStep: TransferStep.amount,
        clearRecipientError: true,
      );
    } else {
      state = state.copyWith(
        isLookingUpRecipient: false,
        recipientError: result.errorMessage ?? 'لم يتم العثور على المستخدم',
      );
    }
  }

  void setAmount(Money? amount) {
    state = state.copyWith(amount: amount, clearAmountError: true);
  }

  void setBalance(Money balance) {
    state = state.copyWith(balance: balance);
  }

  String? validateAmount() {
    final amount = state.amount;
    final balance = state.balance;

    if (amount == null) {
      return 'يرجى إدخال المبلغ';
    }

    if (amount.fils < _minAmountFils) {
      return 'الحد الأدنى للتحويل هو ${Money.fromFils(_minAmountFils).format()}';
    }

    if (amount.fils > _maxAmountFils) {
      return 'الحد الأقصى للتحويل هو ${Money.fromFils(_maxAmountFils).format()}';
    }

    if (balance != null && amount > balance) {
      return 'الرصيد غير كافٍ. رصيدك الحالي هو ${balance.format()}';
    }

    return null;
  }

  void proceedToConfirmation() {
    final amountError = validateAmount();
    if (amountError != null) {
      state = state.copyWith(amountError: amountError);
      return;
    }

    final amount = state.amount!;
    final fee = Money.fromFils(_feeFils);
    final netAmount = amount - fee;

    state = state.copyWith(
      fee: fee,
      netAmount: netAmount,
      currentStep: TransferStep.confirmation,
      clearAmountError: true,
      clearTransferError: true,
    );
  }

  void goBackToAmount() {
    state = state.copyWith(
      currentStep: TransferStep.amount,
      clearTransferError: true,
    );
  }

  void goBackToRecipient() {
    state = state.copyWith(
      currentStep: TransferStep.recipient,
      clearTransferError: true,
      clearAmountError: true,
    );
  }

  Future<void> executeTransfer() async {
    if (state.recipientWalletId == null || state.amount == null) return;

    state = state.copyWith(
      isProcessingTransfer: true,
      clearTransferError: true,
    );

    final result = await _transferService.transfer(
      toWalletId: state.recipientWalletId!,
      amount: state.amount!,
      currency: 'SYP',
    );

    if (result.success) {
      state = state.copyWith(
        isProcessingTransfer: false,
        isSuccess: true,
        transactionId: result.transactionId,
        clearTransferError: true,
      );
    } else {
      state = state.copyWith(
        isProcessingTransfer: false,
        transferError: result.errorMessage,
      );
    }
  }

  void reset() {
    state = const TransferState();
  }
}

final transferServiceProvider = Provider<TransferService>((ref) {
  final config = ref.watch(appConfigProvider);
  return TransferService(baseUrl: config.apiBaseUrl);
});

final transferStateProvider = StateNotifierProvider<TransferNotifier, TransferState>((ref) {
  final service = ref.watch(transferServiceProvider);
  return TransferNotifier(service);
});

final appConfigProvider = Provider<AppConfig>((ref) {
  return AppConfig.development();
});
