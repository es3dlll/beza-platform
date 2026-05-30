import 'package:flutter_riverpod/flutter_riverpod.dart';
import '../services/fcm_service.dart';

class FcmState {
  final String? token;
  final bool isPermissionGranted;
  final bool isLoading;
  final String? error;

  const FcmState({
    this.token,
    this.isPermissionGranted = false,
    this.isLoading = false,
    this.error,
  });

  FcmState copyWith({
    String? token,
    bool? isPermissionGranted,
    bool? isLoading,
    String? error,
  }) {
    return FcmState(
      token: token ?? this.token,
      isPermissionGranted: isPermissionGranted ?? this.isPermissionGranted,
      isLoading: isLoading ?? this.isLoading,
      error: error,
    );
  }
}

class FcmNotifier extends StateNotifier<FcmState> {
  FcmNotifier() : super(const FcmState());

  Future<void> requestPermission() async {
    state = state.copyWith(isPermissionGranted: true);
  }

  Future<void> refreshToken() async {
    state = state.copyWith(isLoading: true);
    try {
      final token = await FcmService.getToken();
      state = state.copyWith(token: token, isLoading: false);
    } catch (e) {
      state = state.copyWith(error: e.toString(), isLoading: false);
    }
  }
}

final fcmProvider = StateNotifierProvider<FcmNotifier, FcmState>((ref) {
  return FcmNotifier();
});
