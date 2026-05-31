# نظام الإشعارات — Notification System

## Notification Types

### Transactional Notifications (High Priority)
| Trigger | Channel | Timing | Arabic Template |
|---------|---------|--------|-----------------|
| Application submitted | Push + In-App | Instant | "تم استلام طلب التمويل رقم {id}. سنقوم بإعلامك عند الانتهاء من التقييم." |
| Application approved | Push + SMS | Instant | "🎉 تمت الموافقة على طلب التمويل! المبلغ: {amount} ل.س. العرض صالح لمدة 7 أيام." |
| Application rejected | Push + In-App | Instant | "نأسف، لم تتم الموافقة على طلب التمويل. السبب: {reason}. يمكنك التقديم مرة أخرى بعد {days} يوم." |
| Offer accepted | Push + In-App | Instant | "تم قبول العرض! جاري تجهيز العقد والصرف." |
| Disbursed | Push + SMS | Instant | "✅ تم صرف مبلغ {amount} ل.س. أول دفعة مستحقة في {date}." |
| Payment received | Push + In-App | Instant | "تم استلام دفعة {number} من {total} بمبلغ {amount} ل.س. ✓" |
| Payment failed | Push + SMS | On failure | "تعذر تحصيل الدفعة. الرصيد غير كافٍ. يرجى إيداع المبلغ قبل {date}." |
| Overdue (Day 1) | Push + SMS | 08:00 AM | "تنبيه: لديك دفعة متأخرة بمبلغ {amount} ل.س. يرجى السداد لتجنب رسوم التأخير." |
| Overdue (Day 7) | Push + SMS + Call | 10:00 AM | "مذكّرة: متأخرات {amount} ل.س. لمدة {days} يوم. اتصل بنا على {phone} لمناقشة الخيارات." |
| Default | SMS + Email | 08:00 AM | "تم تصنيف حسابك كمتأخر. يرجى الاتصال بقسم التحصيل: {phone}" |
| Restructure approved | Push + SMS | Instant | "تمت الموافقة على إعادة الجدولة. القسط الجديد: {amount} ل.س. شكراً لتعاونك." |

### Promotional Notifications (Medium Priority)
| Trigger | Channel | Arabic Template |
|---------|---------|-----------------|
| New product available | Push | "تعرف على منتج التمويل الجديد: {productName}" |
| Pre-approved offer | Push | "تهانينا! أنت مؤهل مسبقاً للحصول على تمويل حتى {amount} ل.س." |
| Credit score improvement | Push | "درجة الائتمان الخاصة بك ارتفعت إلى {score}! أحسنت! 🎉" |
| Financing education tip | Push | "هل تعلم؟ قرض حسن هو قرض بدون فائدة متوافق مع الشريعة." |

### Admin Notifications
| Trigger | Channel | Arabic Template |
|---------|---------|-----------------|
| New application | Push | "طلب تمويل جديد: {name} - {amount} ل.س - {product}" |
| Application > 24h pending | Push | "تنبيه: الطلب {id} معلق لأكثر من 24 ساعة." |
| High-value application | Push + Email | "طلب بقيمة كبيرة: {name} - {amount} ل.س. يرجى المراجعة." |
| Collection required | Push | "تحصيل: {name} - {amount} ل.س - متأخر {days} يوم." |
| Portfolio threshold breach | Push + Email | "⚠️ مؤشر {metric} تجاوز الحد المسموح: {value}" |

## Scheduling Rules
```typescript
const notificationSchedule = {
  payment_reminder: { beforeDue: [{ days: -3, time: '09:00' }, { days: -1, time: '09:00' }] },
  overdue: {
    day1: { time: '08:00', channel: ['push', 'sms'] },
    day3: { time: '08:00', channel: ['push', 'sms'] },
    day7: { time: '10:00', channel: ['push', 'sms', 'call'] },
    day14: { time: '10:00', channel: ['push', 'sms', 'call'] },
    day30: { time: '08:00', channel: ['sms', 'email'] },
    day60: { time: '08:00', channel: ['sms', 'email'] },
    day90: { time: '08:00', channel: ['sms', 'email'] },
  },
  late_fee_charity: { quarterly: { month: [3, 6, 9, 12], day: 15, channel: ['in-app'] } },
};
```

## Opt-out Rules
- Transactional notifications: **cannot** opt out
- Promotional notifications: **can** opt out in Settings > Notifications
- Overdue/critical: **cannot** opt out
