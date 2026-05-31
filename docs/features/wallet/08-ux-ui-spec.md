# Wallet UX/UI Specification

## Screen 1: Home / Dashboard

### Layout
```
┌────────────────────────────────┐
│ 🔔  📱  Beza Wallet    ⚙️     │ Status bar
├────────────────────────────────┤
│ ┌──────────────────────────┐   │ Balance Card (24pt padding)
│ │ SYP 125,000              │   │ Amount display
│ │ ●○○○ Show                │   │ Eye toggle
│ │ USD $250                 │   │ Secondary currency
│ │ ┌────┐ ┌────┐ ┌────┐    │   │ Quick actions row
│ │ │ارسل│ │اطلب│ │ادفع│    │   │ Send / Request / Pay
│ │ └────┘ └────┘ └────┘    │   │
│ │ FX: 1 USD = 12,500 SYP  │   │ Rate ticker
│ └──────────────────────────┘   │
├────────────────────────────────┤
│ Recent Transactions        >   │ Section header
│ ┌──────────────────────────┐   │
│ │ 🟢 استلمت من أحمد 25,000│   │ Received (green dot)
│ │ 10:30 صباحاً             │   │ Timestamp
│ ├──────────────────────────┤   │
│ │ 🔴 أرسلت إلى لينا 10,000│   │ Sent (red dot)
│ │ 09:15 صباحاً             │   │
│ ├──────────────────────────┤   │
│ │ 📄 فاتورة كهرباء 35,000 │   │ Bill payment
│ │ 08:00 صباحاً             │   │
│ └──────────────────────────┘   │
├────────────────────────────────┤
│ Savings Goal               >   │ Section
│ [████████░░░░░░░░░░] 50%      │ Progress bar
│ هدف 100,000 / 50,000         │
├────────────────────────────────┤
│ [Bottom Tab Bar]              │
│  الرئيسية | أرسل | اكتشف | المزيد
└────────────────────────────────┘
```

### States
| State | Behavior |
|-------|----------|
| Loading | Skeleton: balance card shimmer, 3x transaction skeleton rows |
| Empty | "مرحباً بك في Beza! قم بشحن محفظتك" with FAB "إيداع" |
| Error | "حدث خطأ في تحميل البيانات" + "إعادة المحاولة" button |
| Offline | Banner: "أنت غير متصل — البيانات قديمة" + cached balance |
| Slow (3s+) | Balance: last known shown with "جارِ التحديث..." |
| Negative balance | Not applicable (prevented at system level) |

## Screen 2: Send Money

### Layout
```
┌────────────────────────────────┐
│ <  إرسال أموال                │ Header
├────────────────────────────────┤
│ البحث عن جهة اتصال...    🔍    │ Search input
├────────────────────────────────┤
│ المدفوعين مؤخراً              │ Section
│ [أحمد] [لينا] [محمود] [+]    │ Recent recipients (horizontal scroll)
├────────────────────────────────┤
│ أو أدخل رقم الهاتف            │ Section
│ ┌────────────────────────┐    │
│ │ +963 9XX XXX XXX      │    │ Phone input with country code
│ └────────────────────────┘    │
├────────────────────────────────┤
│ المبلغ                        │
│ ┌────────────────────────┐    │
│ │     25,000            ل.س │  │ Amount input (large, centered)
│ └────────────────────────┘    │
│ SYP │ USD                    │ Currency toggle
├────────────────────────────────┤
│ تفاصيل العملية                │
│ الرسوم: 125 ل.س (0.5%)       │ Fee breakdown
│ الإجمالي: 25,125 ل.س         │ Total
│ المبلغ المستلم: 25,000 ل.س   │ Recipient gets
├────────────────────────────────┤
│ ملاحظة (اختياري)              │
│ ┌────────────────────────┐    │
│ │ إيجار شهر يونيو        │    │ Note input
│ └────────────────────────┘    │
├────────────────────────────────┤
│ [تأكيد الإرسال]               │ Primary CTA
└────────────────────────────────┘
```

### States
| State | Behavior |
|-------|----------|
| Loading contacts | Phonebook permission → load contacts → show list |
| No contacts | "لم يتم العثور على جهات اتصال — أدخل رقم الهاتف" |
| Invalid number | Red border + "رقم غير صحيح" |
| Insufficient balance | CTA disabled + "الرصيد غير كافٍ" text |
| Daily limit reached | "تم تجاوز الحد اليومي — قم بترقية حسابك" with upgrade link |
| Successful | Confetti animation + "تم الإرسال!" + receipt button |
| Failed | "فشلت العملية" + reason + "إعادة المحاولة" button |

## Screen 3: Transaction Detail

### Layout
```
┌────────────────────────────────┐
│ <  تفاصيل العملية              │ Header
├────────────────────────────────┤
│        🟢                       │ Status icon
│        تم بنجاح                 │ Status text
├────────────────────────────────┤
│         25,000 ل.س             │ Amount (large, centered)
├────────────────────────────────┤
│ المعلومات                      │
│ المرسل: أنت                    │
│ المستلم: أحمد خالد             │
│ التاريخ: 1 يونيو 2026          │
│ الوقت: 10:30 صباحاً            │
│ المرجع: TXN-ABC123XYZ          │
│ الحالة: مكتملة                 │
│ الملاحظة: إيجار شهر يونيو      │
├────────────────────────────────┤
│ تفاصيل الرسوم                  │
│ قيمة التحويل: 25,000 ل.س       │
│ رسوم التحويل: 125 ل.س          │
│ الإجمالي: 25,125 ل.س           │
│ المبلغ المستلم: 25,000 ل.س     │
├────────────────────────────────┤
│ [مشاركة الإيصال] [الإبلاغ عن مشكلة] │ Actions
└────────────────────────────────┘
```
