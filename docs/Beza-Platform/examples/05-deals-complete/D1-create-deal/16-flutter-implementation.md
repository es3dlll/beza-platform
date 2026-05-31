# 16 - تطبيق Flutter (Flutter Implementation) - إنشاء صفقة (Admin)

## هيكل الملفات

```
lib/features/admin/deals/create/
├── data/
│   ├── models/create_deal_request_model.dart
│   ├── models/create_deal_response_model.dart
│   └── repositories/admin_deal_repository.dart
├── domain/
│   ├── entities/deal_entity.dart
│   └── repositories/i_admin_deal_repository.dart
└── presentation/
    ├── bloc/
    │   ├── create_deal_bloc.dart
    │   ├── create_deal_event.dart
    │   └── create_deal_state.dart
    └── screens/
        └── admin_deal_create_screen.dart
```

## CreateDealBloc

```dart
abstract class CreateDealEvent {}
class SubmitDeal extends CreateDealEvent {
  final String title, description, category, riskLevel, currency;
  final double targetAmount, expectedProfitPercentage;
  final int durationDays;
  SubmitDeal({...});
}

abstract class CreateDealState {}
class CreateDealInitial extends CreateDealState {}
class CreateDealLoading extends CreateDealState {}
class CreateDealSuccess extends CreateDealState {
  final DealEntity deal;
  CreateDealSuccess(this.deal);
}
class CreateDealFailure extends CreateDealState {
  final String error;
  CreateDealFailure(this.error);
}

class CreateDealBloc extends Bloc<CreateDealEvent, CreateDealState> {
  final IAdminDealRepository repository;
  // ...
}
```
