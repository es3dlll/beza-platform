# Settlement Flutter Navigation

## Note
Settlement navigation is primarily web-based. The mobile app provides a thin monitoring widget embedded in the operations dashboard section.

## Navigation Map

```
HomeScreen (Dashboard)
├── SettlementMonitoringWidget (embedded)
│   ├── SettlementAlertSheet (bottom sheet)
│   │   ├── ExceptionCard → External URL: https://ops.beza.sy/settlement/exceptions/{id}
│   │   ├── BatchCard → External URL: https://ops.beza.sy/settlement/batches/{id}
│   │   └── View Dashboard → External URL: https://ops.beza.sy/settlement
│   └── SettlementStatusBanner → SettlementAlertSheet
```

## Route Definitions
```dart
// settlement_route.dart
class SettlementRoutes {
  static const String viewBatch = 'https://ops.beza.sy/settlement/batches/{id}';
  static const String viewException = 'https://ops.beza.sy/settlement/exceptions/{id}';
  static const String viewDashboard = 'https://ops.beza.sy/settlement';
  static const String viewReport = 'https://ops.beza.sy/settlement/reports/{date}';
}
```

## Deep Link Handling
```dart
// Deep links handled by the web app; mobile app opens URLs externally:
void openSettlementUrl(String path) async {
  final uri = Uri.parse('https://ops.beza.sy$path');
  if (await canLaunchUrl(uri)) {
    await launchUrl(uri, mode: LaunchMode.externalApplication);
  }
}
```
