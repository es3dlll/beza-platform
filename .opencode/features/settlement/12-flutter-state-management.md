# Settlement Flutter State Management

## Note
Settlement monitoring widget uses a lightweight state layer since it only displays read-only status.

## State Definitions

### SettlementMonitoringState
```dart
@freezed
class SettlementMonitoringState with _$SettlementMonitoringState {
  const factory SettlementMonitoringState.initial() = _Initial;
  const factory SettlementMonitoringState.loading() = _Loading;
  const factory SettlementMonitoringState.loaded({
    required SettlementStatus overallStatus,
    required List<BatchSummary> batches,
    required List<SettlementException> exceptions,
    required int pendingCount,
    required int exceptionCount,
    required DateTime lastUpdated,
  }) = _Loaded;
  const factory SettlementMonitoringState.error(String message) = _Error;
}
```

### Actions
```dart
@freezed
class SettlementMonitoringAction with _$SettlementMonitoringAction {
  const factory SettlementMonitoringAction.refresh() = _Refresh;
  const factory SettlementMonitoringAction.startPolling() = _StartPolling;
  const factory SettlementMonitoringAction.stopPolling() = _StopPolling;
  const factory SettlementMonitoringAction.openBatch(BatchSummary batch) = _OpenBatch;
  const factory SettlementMonitoringAction.openException(SettlementException exception) = _OpenException;
  const factory SettlementMonitoringAction.openDashboard() = _OpenDashboard;
}
```

### Reducer Logic
```dart
class SettlementMonitoringReducer {
  SettlementMonitoringState reduce(
    SettlementMonitoringState state,
    SettlementMonitoringAction action,
  ) {
    return action.when(
      refresh: () => state.maybeWhen(
        loaded: (status, batches, exceptions, pending, exCount, updated) {
          // Trigger API refresh
          return state;
        },
        orElse: () => const SettlementMonitoringState.loading(),
      ),
      startPolling: () => state,
      stopPolling: () => state,
      openBatch: (batch) => state,
      openException: (exception) => state,
      openDashboard: () => state,
    );
  }
}
```

### Data Flow
```
UI Layer                    Bloc/Cubit                  Service Layer
   │                           │                            │
   │── refresh() ─────────────>│                            │
   │                           │── fetchStatus() ──────────>│
   │                           │                            │── GET /settlement/monitoring/status
   │                           │<── SettlementState ────────│
   │                           │                            │
   │<── emit(loaded) ─────────│                            │
   │                           │                            │
   │── openException(exc) ────>│                            │
   │                           │── launchUrl(exc.url) ─────>│
   │<── (opens browser) ──────│                            │
```
