# Accessibility — Beza (بزة)

## Visual Accessibility

### Font Size
| Element | Minimum Size | Notes |
|---------|-------------|-------|
| Arabic body text | 16sp | Primary content |
| English numerals | 14sp | Phone numbers, amounts |
| Headings | 20sp+ | Section headers |
| Button text | 16sp | CTA labels |
| Caption / helper | 12sp | Fee details, timestamps |
| Bottom navigation | 12sp | Tab labels |
- System font scaling supported up to 200% (Android `fontScale`, iOS `UIFontMetrics`)

### Contrast Ratios
| Level | Ratio | Application |
|-------|-------|-------------|
| AA minimum | 4.5:1 | Body text, labels |
| AA large text | 3:1 | Text ≥ 18sp or ≥ 14sp bold |
| AAA preferred | 7:1 | Critical info: balances, fees, errors |
| Component boundary | 3:1 | Card borders, input outlines |

### Touch Targets
- **Minimum**: 48×48dp (all tappable elements)
- **Preferred**: 56×56dp (bottom nav items, icon buttons)
- **Spacing**: 8dp minimum between touch targets
- **Bottom sheet drag handle**: 48×24dp minimum

### Color-Independent Indicators
All states use **three channels**: icon + text + color
- **Success**: Green check + "تمت" (Completed) text
- **Error**: Red X-circle + "فشلت" (Failed)
- **Warning**: Amber triangle + "تنبيه" (Warning)
- **Info**: Blue circle-i + "معلومة" (Info)
- **Pending**: Gray hourglass + "قيد الانتظار" (Pending)
- **Disabled**: Gray + diagonal slash icon + text explanation

### System Font Scaling
- Test at 100%, 150%, and 200% font scale
- No text truncation at 200% scale
- Line height adjusts proportionally (min 1.3× font size)
- Layout reflows gracefully; no horizontal scrolling at 200%

## Screen Reader (TalkBack / VoiceOver)

### Semantic Labels
- Every icon has a label in both Arabic and English
  - Bell icon: "إشعارات" / "Notifications"
  - Plus icon: "إضافة" / "Add"
  - Arrow: "رجوع" / "Back"
- Custom widgets labeled (e.g., PIN dot fields = "رقم 1 من 6" / "Digit 1 of 6")
- Amounts announced as numerals + words: "٢٥٬٠٠٠ ليرة سورية" / "25,000 Syrian Pounds"

### Navigation Landmarks
- `main` landmark on every screen
- `navigation` for bottom tab bar
- `complementary` for side panels
- `form` for input fields
- Labels in active language: "التنقل الرئيسي" / "Main navigation"

### Form Fields
- Floating labels (Material Design pattern) — label text remains visible when field has content
- Error states: label turns red + error message below
- Validation on blur: "رقم الهاتف غير صحيح" / "Invalid phone number"
- Auto-fill hints: `tel` for phone, `one-time-code` for OTP

### Dynamic Content Announcements
- Balance refresh: "تم تحديث الرصيد. الرصيد الجديد: ١٬٢٠٠٬٠٠٠ ليرة سورية"
- Transaction received: "وصلت حوالة من محمد بقيمة ٢٥٬٠٠٠ ليرة سورية"
- Error: "فشلت المعاملة. الرصيد غير كافٍ."
- Use `announceForAccessibility()` / `UIAccessibility.post(notification:)`

## Motor Accessibility

### Keyboard Support
All functions accessible via external Bluetooth keyboard:
- **Tab / Shift+Tab**: Navigate between elements
- **Enter / Space**: Activate focused element
- **Escape**: Go back / close sheet
- **Arrow keys**: Navigate lists, swipeable tabs
- **Number row**: PIN entry digits

### Gesture Alternatives
Every swipe gesture has a tappable button alternative:
| Gesture | Swipe | Button Alternative |
|---------|-------|-------------------|
| Swipe to confirm | Right swipe on slider | "تأكيد" button below slider |
| Swipe to delete | Left swipe on item | Long press → "حذف" option |
| Pull to refresh | Pull down | "تحديث" button in app bar |
| Swipeable tabs | Horizontal swipe | Tap tab label |
| Drag handle | Swipe down on sheet | "إغلاق" X button in sheet header |

### Authentication
- Biometric + PIN always available as fallback pair
- If biometric sensor fails 3 times: auto-fallback to PIN
- PIN entry works with keyboard (for accessibility keyboards)
- "Use PIN instead" option on biometric prompt
- Timeout: 5 minutes idle → re-authenticate for financial actions
- Longer timeout (15 min) for non-financial: viewing history, agent list

### USSD Fallback
All critical functions available via `*123#`:
- Balance check
- Mini-statement
- Agent locator
- Bill inquiry
- PIN change
- Account freeze (emergency)

## Cognitive Accessibility

### Consistency
- Same navigation structure across all screens
- Bottom tabs never reorder
- Back button always in same position
- Confirmation always requires same flow (review → authenticate → confirm)
- Color coding consistent: green = financial positive, red = financial negative/error

### Error Messages
- Written in Syrian Arabic dialect (عامية سورية), not Modern Standard Arabic
- Examples:
  - "الرصيد ما يكفي" (not "الرصيد غير كافٍ")
  - "حاول مرة تاني" (not "حاول مرة أخرى")
  - "نتاكد من اتصال الانترنت" (not "تأكد من اتصال الإنترنت")
- Clear, actionable: states the problem + how to fix it
- No error codes shown to user (internal only)

### Confirmation Steps
- All irreversible actions require 2-step confirmation:
  1. Review screen (shows all details)
  2. Authentication (PIN or biometric)
- Destructive actions (delete beneficiary, cancel transfer): additional warning dialog
- "Undo" option where possible: 5-second window after beneficiary deletion

### Transaction Limits
- Displayed clearly before confirmation:
  - "الحد اليومي: ١٠٠٠٠٠٠٠ ل.س" (Daily limit)
  - "الحد الأقصى للمعاملة: ٥٠٠٠٠٠٠ ل.س" (Max per transaction)
  - "الحد الفردي: ١٠٠٠٠٠٠ ل.س" (Per transfer limit)
- Limits vary by:
  - Account tier: Basic (Basic) / Verified (موثق) / Premium (ممتاز)
  - KYC level completed
  - Account age (new accounts have lower limits for 30 days)

### Session Timeout
- Inactivity warning at **60 seconds** before logout:
  - Modal: "ستنتهي جلستك بعد 60 ثانية"
  - Dismissible: "البقاء" (Stay) or auto-logout
- Second warning at **30 seconds**: "ستنتهي جلستك بعد 30 ثانية"
- After timeout: redirect to login, no data loss
- Transaction in progress: timeout postponed until transaction completes or 5 min absolute max

### Focus Management
- On screen load: focus on first interactive element
- After action: focus moves to confirmation or next step
- Error state: focus moves to error element
- Modal opens: focus trapped inside modal
- Modal closes: focus returns to trigger element
