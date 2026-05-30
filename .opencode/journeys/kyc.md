# Journey 2: KYC Upgrade (Tier 1 → Tier 2)

## Goal
User upgrades from Tier 1 (basic, max 50,000 SYP daily) to Tier 2 (full, max 500,000 SYP daily) by submitting national ID photos, selfie, and proof of address.

## Actor
- Role: Existing Tier 1 user
- Device: Mobile (Android/iOS)
- Language: Arabic (default), English optional
- Tier: Tier 1 → Tier 2 (pending) → Tier 2 (approved)
- Connectivity: Online

## Preconditions
- User is registered and logged in at Tier 1
- User has Syrian national ID card (الهوية الشخصية أو البطاقة الذكية)
- User has a recent utility bill (water or electricity) in their name
- User has a smartphone with camera
- Daily transaction limit of 50,000 SYP is insufficient for user's needs

## Success Flow
| Step | Actor | Action | System | Event Emitted | State Change |
|------|-------|--------|--------|---------------|--------------|
| 1 | User | Opens app, goes to profile → "تحديث بياناتي" | Shows current KYC status: Tier 1 (50,000 ل.س/يوم) | — | — |
| 2 | User | Taps "رفع الحد اليومي" (Increase Limit) | Displays Tier 2 requirements list: 1. صورة الهوية (وجهان) 2. صورة شخصية 3. فاتورة ماء أو كهرباء | — | — |
| 3 | User | Reads requirements, taps "بدء التوثيق" (Start Verification) | Opens camera with guide overlay for ID front | — | — |
| 4 | User | Positions national ID (الوجه الأمامي) in frame, taps capture | Auto-crops and checks image quality (brightness, blur, glare) | `ID_FRONT_CAPTURED` | — |
| 5 | User | Taps "موافق" (Accept) or "إعادة" (Retake) | Saves ID front image | — | — |
| 6 | User | Flips ID, captures back side (الوجه الخلفي) | Auto-crops and validates | `ID_BACK_CAPTURED` | — |
| 7 | System | — | Runs OCR on ID images: extracts الاسم الكامل، الرقم الوطني، تاريخ الميلاد، مكان الإقامة | — | — |
| 8 | User | Reviews extracted data on screen | Shows: "الاسم: أحمد محمد الخالد / الرقم: 010-1234-5678 / تاريخ الميلاد: 15/03/1990" | — | — |
| 9 | User | Confirms data is correct, taps "صحيح" (Correct) | Proceeds to selfie step | — | — |
| 10 | User | Positions face within oval guide, taps capture | Takes selfie, runs liveness check (blink detection) | `SELFIE_CAPTURED` | — |
| 11 | System | — | Compares selfie with ID photo (facial recognition match ≥ 90%) | `FACE_MATCH_SUCCESS` | — |
| 12 | User | Taps "التالي" (Next) prompted to capture proof of address | Opens camera for utility bill | — | — |
| 13 | User | Captures photo of latest water bill (فاتورة مؤسسة المياه) or electricity bill (فاتورة كهرباء) | Stores bill image | `PROOF_OF_ADDRESS_CAPTURED` | — |
| 14 | User | Taps "إرسال" (Submit) | Bundles all documents into KYC submission | `KYC_SUBMITTED` | KYC: pending review |
| 15 | System | — | Shows "تم استلام طلبك. سنقوم بالمراجعة خلال 24 ساعة." | — | — |
| 16 | System (Compliance) | — | Compliance team reviews: checks OCR data, selfie match, bill validity (date ≤ 3 months, name matches ID) | — | — |
| 17 | System (Compliance) | — | Approves application, upgrades user to Tier 2 | `KYC_APPROVED` | KYC: approved, Tier: 1 → 2 |
| 18 | User | Receives SMS: "تم توثيق حسابك بنجاح. حدك اليومي الآن 500,000 ل.س. شكراً لثقتك ب Beza." | Updates app display to Tier 2 | — | — |
| 19 | User | Opens app, sees profile → Tier 2 with limits displayed: daily 500,000 SYP, monthly 5,000,000 SYP | — | — | — |

## Alternative Flows
### A1: OCR data mismatch
If extracted name doesn't match registration name, flag for manual review. User may need to visit agent with physical ID.

### A2: Selfie liveness check fails
Retry up to 3 times with guided instructions ("ابتعد قليلاً", "ارمش ببطء"). After 3 failures, redirect to in-person verification at agent.

### A3: Utility bill rejected (name mismatch)
Show "اسم صاحب الفاتورة لا يتطابق مع الاسم المسجل. يرجى تقديم فاتورة أخرى أو زيارة أقرب وكيل."

### A4: KYC rejected by compliance
Send SMS: "عذراً، لم يتم قبول طلب التوثيق. السبب: {reason}. يرجى إعادة التقديم." Reasons: صورة غير واضحة, بيانات غير متطابقة, فاتورة منتهية الصلاحية.

## Failure Flows
### F1: Camera access denied
Show "تطبيق Beza يحتاج إلى صلاحية الكاميرا لالتقاط صور الوثائق. يرجى السماح من الإعدادات."

### F2: Compliance system timeout
Submission queued. Retry mechanism: max 3 retries within 5 minutes. If all fail, "تعذر رفع الملفات. حاول مرة أخرى لاحقاً."

### F3: Fraud suspicion during selfie
If liveness detects mask or deepfake indicators, auto-reject and flag account for fraud team review.

## Notifications
- SMS (approval): "تم توثيق حسابك بنجاح. حدك اليومي الآن 500,000 ل.س. شكراً لثقتك ب Beza."
- SMS (rejection): "عذراً، لم يتم قبول طلب التوثيق. يرجى الاتصال بخدمة العملاء على 1234."
- Push (pending): "طلب التوثيق قيد المراجعة. سنخبرك عند الانتهاء."
- Push (approved): "تهانينا! حسابك موثّق. الحد اليومي مرفوع إلى 500,000 ل.س."

## Ledger Impact
No ledger impact for KYC upgrade. Limits change only.

## State Changes
- KYC tier: 1 → (pending) → 2
- Daily transfer limit: 50,000 SYP → 500,000 SYP
- Monthly transfer limit: 500,000 SYP → 5,000,000 SYP
- Wallet max balance: 200,000 SYP → 2,000,000 SYP

## UI Screens
1. Profile → 2. KYC Status → 3. Requirements → 4. ID Front Capture → 5. ID Back Capture → 6. Data Review → 7. Selfie Capture → 8. Utility Bill Capture → 9. Submission Confirmation → 10. Pending Screen → 11. Approved Screen (Tier 2 badge)
