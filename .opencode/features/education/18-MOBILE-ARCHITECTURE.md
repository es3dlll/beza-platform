# 18 — Mobile Architecture (Flutter)

## 18.1 App Structure

```
lib/
├── features/
│   └── education/
│       ├── screens/
│       │   ├── education_home_screen.dart       — Multi-child dashboard
│       │   ├── fee_detail_screen.dart           — Fee breakdown
│       │   ├── payment_screen.dart              — Pay + authenticate
│       │   ├── receipt_screen.dart              — Post-payment receipt
│       │   ├── payment_history_screen.dart      — All transactions
│       │   ├── auto_pay_screen.dart             — Schedule payments
│       │   ├── financing_screen.dart            — Instalment plans
│       │   └── student_add_screen.dart          — Add child/school
│       ├── widgets/
│       │   ├── student_card.dart
│       │   ├── fee_breakdown_card.dart
│       │   ├── payment_method_picker.dart
│       │   ├── pin_input.dart
│       │   ├── receipt_card.dart
│       │   ├── overdue_badge.dart
│       │   └── empty_state.dart
│       ├── models/
│       │   ├── student.dart
│       │   ├── fee_invoice.dart
│       │   ├── payment.dart
│       │   └── fee_template.dart
│       ├── providers/
│       │   ├── education_provider.dart
│       │   ├── payment_provider.dart
│       │   └── financing_provider.dart
│       ├── repositories/
│       │   ├── education_repository.dart
│       │   └── payment_repository.dart
│       └── services/
│           ├── receipt_service.dart
│           └── notification_service.dart
├── shared/
│   ├── widgets/
│   │   ├── money_text.dart
│   │   ├── status_chip.dart
│   │   └── loading_button.dart
│   └── utils/
│       ├── currency_formatter.dart
│       ├── arabic_date_formatter.dart
│       └── rtl_helper.dart
├── l10n/
│   ├── app_ar.arb
│   └── app_en.arb
└── main.dart
```

## 18.2 Screen Navigation

```
[Education Home] ──→ [Fee Detail] ──→ [Payment] ──→ [Receipt]
       │                                    │
       ├── [Payment History]                └── [Auto-Pay]
       │
       └── [Financing] ──→ [Apply Instalments]
```

## 18.3 Platform-Specific Features

| Feature | Implementation |
|---|---|
| Biometric auth | `local_auth` package (Face ID / fingerprint) |
| Push notifications | Firebase Cloud Messaging (FCM) |
| Receipt PDF sharing | `share_plus` + `path_provider` |
| QR code display | `qr_flutter` |
| SMS fallback | `url_launcher` with `sms:` URI |
| Offline receipt cache | `sqflite` — store last 50 receipts |
| In-app dark mode | System default + manual toggle |

## 18.4 Android-Specific

- Target SDK: 34 (Android 14)
- Min SDK: 26 (Android 8.0)
- Required permissions: INTERNET, VIBRATE, POST_NOTIFICATIONS (Android 13+), BIOMETRIC (for fingerprint)
- Shortcut: long-press app icon → "Pay School Fees" deep link

## 18.5 iOS-Specific

- Min target: iOS 16.0
- Required capabilities: Push Notifications, Face ID
- Associated Domains: `applinks:beza.sy` for universal links
- Widget (iOS 17+): Show next fee due date on home screen

## 18.6 Deep Links

| URI | Action |
|---|---|
| `beza://education/students/{id}/pay` | Open payment for specific student |
| `beza://education/invoices/{id}` | Open invoice detail |
| `beza://receipt/{id}` | Open receipt |
| `beza://education/school/{id}` | Open school message/landing |
