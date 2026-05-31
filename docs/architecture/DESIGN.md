# DESIGN.md – تطبيق بزة (Beza) على Google Stitch

**الإصدار:** 1.0
**تاريخ السريان:** 2026
**المنصة المستهدفة:** Google Stitch (Declarative UI + RTL-first)
**ملخص:** نظام تصميم شامل لتطبيق مالي سوري يجمع بين الأصالة المحلية وأحدث اتجاهات 2026، مع دعم كامل للذكاء الاصطناعي، وإمكانية الوصول، والوضع الليلي الذكي.

---

## 1. هوية العلامة التجارية (Brand Identity)

### 1.1 الرؤية
تحويل اقتصاد سوريا من الاعتماد على النقد (90%) إلى منصة رقمية موحدة، آمنة، وشاملة.

### 1.2 القيم

- **الثقة قبل السرعة** – طبقات أمان دون تعقيد.
- **الشفافية المطلقة** – عرض كل رسم وسعر صرف قبل التأكيد.
- **الأولوية العربية** – RTL أولاً مع دعم اللهجات المحلية.
- **الشمولية المالية** – تصميم بديهي لجميع الأعمار والمستويات التعليمية.

---

## 2. نظام الألوان (Color System)

| اللون | الرمز اللوني | الاستخدام | التدرج (Gradient) |
|-------|-------------|-----------|-------------------|
| الأزرق الملكي | `#1E3A8A` | العناصر الأساسية، الثقة، الأزرار الرئيسية | `#1E3A8A → #3B82F6` |
| الأخضر الزمردي | `#10B981` | النمو، الإجراءات الإيجابية (تحويل، دفع) | `#10B981 → #34D399` |
| الذهبي الدافئ | `#F59E0B` | القيمة، المكافآت، الذهب، العروض | `#F59E0B → #FBBF24` |
| الرمادي الحيادي | `#6B7280` | الخلفيات، النصوص الثانوية | – |
| الخلفية (فاتح) | `#F9FAFB` | خلفية الوضع الفاتح | – |
| الخلفية (داكن) | `#111827` | خلفية الوضع الليلي | – |

**تطبيق الألوان في Stitch:** استخدم `StitchColorScheme.fromSeed(seedColor: '#1E3A8A')` لإنشاء لوحة متكاملة مع دعم Dynamic Color على Android 12+.

### 2.1 الألوان الوظيفية

- نجاح: `#10B981`
- خطأ: `#EF4444`
- تحذير: `#F59E0B`
- معلومات: `#3B82F6`

---

## 3. الطباعة (Typography)

### 3.1 الخطوط الأساسية

- **العربية:** Cairo أو Tajawal (وزن: Regular, Medium, Bold)
- **الأرقام والإنجليزية:** Inter (لضمان وضوح الأرقام المالية)
- **التراجع (Fallback):** Roboto / Noto Sans Arabic

### 3.2 مقياس الخطوط الديناميكي (مع دعم تكبير المستخدم)

| الدور | حجم الخط (dp) | الوزن | الارتفاع السطري | الاستخدام |
|-------|--------------|-------|-----------------|-----------|
| Display Large | 57 | Bold | 64 | شاشة الترحيب، الأرقام الكبيرة (الرصيد) |
| Headline Large | 32 | Bold | 40 | عناوين الشاشات الرئيسية |
| Title Large | 22 | SemiBold | 28 | عناوين البطاقات، أسماء الأقسام |
| Body Large | 16 | Regular | 24 | النصوص الطويلة، الفقرات |
| Body Medium | 14 | Regular | 20 | النصوص الثانوية، التسميات |
| Label Large | 14 | Medium | 20 | أزرار، تذييلات |
| Label Small | 11 | Regular | 16 | تواقيت، رسوم بيانية صغيرة |

**تنفيذ Stitch:** استخدم `StitchTextTheme` مع `textScaleFactor` قابل للتعديل من إعدادات إمكانية الوصول.

### 3.3 RTL & LTR

- الاتجاه الافتراضي: RTL (للعربية).
- يتم تبديل الاتجاه تلقائياً عبر `StitchLocalization` عند تغيير اللغة.

---

## 4. الاتجاهات التصميمية لعام 2026 (Design Trends)

### 4.1 Glassmorphism المتقدم

- **متى يُستخدم:** بطاقات الرصيد الرئيسية، النوافذ العائمة (FAB، Chat widget)، شريط التبويب السفلي.
- **قاعدة Stitch:** `StitchGlassContainer(elevation: 2, blurStrength: 8, opacity: 0.85)`

**مثال:**
```dart
StitchGlassContainer(
  borderRadius: 28,
  border: Border.all(color: Colors.white.withOpacity(0.2)),
  child: ...
)
```

### 4.2 Neumorphism الناعم

- **متى يُستخدم:** الأزرار الثانوية، حقول الإدخال، مؤشرات التقدم.
- **تطبيق Stitch:** ليس مدمجاً مباشرة، لكن يمكن تحقيقه عبر `StitchContainer` مع `boxShadow` مخصص (ظلان: فاتح وداكن).
- **معامل:** `offset: Offset(4,4), blurRadius: 8, color: Colors.grey.shade300` + `offset: Offset(-2,-2), blurRadius: 5, color: Colors.white`.

### 4.3 Claymorphism

- **متى يُستخدم:** البطاقات التفاعلية (نوع الحساب، أهداف الادخار)، الأزرار الكبيرة (CTA).
- **سمات:** زوايا مستديرة كبيرة (24dp)، حد سميك 2dp بلون أفتح، ظل داخلي ناعم.
- **تنفيذ مخصص:** يمكن تغليفه كـ `StitchClayCard`.

### 4.4 Micro-animations

- **أنواعها:**
  - تأثير التموج (Ripple): عند الضغط على أي عنصر قابل للنقر.
  - حركة انتقالية (Shared Axis): بين الشاشات (مثلاً من بطاقة الرصيد إلى شاشة التفاصيل).
  - تأثير "نبض" (Pulse): للإشعارات الجديدة أو العروض.
  - رسوم متحركة عند النجاح (Confetti + Checkmark): بعد إتمام تحويل.
- **في Stitch:** استخدم `StitchAnimationBuilder` أو `StitchLottie` لملفات JSON.

### 4.5 التدرج اللوني للنصوص (Gradient Typography)

- **الاستخدام:** الأرقام المهمة (الرصيد، مبلغ التحويل)، العناوين الرئيسية (مرحباً بك).

**مثال:**
```dart
StitchGradientText(
  "1,250,000 ل.س",
  gradient: LinearGradient(colors: [Color(0xFFF59E0B), Color(0xFF1E3A8A)]),
  style: StitchTextTheme.displayLarge,
)
```

---

## 5. المكونات (Components) – حزمة Stitch المخصصة

سنبني مكتبة مكونات داخل `lib/core/widgets` لضمان الاتساق.

### 5.1 أزرار (Buttons)

**BezaPrimaryButton**
- الخلفية: تدرج أزرق-ذهبي (أو أخضر حسب السياق).
- الزوايا: 28dp.
- الظل: خفيف (elevation=2).
- الحالات: طبيعي، مضغوط (مؤشر تحميل)، معطل (عتامة 0.5).
- Stitch: يستند إلى `StitchElevatedButton` مع style مخصص.

**BezaSecondaryButton**
- خلفية شفافة، حد سمك 1.5 بلون ذهبي.
- الزوايا: 28dp.
- Stitch: `StitchOutlinedButton`.

**BezaFloatingActionButton**
- دائري، خلفية زمردية، أيقونة ذهبية.
- تأثير نبض عند ظهور إشعار جديد.

### 5.2 بطاقات (Cards)

**BezaBalanceCard (Hero Card)**
- النمط: Glassmorphism.
- المحتوى: الرصيد الكبير (مع زر إخفاء/إظهار)، رسم بياني صغير (Sparkline)، صف من الإجراءات السريعة.
- التفاعل: الضغط على الرصيد يفتح تفصيل الحركة.

**BezaTransactionTile**
- أيقونة النوع (دائرة ملونة).
- السطر الأول: الاسم / الوصف.
- السطر الثاني: التاريخ والوقت.
- الجهة اليمنى: المبلغ (باللون الأخضر للإيداع، الأحمر للسحب).
- حالة العملية (أيقونة صغيرة: ✔️، ⏳، ❌).

### 5.3 حقول الإدخال (Inputs)

**BezaPhoneField**
- تنسيق تلقائي (+963 ### ### ####).
- زر مسح النص.
- التحقق الفوري (Regex) مع رسالة خطأ أسفل الحقل.

**BezaAmountField**
- لوحة أرقام مخصصة (يمكن استدعاؤها عند النقر).
- عرض المبلغ بخط كبير مع فاصلات الآلاف.
- أزرار سريعة بمبالغ مقترحة.

### 5.4 التنقل (Navigation)

**BezaBottomNavBar**
- 5 أيقونات: الرئيسية، التحويلات، الذهب، الإشعارات، الملف الشخصي.
- تأثير Glassmorphism مع شريط علوي رفيع.
- إظهار شارة (Badge) لعدد الإشعارات غير المقروءة.

**BezaAppBar**
- شفاف (في الشاشات الرئيسية) أو بلون صلب (في الشاشات الداخلية).
- زر الرجوع + العنوان + أيقونة الإعدادات / المساعدة.

---

## 6. دعم الوضع الليلي (Dark Mode)

- يتم الكشف التلقائي عبر `MediaQuery.platformBrightness`.
- يمكن للمستخدم تجاوزه يدوياً من الإعدادات (فاتح، داكن، تلقائي).

**الألوان في الوضع الليلي:**
- الخلفية: `#111827`
- السطح: `#1F2937`
- النص الأساسي: `#F9FAFB`
- النص الثانوي: `#9CA3AF`
- Glassmorphism في الليلي: خلفية شفافة مع `backdropFilter: blur` ولون خلفية `Colors.black.withOpacity(0.5)`.

---

## 7. إمكانية الوصول (Accessibility)

### 7.1 القياسات الدنيا

- حجم اللمس: 48×48dp لجميع العناصر القابلة للنقر.
- تباين النص: لا يقل عن 4.5:1 للنص العادي، 3:1 للنص الكبير.

### 7.2 دعم قارئات الشاشة

- إضافة `semanticLabel` لكل عنصر واجهة.
- استخدام `StitchSemantics` لوصف المحتوى الديناميكي (مثل تحديث الرصيد).

### 7.3 وضع التباين العالي (High Contrast)

- خيار في الإعدادات: يستبدل الألوان الأساسية بـ `#000000` و `#FFFFFF` مع الاحتفاظ بالذهبى كتحديد للعناصر الهامة.

### 7.4 وضع كبار السن (Senior Mode)

- تفعيل يدوي أو عند اكتشاف عمر > 65.
- تكبير الخط الأساسي إلى 24dp.
- إخفاء الرسوم البيانية الثانوية، وعرض 5 وظائف رئيسية فقط.

---

## 8. الاستجابة والتخطيط (Responsive Layout)

### 8.1 نقاط التوقف (Breakpoints)

| الشاشة | العرض | التخطيط |
|--------|-------|---------|
| هاتف صغير | < 360dp | عمود واحد، تباعد 8dp |
| هاتف عادي | 360-600dp | عمود واحد، تباعد 12dp |
| جهاز لوحي (تابلت) | 600-840dp | عمودان (شبكة مرنة) |
| كبير (iPad/Desktop) | > 840dp | 3 أعمدة أو أكثر، استخدام `StitchResponsiveGrid` |

### 8.2 استخدام `StitchLayoutBuilder` لتغيير عدد الأعمدة في شبكة الاختصارات:
- الهاتف: 2x3
- التابلت: 3x4
- سطح المكتب: 4x4

---

## 9. الحركات والانتقالات (Motion)

| الانتقال | المدة | المنحنى | الاستخدام |
|----------|-------|---------|-----------|
| الانتقال بين الشاشات | 300ms | Standard (Ease In Out) | دفع أفقي (من اليمين لليسار في RTL) |
| ظهور بطاقة من أسفل | 250ms | Decelerate Ease | تفاصيل المعاملة |
| إغلاق حوار | 200ms | Accelerate Ease | أي مودال |
| نبض زر عائم | تكرار كل 2 ثانية | – | لفت الانتباه إلى ميزة جديدة |

**تنفيذ Stitch:** `StitchPageTransition` مع `CupertinoPageRoute` أو `StitchHero` للحركات المشتركة.

---

## 10. الملفات المطلوبة للتصميم (Assets)

لضمان عمل التصميم على Google Stitch، يجب توفير الملفات التالية في المسارات الصحيحة:

| الملف | المسار | الوصف |
|-------|--------|-------|
| شعار بزة (متجه) | `assets/logo/beza_logo.svg` | أيقونة رئيسية، يجب أن تكون أحادية اللون للتكيف مع الوضع الليلي |
| أيقونات SVG | `assets/icons/` | 50+ أيقونة (تحويل، فواتير، ذهب، إعدادات، مستخدم، إلخ) |
| خط Cairo | `assets/fonts/Cairo/` | جميع الأوزان (Regular, Medium, SemiBold, Bold) |
| خط Inter | `assets/fonts/Inter/` | للأرقام والإنجليزية |
| ملفات Lottie | `assets/animations/` | مثل `success_checkmark.json`, `confetti.json`, `loading_spinner.json` |
| صور تجريبية | `assets/images/` | صور للبطاقات الترويجية، شعارات الشركاء |

---

## 11. تكامل الذكاء الاصطناعي في التصميم

### 11.1 توصيات مخصصة (AI Widget)
- **المكان:** أسفل الشاشة الرئيسية (كبطاقة أفقية قابلة للإغلاق).
- **المحتوى:** يتم تحديثه ديناميكياً من Vertex AI. مثال: "سعر الذهب منخفض اليوم، هل تريد شراء 1 جرام؟".
- **التفاعل:** زر "تذكير لاحقاً" أو "تنفيذ".

### 11.2 مساعد صوتي (Voice Assistant)
- أيقونة ميكروفون في `BezaAppBar`.
- تظهر موجة صوتية (Audio Wave) عند الاستماع.
- تُستخدم للأوامر السريعة (تحويل، دفع فاتورة، استعلام عن الرصيد).

### 11.3 تحليل سلوكي لتخصيص التخطيط
- **آلية:** يتم إرسال الأحداث (الضغطات، وقت البقاء على الشاشة) إلى BigQuery، ثم نموذج ML يُعيد ترتيب أيقونات الاختصارات حسب الاستخدام.
- **تأثير مرئي:** المستخدم يلاحظ أن الأيقونات التي يستخدمها كثيراً تنتقل إلى الواجهة الأمامية تلقائياً.

---

## 12. نموذج لشاشة رئيسية (Home Screen) مع كود Stitch افتراضي

```dart
// lib/features/home/home_screen.dart
import 'package:stitch/stitch.dart';
import '../../core/widgets/beza_balance_card.dart';
import '../../core/widgets/beza_transaction_tile.dart';

class HomeScreen extends StitchWidget {
  @override
  Widget build(BuildContext context) {
    final homeStore = StitchStoreProvider.of<HomeStore>(context);
    return StitchScaffold(
      appBar: BezaAppBar(title: 'الرئيسية', showNotifications: true),
      body: StitchRefreshIndicator(
        onRefresh: homeStore.loadData,
        child: SingleChildScrollView(
          child: StitchColumn(
            children: [
              BezaBalanceCard(
                balance: homeStore.balance,
                onHideToggle: homeStore.toggleBalanceVisibility,
                isVisible: homeStore.isBalanceVisible,
                quickActions: [
                  QuickAction(icon: Icons.send, label: 'تحويل', onTap: () => StitchNav.to('/transfer')),
                  QuickAction(icon: Icons.qr_code, label: 'شحن', onTap: () => StitchNav.to('/topup')),
                  QuickAction(icon: Icons.receipt, label: 'دفع', onTap: () => StitchNav.to('/bills')),
                ],
              ),
              StitchSizedBox(height: 16),
              StitchHorizontalScrollable(
                children: homeStore.smartCards.map((card) => BezaSmartCard(card)).toList(),
              ),
              StitchSizedBox(height: 24),
              StitchRow(
                mainAxisAlignment: MainAxisAlignment.spaceBetween,
                children: [
                  StitchText('أحدث المعاملات', style: StitchTextTheme.titleLarge),
                  StitchTextButton(child: StitchText('عرض الكل'), onPressed: () => StitchNav.to('/transactions')),
                ],
              ),
              StitchColumn(
                children: homeStore.recentTransactions.map((tx) => BezaTransactionTile(tx)).toList(),
              ),
            ],
          ),
        ),
      ),
      floatingActionButton: BezaAIAssistantFAB(),
      bottomNavigationBar: BezaBottomNavBar(selectedIndex: 0),
    );
  }
}
```

---

## 13. تعليمات لمصممي UI/UX (Figma + Stitch)

إذا كنت تستخدم Figma لتصميم الواجهات، يرجى اتباع الخطوات التالية لتسهيل النقل إلى Stitch:

1. **تثبيت Plugin** `Stitch for Figma` – يقوم بتحويل الإطارات (Frames) إلى StitchWidgets أولية.
2. **تسمية الطبقات (Layers)** وفقاً لأسماء المكونات – مثلاً `BezaPrimaryButton`, `BezaBalanceCard`.
3. **استخدام متغيرات الألوان** (Color Styles) المطابقة للوحة أعلاه.
4. **إنشاء أنماط نصية** (Text Styles) تطابق مقياس الخطوط.
5. **تصدير الأيقونات** كـ SVG (يفضل Flatten) ووضعها في مجلد `assets/icons`.
6. **اختبار التصميم مع اتجاه RTL** في Figma (يمكن عكس الإطار مؤقتاً).

---

## 14. معايير الجودة والمراجعة

قبل اعتماد أي تصميم، تأكد من:

- [ ] جميع النصوص قابلة للقراءة مع خلفيات Glassmorphism.
- [ ] المسافات بين العناصر لا تقل عن 8dp.
- [ ] تم اختبار الوضع الليلي يدوياً.
- [ ] الأزرار تعرض حالة "تحميل" (Loading state).
- [ ] تم إضافة `semanticLabel` لكل عنصر تفاعلي.
- [ ] لا يوجد استخدام للصور كخلفية للنصوص المهمة.

---

## 15. إصدارات مستقبلية

- **الإصدار 1.1:** إضافة دعم للواقع المعزز (AR) لمواقع الصراف الآلي.
- **الإصدار 2.0:** دمج محفظة العملات الرقمية للبنك المركزي (CBDC) عند إطلاقها.

---

*تم حفظ هذا الملف كمرجع تصميم (Design System) لمنصة بزة.*
