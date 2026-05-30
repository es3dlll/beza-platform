# Open Finance Flutter State Management

## Provider Definitions (Riverpod)

### ApiUsage Provider
```dart
@riverpod
class ApiUsage extends _$ApiUsage {
  @override
  AsyncValue<UsageStats> build() {
    _fetchStats();
    return const AsyncValue.loading();
  }

  Future<void> _fetchStats() async {
    state = const AsyncValue.loading();
    state = await AsyncValue.guard(() => _fetchFromApi());
  }

  Future<UsageStats> _fetchFromApi() async {
    final repo = ref.read(statsRepositoryProvider);
    return repo.getDashboardStats();
  }
}

class UsageStats {
  final int dailyRequests;
  final double errorRate;
  final int p99Latency;
  final int activeApps;
  final List<UsagePoint> timeSeries;
  final List<RequestLogItem> recentRequests;
  // ...
}
```

### ApiKeyList Provider
```dart
@riverpod
class ApiKeyList extends _$ApiKeyList {
  @override
  AsyncValue<List<ApiKey>> build() {
    _loadKeys();
    return const AsyncValue.loading();
  }

  Future<void> _loadKeys() async {
    final repo = ref.read(apiKeyRepositoryProvider);
    state = await AsyncValue.guard(() => repo.getKeys());
  }

  Future<void> createKey(CreateKeyRequest request) async {
    final repo = ref.read(apiKeyRepositoryProvider);
    final newKey = await repo.createKey(request);
    state = AsyncValue.data([...state.value ?? [], newKey]);
  }

  Future<void> revokeKey(String keyId) async {
    final repo = ref.read(apiKeyRepositoryProvider);
    await repo.revokeKey(keyId);
    state = AsyncValue.data(
      (state.value ?? []).where((k) => k.id != keyId).toList()
    );
  }
}
```

### WebhookConfig Provider
```dart
@riverpod
class WebhookConfig extends _$WebhookConfig {
  @override
  AsyncValue<WebhookSettings> build() {
    _loadConfig();
    return const AsyncValue.loading();
  }

  Future<void> _loadConfig() async {
    final repo = ref.read(webhookRepositoryProvider);
    state = await AsyncValue.guard(() => repo.getConfig());
  }

  Future<void> updateEndpoint(String url) async {
    final repo = ref.read(webhookRepositoryProvider);
    await repo.updateEndpoint(url);
    state = AsyncValue.data(
      (state.value ?? WebhookSettings.empty()).copyWith(endpointUrl: url)
    );
  }

  Future<void> toggleEvent(String eventType, bool enabled) async {
    final repo = ref.read(webhookRepositoryProvider);
    await repo.setEventSubscription(eventType, enabled);
    // Refresh config
    _loadConfig();
  }
}

class WebhookSettings {
  final String endpointUrl;
  final String signingSecret;
  final List<EventSubscription> events;
  final List<DeliveryLog> recentDeliveries;
  // ...
}
```

## API Client Interceptor
```dart
class BezaApiInterceptor extends Interceptor {
  BezaApiInterceptor({
    required this.apiKeyProvider,
    required this.idempotencyProvider,
  });

  final String Function() apiKeyProvider;
  final String Function() idempotencyProvider;

  @override
  void onRequest(RequestOptions options, RequestInterceptorHandler handler) {
    options.headers['Authorization'] = 'Bearer ${apiKeyProvider()}';
    options.headers['Idempotency-Key'] = idempotencyProvider();
    options.headers['Content-Type'] = 'application/json';
    options.headers['Accept'] = 'application/vnd.beza.v1+json';
    handler.next(options);
  }

  @override
  void onError(DioException err, ErrorInterceptorHandler handler) {
    if (err.response?.statusCode == 429) {
      // Rate limited — show retry-after
      final retryAfter = err.response?.headers['Retry-After']?.first;
      throw RateLimitExceededException(retryAfter: retryAfter);
    }
    if (err.response?.statusCode == 401) {
      // API key invalid — show re-authenticate
      throw ApiKeyInvalidException();
    }
    handler.next(err);
  }
}
```

## Event Bus Integration
```dart
// Events consumed by Developer Portal
sealed class PortalEvent {
  factory PortalEvent.apiCallCompleted(ApiCall call) = ApiCallCompleted;
  factory PortalEvent.webhookReceived(WebhookDelivery delivery) = WebhookReceived;
  factory PortalEvent.keyRotated(String keyId) = KeyRotated;
  factory PortalEvent.sandboxReset() = SandboxReset;
}

// Real-time dashboard updates
ref.listen(eventBusProvider, (prev, next) {
  next.whenData((event) {
    switch (event) {
      case ApiCallCompleted(:final call):
        ref.read(apiUsageProvider.notifier).addRequest(call);
      case WebhookReceived(:final delivery):
        ref.read(webhookConfigProvider.notifier).addDelivery(delivery);
    }
  });
});
```
