# Mobile Interaction Patterns — Beza (بزة)

## Screen Structure

- **Safe area + status bar**: Adapts to Arabic/Latin script. Status bar shows device info in system language.
- **Bottom navigation**: 4 tabs — Home (الرئيسية), Transfer (تحويل), More (المزيد), Profile (الملف الشخصي).
- **Top bar**: App name (Beza/بزة) centered, notification bell (left in RTL / right in LTR), language toggle (عربي / EN) as text button.
- **Content area**: Scrollable vertically. Pull-to-refresh on Home, Transactions, Agents lists.
- **Bottom sheet for confirmations**: Slide-up panel covering 30-50% of screen height — never full screen.

## Common Mobile Patterns

### 1. Phone-First Login
1. User enters phone number (09XX-XXX-XXX format mask)
2. OTP sent via SMS (6 digits, auto-read on Android)
3. User creates 6-digit PIN
4. Biometric prompt (offer fingerprint/face)
5. Dashboard loads

No email/password flow. Phone is the primary identifier.

### 2. PIN Entry
- Custom 6-digit widget, not platform native
- Digits shuffled every time (prevents shoulder surfing)
- Haptic feedback on each press (VibrationEffect on Android, haptic tap on iOS)
- Last digit entry triggers auto-verify (no confirm button)
- After 3 failed attempts: 30s cooldown with countdown timer
- After 5 failed attempts: force logout + SMS reset link

### 3. Biometric Prompt
- Offered immediately after PIN setup
- On subsequent logins: biometric first, PIN fallback
- If biometric unavailable (wet hands, sensor dirty): fallback to PIN silently
- Biometric also used for transaction approval (amounts ≤ 500,000 SYP)

### 4. Amount Entry
- Full-screen numeric keypad overlay
- SYP symbol (ل.س) prepended, large font (32sp)
- Commas for thousands: ١٢٬٣٤٥٬٦٧٨
- Max 12 digits including 2 decimal places (99,999,999,999.99 SYP)
- Amount in words displayed below input (e.g., "خمسة آلاف ليرة سورية فقط")
- Quick-amount buttons: 10,000 | 25,000 | 50,000 | 100,000 | 500,000 SYP

### 5. Confirmation Bottom Sheet
- Slides up with spring animation
- Shows: amount (large, green), recipient name + phone, fee, total, optional note
- "Swipe to confirm" slider at bottom — not a button
- Swipe gesture must travel 100% of slider width (prevents accidental confirm)
- Alternative: PIN entry field for users who prefer tap
- Swipe confirmation triggers haptic + success animation

### 6. Success Animation
- Green checkmark scales from center (0→1.2→1.0 over 400ms)
- Particle burst: 20 small circles in Beza green, expanding outward for 600ms
- Screen holds success state for 2 seconds
- Auto-dismiss and return to dashboard
- Optional: "View Receipt" button during the 2s window

### 7. Toast Messages
- **Non-critical**: Bottom toast, 3s auto-dismiss, no action
  - "تم تحديث الرصيد" (Balance updated)
  - "تم حفظ المستفيد" (Beneficiary saved)
- **Critical errors**: Center modal with error icon, title, message, retry button
  - "فشلت المعاملة. الرجاء المحاولة مرة أخرى."
  - "الرصيد غير كافٍ."
- Error modal persists until user dismisses

### 8. Skeleton Loading
- Shimmer placeholder animation
- Card skeletons with rounded corners matching card shape
- Specific skeletons:
  - **Balance card**: rectangle 340×120dp, two line placeholders
  - **Transaction list**: 4 rows of icon + 2 lines each
  - **Agent list**: 3 rows of avatar + name + distance
  - **Bills**: 2 rows of biller logo + amount + status
- Shimmer gradient: light gray → white → light gray, 1.5s cycle

### 9. Empty States
- Centered illustration (vector, theme-aware)
- Title in Arabic (bold) + English subtitle (light)
- Descriptive message
- Primary CTA button
- See empty-states.md for full reference

### 10. Offline Banner
- Top sticky banner below app bar
- Background: amber/orange (not red — avoid alarm)
- Text: "أنت غير متصل. آخر تحديث: 10:30" with retry button (إعادة المحاولة)
- Retry triggers immediate connectivity check
- Banner dismissible once connection restored

## Navigation Patterns

- **Bottom navigation**: 4 tabs max. Tab re-ordering not allowed.
- **Swipeable tabs**: Within sections like Transaction History — All (الكل) | Send (مرسلة) | Receive (واصلة) | Bills (فواتير). Swipe for tab switching.
- **Back navigation**:
  - Android hardware back: pop screen or close bottom sheet
  - iOS swipe-back gesture: enabled
  - App bar back button: arrow icon (flips direction in RTL)
- **Deep linking**:
  - `beza://transfer` — opens transfer flow
  - `beza://pay/{merchant_id}` — opens payment to specific merchant
  - `beza://bill/{biller_code}` — opens bill payment for specific biller
  - `beza://agent/{agent_id}` — opens agent details/map
