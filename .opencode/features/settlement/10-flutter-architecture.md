# Settlement Flutter Architecture

## Note
Settlement is a **back-office operations tool** delivered as a **web application** (Laravel Blade + Vue.js or Livewire), not a Flutter mobile app. This page documents the mobile monitoring widget for settlement alerts in the existing Beza Flutter app.

## Mobile Monitoring Widget

### Widget Tree
```
SettlementMonitoringWidget (ConsumerWidget)
├── SettlementStatusBanner           # Top banner: batch status summary
├── OpenExceptionsBadge             # Badge on home screen notification dot
└── SettlementAlertSheet            # Bottom sheet for detail view

SettlementAlertSheet
├── HeaderSection                   # "التسوية — آخر التحديثات"
├── ExceptionList                   # Scrollable list of active exceptions
│   └── ExceptionCard               # Individual exception summary
│       ├── ExceptionTypeIcon
│       ├── ExceptionAmount
│       └── TimeAgo
├── PendingBatchesList              # Batches awaiting confirmation
│   └── MiniBatchCard
└── QuickActionsBar
    ├── ViewDashboardButton         # Opens web dashboard in browser
    └── CallSupportButton            # Quick dial to ops team
```

### State Management
```dart
// settlement_state.dart
class SettlementState extends Equatable {
  final List<BatchSummary> activeBatches;
  final List<SettlementException> openExceptions;
  final int pendingCount;
  final int exceptionCount;
  final SettlementStatus overallStatus;
  final DateTime lastUpdated;

  const SettlementState({
    this.activeBatches = const [],
    this.openExceptions = const [],
    this.pendingCount = 0,
    this.exceptionCount = 0,
    this.overallStatus = SettlementStatus.settled,
    required this.lastUpdated,
  });

  @override
  List<Object?> get props => [
    activeBatches, openExceptions, pendingCount,
    exceptionCount, overallStatus, lastUpdated
  ];
}

// settlement_cubit.dart
class SettlementCubit extends Cubit<SettlementState> {
  final SettlementMonitoringService monitoringService;

  SettlementCubit(this.monitoringService)
    : super(SettlementState(lastUpdated: DateTime.now()));

  StreamSubscription? _pollSub;

  void startMonitoring() {
    _pollSub = Stream.periodic(const Duration(seconds: 30))
      .asyncMap((_) => monitoringService.fetchStatus())
      .listen((status) => emit(status));
  }

  void stopMonitoring() => _pollSub?.cancel();
}
```

### Navigation
- The monitoring widget is a **dashboard embed** (not full-screen navigation)
- Tapping "View Dashboard" opens `https://ops.beza.sy/settlement` in external browser
- Tapping an exception card opens deep link to ops portal exception detail
- Tapping the banner toggles bottom sheet

### API Integration
```dart
class SettlementMonitoringService {
  final ApiClient client;

  SettlementMonitoringService(this.client);

  Future<SettlementState> fetchStatus() async {
    final response = await client.get('/api/v1/settlement/monitoring/status');
    return SettlementState.fromJson(response.data);
  }

  Future<List<SettlementException>> fetchExceptions() async {
    final response = await client.get('/api/v1/settlement/exceptions?limit=5');
    return (response.data['exceptions'] as List)
      .map((e) => SettlementException.fromJson(e))
      .toList();
  }
}
```
