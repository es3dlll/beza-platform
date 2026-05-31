# Open Finance Flutter Screens

## Screen Tree
```
DeveloperPortalFeature
├── DashboardScreen
│   ├── StatCardRow (requests, errors, latency, apps)
│   ├── ApiUsageChart (last 24h line chart)
│   ├── RecentRequestsList
│   │   └── RequestLogItem (method, path, status, time)
│   └── ServiceStatusCard
│       └── ServiceIndicator (name, status, latency)
│
├── ApiKeyListScreen
│   ├── ApiKeyCard (label, prefix, expiry, scopes)
│   │   └── ActionRow (copy, rotate, revoke)
│   └── CreateKeySheet
│       ├── KeyNameField
│       ├── EnvironmentToggle (sandbox/production)
│       └── ScopeCheckboxList
│
├── WebhookConfigScreen
│   ├── EndpointUrlField
│   ├── EventToggleList
│   └── DeliveryLogList
│       └── DeliveryLogItem (event, status, latency, timestamp)
│
├── SandboxScreen
│   ├── BalanceDisplay (simulated wallets)
│   ├── ResetButton
│   ├── WebhookInspector (live event stream)
│   └── TestAccountList
│
├── ApiReferenceScreen
│   ├── EndpointGroupList
│   └── EndpointDetail
│       ├── MethodBadge
│       ├── RequestBody (JSON schema)
│       ├── ResponseExample
│       └── TryItButton → PlaygroundScreen
│
└── PlaygroundScreen
    ├── MethodDropdown
    ├── UrlInput
    ├── HeadersEditor
    ├── BodyEditor (JSON with syntax highlight)
    ├── SendButton
    └── ResponsePanel (status, body, headers, time)
```

## Screen Specifications

### DashboardScreen
```
Widget Tree:
  Scaffold(
    body: RefreshIndicator(
      child: CustomScrollView(
        slivers: [
          SliverAppBar(title: "Beza Developers", actions: [Notifications, Profile]),
          SliverToBoxAdapter(child: StatsRow),
          SliverToBoxAdapter(child: ApiUsageChart),
          SliverToBoxAdapter(child: SectionHeader("آخر الطلبات")),
          SliverList(delegate: RecentRequestsDelegate),
          SliverToBoxAdapter(child: ServiceStatusCard),
        ]
      )
    ),
    bottomNavigationBar: BottomTabBar
  )

Behavior:
  - Pull-to-refresh: resync dashboard stats
  - StatCard tap: navigate to detailed analytics
  - Request tap: navigate to request detail (sandbox or production)
  - Service status tap: view service health history
```

### ApiKeyListScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "مفاتيح API", actions: [CreateButton]),
    body: ListView.builder(
      itemCount: keys.length,
      itemBuilder: ApiKeyCard
    ),
    floatingActionButton: CreateKeyFAB
  )

Behavior:
  - Swipe to revoke with confirmation
  - Tap to copy key with "تم النسخ" snackbar
  - Long press to show key details
  - Rotate generates new key, old expires in 24h
```

### WebhookConfigScreen
```
Widget Tree:
  Scaffold(
    appBar: AppBar(title: "Webhooks"),
    body: SingleChildScrollView(
      children: [
        EndpointSection,
        EventsSection,
        SectionHeader("سجل التسليم"),
        DeliveryLogList,
      ]
    )
  )

Behavior:
  - Endpoint validation: must be HTTPS
  - Test button sends sample event
  - Delivery log: pull-to-refresh, tap for full payload
  - Failed deliveries: tap to retry
```
