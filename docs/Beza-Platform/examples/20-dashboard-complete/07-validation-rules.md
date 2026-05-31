# 07 - قواعد التحقق من المدخلات (Validation Rules)

## نموذج الإرسال (Send Bottom Sheet)

| الحقل | النوع | القواعد | سبب الرفض |
|-------|-------|---------|-----------|
| `to_phone` | `tel` | Required | رقم المستلم مطلوب |
| `amount` | `number` | Required, > 0 | المبلغ يجب أن يكون أكبر من 0 |
| `currency` | `select` | SYP / USD | عملة غير مدعومة |
| `pin` | `password` | Required, maxLength=4 | PIN مطلوب لتأكيد العملية |
| `description` | `textarea` | Optional | - |

## نموذج الصرف (Exchange Widget)

| الحقل | النوع | القواعد | سبب الرفض |
|-------|-------|---------|-----------|
| `amount` | `text` + `inputMode=decimal` | Required, > 0, أرقام فقط | المبلغ يجب أن يكون رقماً صحيحاً |
| `currency` | `button` toggle | USD → SYP أو SYP → USD | - |
| `rate` | auto | يُجلب من API | - |

## حماية المدخلات (Input Sanitization)

```jsx
// منع الأحرف غير الرقمية في حقل المبلغ (Exchange)
onChange={(e) => setExAmt(e.target.value.replace(/[^0-9.]/g, ''))}

// تعطيل الزر أثناء التحميل أو إذا كانت الحقول فارغة
disabled={sendLoading || !sendForm.to_phone || !sendForm.amount || !sendForm.pin}
disabled={exSending || !exAmt || Number(exAmt) <= 0}
```

## رسائل الخطأ (Error Messages)

| السيناريو | الرسالة |
|-----------|---------|
| تحويل ناجح | "تم التحويل بنجاح" (رسالة خضراء) |
| تحويل فاشل | رسالة من السيرفر (حمراء) |
| صرف ناجح | "تم الصرف بنجاح ✓" مع تفاصيل |
| صرف فاشل | "عذراً، فشلت العملية" |
| PIN خاطئ | "PIN غير صحيح" (من السيرفر) |
