# 02 - بنية الاختبارات (Architecture)

## هيكل مجلدات الاختبارات

```
mobile-app/
├── test/
│   ├── unit/
│   │   ├── services/
│   │   │   ├── api_client_test.dart
│   │   │   ├── auth_service_test.dart
│   │   │   └── wallet_service_test.dart
│   │   ├── repositories/
│   │   │   ├── transfer_repository_test.dart
│   │   │   └── auth_repository_test.dart
│   │   ├── models/
│   │   │   ├── user_model_test.dart
│   │   │   └── transaction_model_test.dart
│   │   └── utils/
│   │       ├── validators_test.dart
│   │       └── formatters_test.dart
│   ├── widget/
│   │   ├── login_screen_test.dart
│   │   ├── transfer_form_test.dart
│   │   ├── wallet_balance_test.dart
│   │   └── transaction_list_test.dart
│   ├── bloc/
│   │   ├── auth_bloc_test.dart
│   │   ├── transfer_bloc_test.dart
│   │   └── wallet_bloc_test.dart
│   ├── integration/
│   │   ├── login_flow_test.dart
│   │   ├── transfer_flow_test.dart
│   │   └── app_flow_test.dart
│   └── helpers/
│       ├── test_helpers.dart
│       └── mock_data.dart
└── integration_test/
    └── app_test.dart
```
