# Open Finance Flutter Architecture

## Architecture Pattern
```
Feature-first modular architecture for Developer Portal mobile app:

lib/
├── core/
│   ├── api/                    # Dio client with OAuth 2.0 interceptor
│   ├── auth/                   # API key storage, token management
│   ├── design/                 # Portal design tokens, theme
│   ├── errors/                 # API error handling
│   └── utils/                  # Formatters, validators
│
├── features/
│   └── developer-portal/
│       ├── data/
│       │   ├── datasources/    # Remote (API gateway) + Local (secure storage)
│       │   ├── models/         # JSON serializable
│       │   └── repositories/
│       ├── domain/
│       │   ├── entities/       # APIKey, WebhookEndpoint, etc.
│       │   ├── repositories/
│       │   └── usecases/
│       └── presentation/
│           ├── providers/      # Riverpod providers
│           ├── screens/        # Dashboard, Keys, Webhooks, Sandbox
│           ├── widgets/        # StatCard, EndpointBlock, CodeBlock
│           └── state/
│
├── app.dart
└── main.dart
```

## State Management (Riverpod)
```
Provider Hierarchy:
  ┌─────────────────────────────────────────┐
  │         AuthNotifierProvider            │
  │  (developer session, tokens, org)       │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │       ApiUsageProvider                   │
  │  (daily stats, error rate, latency)     │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │      ApiKeyListProvider                 │
  │  (keys, create, rotate, revoke)         │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │   WebhookConfigProvider                 │
  │  (endpoints, events, delivery logs)     │
  └────────────┬────────────────────────────┘
               │
  ┌────────────▼────────────────────────────┐
  │     SandboxProvider                     │
  │  (reset, webhook inspector, test data)  │
  └─────────────────────────────────────────┘
```

## Data Flow (API Call Example)
```
Developer taps "Send Test Request" in Playground
  → PlaygroundProvider builds request from form
  → ApiClientFactory creates Dio with selected API key
  → RequestInterceptor adds auth header + idempotency key
  → HTTP call to API Gateway
  → Log request/response in PlaygroundProvider history
  → Display response with syntax highlighting
  → On error: parse BezaError, show with docs link
```

## Offline Strategy
```
- API key list cached in encrypted storage
- Dashboard stats cached with 5-min TTL
- Webhook delivery log cached with 1-hour TTL
- API reference docs bundled as static content
- Playground requests require online connection
```
