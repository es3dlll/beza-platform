# Settlement Flutter Screens

## Note
Settlement is a back-office web tool. Only a minimal monitoring widget exists in the mobile app. See `10-flutter-architecture.md` for widget details.

## Screen: Settlement Monitoring Widget (Embed)

### Layout
```
┌──────────────────────────────────┐
│  SettlementStatusBanner          │
│  ┌────────────────────────────┐  │
│  │ ⚙️  التسوية              │  │
│  │ ● ٣ استثناءات نشطة       │  │
│  │ ● ١٣ دفعة في طور التأكيد │  │
│  │ ● آخر تحديث: منذ دقيقتين │  │
│  └────────────────────────────┘  │
│                                   │
│  ExceptionCard #1                │
│  ┌────────────────────────────┐  │
│  │ 🔴 عدم تطابق المبلغ       │  │
│  │ Batch #001 · ٥,٠٠٠ ل.س   │  │
│  │ منذ ١٥ دقيقة              │  │
│  └────────────────────────────┘  │
│                                   │
│  ExceptionCard #2                │
│  ┌────────────────────────────┐  │
│  │ 🟡 تأكيد مفقود            │  │
│  │ Batch #001 · ٧٥٠,٠٠٠ ل.س │  │
│  │ منذ ٤٥ دقيقة              │  │
│  └────────────────────────────┘  │
│                                   │
│  [عرض لوحة التحكم]  [اتصال بالفريق]│
└──────────────────────────────────┘
```

### Wireframe Details
```
Widget: SettlementAlertSheet (BottomSheet)
Height: 60% of screen (expandable to 85%)

States:
  - Loading: Skeleton shimmer for exceptions list
  - Empty: "✅ لا توجد استثناءات — كل شيء على ما يرام"
  - Error: "⚠️ تعذر الاتصال بخدمة التسوية" + Retry button
  - Data: Exception cards with severity indicators

Pull to refresh: Manual refresh triggers re-fetch
Auto-refresh: Every 30 seconds (configurable)
```

### Deep Links
| Action | URI |
|--------|-----|
| Open batch detail | `beza://settlement/batch/{id}` |
| Open exception | `beza://settlement/exceptions/{id}` |
| Open dashboard | `https://ops.beza.sy/settlement` |
