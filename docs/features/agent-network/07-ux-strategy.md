# Agent Network UX Strategy

## Design Principles
1. **سرعة النقد** — كل معاملة يجب أن تتم بأسرع من عد النقود يدوياً (هدف: <30 ثانية للمعاملة النموذجية)
2. **صفر تدريب** — وكيل يفتح التطبيق لأول مرة يجب أن ينجز معاملة خلال دقيقتين بدون مساعدة
3. **الثقة في كل خطوة** — عرض المبلغ كبير وواضح، تأكيد قبل التنفيذ، إيصال مطبوع فوراً
4. **تعمل بدون إنترنت** — جميع العمليات الحرجة (إيداع، سحب) تعمل كلياً أو جزئياً بدون اتصال
5. **مناسبة لمحدودي القراءة** — أيقونات كبيرة، ألوان واضحة، إرشادات صوتية فوق النص
6. **العربية أولاً** — كل شيء بالعربية، من اليمين إلى اليسار، الإنجليزية ثانوية فقط
7. **مقاومة للأخطاء** — تصميم يمنع الأخطاء قبل حدوثها (تأكيد مزدوج، تحقق من المبلغ، حد أقصى واضح)

## Information Architecture (Agent POS App)
```
تطبيق وكيل Beza (Agent POS)
  ├── الشاشة الرئيسية (Dashboard)
  │   ├── رصيد الصندوق (رقم كبير — أخضر إذا >500K، أصفر إذا 100-500K، أحمر إذا <100K)
  │   ├── أزرار سريعة (كبيرة جداً):
  │   │   ├── 🟢 إيداع (Cash-in) — زر أخضر كبير في الأعلى
  │   │   └── 🔴 سحب (Cash-out) — زر أحمر كبير في الأسفل
  │   ├── إحصائيات اليوم (عدد المعاملات، إجمالي الإيداع، إجمالي السحب، العمولة المقدرة)
  │   └── إشعارات (رصيد منخفض، طلب تحويل، إعلان جديد)
  │
  ├── إيداع نقدي (Cash-in Flow)
  │   ├── إدخال رقم الهاتف (لوحة أرقام كبيرة)
  │   ├── إدخال رمز التحقق (من رسالة SMS)
  │   ├── إدخال المبلغ (لوحة أرقام — أزرار كبيرة لأصابع اليد)
  │   ├── تأكيد (مراجعة المبلغ + اسم الزبون)
  │   └── نجاح/فشل (نتيجة + إيصال)
  │
  ├── سحب نقدي (Cash-out Flow)
  │   ├── إدخال رقم الهاتف
  │   ├── إدخال رمز التحقق
  │   ├── إدخال المبلغ
  │   ├── عرض الرسوم (شفاف — المبلغ + رسوم السحب)
  │   ├── تأكيد الزبون (إدخال الرقم السري على POS أو USSD)
  │   ├── بصمة (فوق 500,000 ل.س)
  │   └── نجاح/فشل (نتيجة + إيصال)
  │
  ├── إدارة الصندوق (Float Management)
  │   ├── الرصيد الحالي
  │   ├── تعبئة الصندوق (من محفظتي، من وكيل، إيداع نقدي في المركز)
  │   ├── سجل الحركات (إيداع نقدي، سحب، تعبئة، عمولة)
  │   └── تنبيهات الصندوق (تعديل حد الإنذار)
  │
  ├── العمليات (History)
  │   ├── معاملات اليوم (قائمة زمنية)
  │   ├── معاملات الأمس
  │   ├── معاملات هذا الشهر
  │   ├── بحث حسب الرقم أو المبلغ أو التاريخ
  │   └── تصدير (طباعة أو PDF أو Excel)
  │
  ├── العمولات (Commissions)
  │   ├── عمولات اليوم
  │   ├── عمولات هذا الشهر
  │   ├── رصيد العمولات المستحقة (رصيد لم يصرف بعد)
  │   └── سجل التسويات (تواريخ ومبالغ التسويات)
  │
  └── المزيد (More)
      ├── الملف الشخصي (الاسم، الرقم، التصنيف)
      ├── الإعدادات (PIN، اللغة، الصوت، الطباعة)
      ├── المساعدة (أسئلة شائعة، فيديوهات تدريب)
      ├── اتصل بالدعم (زر اتصال مباشر)
      └── عن التطبيق (الإصدار، التحديثات)
```

## Key Screens & Their Goals

### Agent POS Home
- **Business Goal**: Enable fast transaction initiation, maintain float awareness
- **Psychological Goal**: Agent feels in control, confident, and informed
- **Trust Signal**: Large float display, real-time updates, clear success animations
- **Layout**: Float balance (hero) + two giant action buttons (Cash-in green / Cash-out red) + daily stats + alerts

### Cash-in Screen
- **Business Goal**: Complete deposit in <20 seconds with zero errors
- **Psychological Goal**: Agent feels efficient, professional, trusted
- **Trust Signal**: Customer verification via SMS code, large amount display, printed receipt
- **Layout**: Phone input → verification → amount → confirmation → success

### Float Management Screen
- **Business Goal**: Enable proactive float maintenance, reduce service interruptions
- **Psychological Goal**: Agent feels prepared, never turns away a customer
- **Trust Signal**: Real-time balance, low-balance alerts, clear top-up options
- **Layout**: Balance card + top-up actions + recent float movements

## Transaction States (POS Display)
| State | Visual | Action Available |
|-------|--------|------------------|
| Processing | Spinner + "جاري تنفيذ المعاملة..." | Wait |
| Completed | Green checkmark + "تمت المعاملة بنجاح" | Print receipt |
| Failed | Red X + "فشلت المعاملة" + reason | Retry / Cancel |
| Pending | Amber clock + "قيدة التنفيذ" (offline) | Wait for sync |
| Offline Queued | Blue cloud + "ستتم المعاملة عند الاتصال" | Continue working |
| Disputed | Red warning + "معاملة محل نزاع" | Call support |

## Empty States
| Screen | Empty State | CTA |
|--------|------------|-----|
| Dashboard (new agent) | "لا توجد معاملات بعد" | "ابدأ أول إيداع" |
| History | "لا توجد معاملات في هذا التاريخ" | "عرض كل المعاملات" |
| Commissions | "لا توجد عمولات بعد" | "ابدأ بالعمل لكسب العمولات" |
| Float movements | "لا توجد حركات صندوق" | — |
| Notifications | "لا توجد إشعارات" | — |
