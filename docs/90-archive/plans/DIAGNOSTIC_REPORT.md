# تقرير تشخيصي — Beza Platform Architecture & Codebase Health

> التوقيع: 2026-05-30  
> النطاق: 30 وحدة نمطية، 903 ملفات PHP، 361 اختبارًا

---

## ملخص تنفيذي

| البعد | التقييم | التفاصيل |
|-------|---------|----------|
| عدد الوحدات | 30 (مُعلن: 31) | نقص وحدة — بالبحث: `TokenServiceProvider` غير موجود |
| README للوحدات | 0/30 | **صفر** وحدات لديها توثيق داخلي |
| ValueObjects | 0/30 | **لا يوجد** أي `ValueObjects/` على مستوى الوحدة |
| Actions | 0/30 | القالب الجديد يطلبها — غير موجودة |
| Policies | 0/30 | ABAC غير مفعل على مستوى الوحدة |
| Listeners | 1/30 | فقط Auth يعالج الأحداث داخليًا |
| G-006 متوافق | 2/30 | فقط CFE + Wallet يقتربان من القالب القياسي |

---

## 🔴 الأولوية الأولى: توحيد هيكلة الوحدات (G-006)

### المشكلة
30 وحدة، 30 هيكلة مختلفة. لا يوجد README واحد. بعض الوحدات تفتقد Models، Database، Events، Exceptions.

### الوحدات الأكثر تضررًا

| الوحدة | المشكلة |
|--------|---------|
| **Admin** | لا Models، لا Migrations، لا Events، لا Exceptions — 8 Controllers فقط |
| **USSD** | 4 ملفات فقط (Controller, Routes, Service, Test) |
| **Notification** | لا Events، لا Exceptions، لا Factories |
| **Escrow** | لا DTOs، لا Http/Requests |
| **Investments** | لا DTOs، لا Http/Requests، لا Repositories |
| **Marketplace** | لا DTOs، لا Http/Requests، لا Repositories |
| **Takaful** | لا DTOs، لا Http/Requests، لا Repositories |

### الإجراء
- فرض قالب G-006 على جميع الوحدات (بما في ذلك المجلدات الفارغة)
- إنشاء README.md لكل وحدة باستخدام القالب الموحد
- نقل `Http/Requests/` المنتشرة إلى `Requests/` موحد

---

## 🔴 الأولوية الثانية: كائنات القيمة المالية (G-004)

### المشكلة
0 وحدات تستخدم `App\Domain\ValueObjects\Money` بشكل إلزامي. النظام يعتمد على `bigint` في قاعدة البيانات ولكن الكود PHP قد يستخدم `int` مباشرة.

### التحقق المطلوب
- مسح جميع الـ Services: هل تتعامل مع `int` مباشرة أم عبر `Money` VO؟
- مسح جميع الـ DTOs: هل تقبل `int $amount` أم `Money $amount`؟
- التأكد من أن `Currency` VO يُستخدم لكل عملة

### الإجراء
- إنشاء `ValueObjects/` داخل كل وحدة مالية (Wallet, CFE, Ledger, Merchant, FX, إلخ)
- إضافة PHPStan rule: `no `int` type hint for monetary parameters in Services`
- فرض `Money::fromInt()` عند حدود الوحدة

---

## 🔴 الأولوية الثالثة: التواصل بين الوحدات (G-008)

### المشكلة
من 30 وحدة:
- 30 وحدة تُصدر Events
- **1 وحدة فقط** (Auth) لديها Listeners داخلية
- باقي الوحدات إما لا تستمع للأحداث أو تستمع عبر `App\Listeners\` العام

### مؤشرات الخطر
- إذا كانت Wallet تستدعي `NotificationService::sendSms()` مباشرة → انتهاك G-008
- إذا كان Merchant يستدعي `LedgerAccountService::post()` مباشرة → انتهاك G-002 + G-008

### الإجراء
- مسح جميع `use App\Modules\{X}\Services\` عبر الوحدات
- نقل الاستماع إلى داخل كل وحدة (`Listeners/`)
- توثيق Event Flow Diagram: أي وحدة تُصدر ماذا، وأي وحدة تستمع لماذا

---

## 🟡 الأولوية الرابعة: إعادة هيكلة `.opencode/`

### المشكلة
الهيكلة الحالية (13 مجلدًا) تختلف عن الهيكلة المطلوبة (00-STRATEGY → 99-GENERATED).

| الحالي | المطلوب |
|--------|---------|
| `architecture/` | `01-ARCHITECTURE/` |
| `engineering/` | `03-ENGINEERING/` |
| `features/` | `02-DOMAIN/` (جزئيًا) |
| `financial-core/` | `02-DOMAIN/` (جزئيًا) |
| `shared/` | موزع على 00, 06, 07 |
| `workflows/` | `99-GENERATED/` |
| — | `00-STRATEGY/` (جديد) |
| — | `07-I18N/` (جديد) |
| — | `99-GENERATED/` (جديد) |
| `plans/` | يحتفظ به |
| `tasks/` | يحتفظ به |

### الإجراء
- ترحيل الملفات حسب الهيكلة الجديدة
- إنشاء `INDEX.md` بفهرس قابل للبحث
- الحفاظ على روابط (symlinks) للمسارات القديمة مؤقتًا

---

## 🟡 الأولوية الخامسة: الاختبارات والتغطية

### المشكلة
| الوحدة | عدد الاختبارات | الحالة |
|--------|---------------|--------|
| Identity | 71 | ✅ جيد |
| Education | 6 | ⚠️ قليل |
| CFE | 5 | ⚠️ قليل جدًا للأهمية |
| Ledger | 5 | ⚠️ قليل جدًا للأهمية |
| Marketplace | 22 | ✅ جيد |
| IAM | 4 | ❌ غير كافٍ |
| Fraud | 3 | ❌ غير كافٍ |
| **Admin** | 8 | لـ 8 Controllers |
| **USSD** | 1 | ❌ حد أدنى |

### G-010 ينص على
1. ✅ Happy path لكل نقطة نهاية
2. ✅ Validation failure (422)
3. ✅ Auth failure (401)
4. ✅ Business rule violation (422/409)

### الثغرات الحرجة
- CFE (5 اختبارات لمحركات Posting/Fee/Hold/Reversal/Settlement) → غير كافٍ
- Ledger (5 اختبارات لـ Account/Hold/Journal/TrialBalance) → غير كافٍ
- IAM + Fraud → بحاجة مضاعفة

---

## ملخص الأولويات

| # | الأولوية | الجهد | الأثر | المنطقة |
|---|----------|-------|-------|---------|
| 1 | توحيد هيكلة الوحدات + README | 3 أيام | عالي جدًا | Engineering |
| 2 | فرض Value Objects المالية | 2 أيام | عالي | Financial Core |
| 3 | تصحيح التواصل بين الوحدات | يوم واحد | عالي | Architecture |
| 4 | إعادة هيكلة `.opencode/` | 4 ساعات | متوسط | Documentation |
| 5 | رفع تغطية الاختبارات | 5 أيام | عالي | Testing |

---

*أنتظر تعليماتك — أي أولوية تبدأ؟*
