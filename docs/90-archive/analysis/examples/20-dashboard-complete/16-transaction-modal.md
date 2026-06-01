# 16 - شاشة المعاملات والفاتورة (Transactions + Invoice Modal)

## Transaction — الصفحة الكاملة (`/transactions`)

### التجميع حسب التاريخ

```jsx
const groups = ['اليوم', 'أمس', 'هذا الأسبوع', 'الأسبوع الماضي'];

// كل معاملة لها حقل date:
// 'اليوم', 'أمس', 'هذا الأسبوع', 'الأسبوع الماضي'
```

### شريط الإحصائيات

```jsx
<div className="grid grid-cols-2 gap-3 mb-6">
  <div>إجمالي الوارد: XX ل.س</div>   <!-- أخضر -->
  <div>إجمالي الصادر: XX ل.س</div>   <!-- أحمر -->
</div>
```

### كل معاملة هي زر

```jsx
<button onClick={() => setSelected(tx)}>
  <!-- أيقونة (in=أخضر / out=أحمر) -->
  <!-- اسم + وقت -->
  <!-- المبلغ -->
</button>
```

## مودال الفاتورة (Invoice Modal)

عند النقر على معاملة، تظهر فاتورة احترافية:

```
┌─────────────────────────────────┐
│         SAKK                    │  ← شعار ذهبي
│      محفظة ذهبية                │
│  ───── ✦ ─────                  │  ← خط ذهبي
│                                 │
│       +٥٠٬٠٠٠ ل.س               │  ← المبلغ (hero)
│                                 │
│  ───── ✦ ─────                  │  ← خط ذهبي
│                                 │
│  المستلم: أحمد الخطيب           │
│  المرجع: TXN-28471              │  ← مع زر نسخ
│  التاريخ: ٢٧ مايو ٢٠٢٦         │
│  الهاتف: +96390000001           │
│  الملاحظة: تحويل عادي           │
│                                 │
│  ───── ✦ ─────                  │
│                                 │
│  الحالة: ● مكتمل                │  ← animate-pulse
│                                 │
│  ┌─────────────────────────┐    │
│  │  █▀▀▀▀█▀▀▀█▀▀▀█▀▀▀█▀▀  │    │  ← باركود مزيف
│  │  █ ▀▀▀ █ ▀▀▀ █ ▀▀▀ █ ▀ │    │
│  └─────────────────────────┘    │
│                                 │
│  شكراً لاستخدامك SAKK           │
└─────────────────────────────────┘
```

### هيكل المودال

```jsx
{selected && (
  <>
    {/* قناع خلفي */}
    <div className="fixed inset-0 z-40" onClick={() => setSelected(null)} />

    {/* المودال */}
    <div className="fixed inset-0 z-50 flex items-center justify-center p-4">
      <div className="w-full max-w-md max-h-[90vh] overflow-y-auto rounded-3xl p-6">

        {/* Header: SAKK */}
        <div className="text-center mb-6">
          <WalletIcon className="w-10 h-10 mx-auto" />  {/* ذهبي */}
          <h2 className="text-xl font-bold">SAKK</h2>
          <p>محفظة ذهبية</p>
        </div>

        {/* Gold decorative line */}
        <div className="h-px" style={{background:'linear-gradient(90deg, transparent, #F5A623, transparent)'}} />

        {/* Amount hero */}
        <p className="text-4xl font-extrabold text-center">{amount}</p>

        {/* Gold line */}
        <div className="h-px" style={{background:'linear-gradient(90deg, transparent, #F5A623, transparent)'}} />

        {/* Details */}
        <div>المستلم: {name}</div>
        <div>المرجع: {ref} ← زر نسخ</div>
        <div>التاريخ: ...</div>
        <div>الهاتف: ...</div>
        <div>الملاحظة: ...</div>

        {/* Status */}
        <div>الحالة: ● مكتمل</div>

        {/* Barcode (decorative) */}
        <div className="barcode-placeholder" />

        {/* Footer */}
        <p>شكراً لاستخدامك SAKK</p>

        {/* Close */}
        <button onClick={() => setSelected(null)}>إغلاق</button>
      </div>
    </div>
  </>
)}
```

### تصميم الفاتورة

| العنصر | التفاصيل |
|--------|----------|
| الخلفية | `#080c1a` مع ظل |
| الألوان | ذهبي للشعار والخطوط، أبيض شفاف للنصوص |
| الخطوط الذهبية | `linear-gradient(90deg, transparent, #F5A623, transparent)` |
| زر الإغلاق | XMarkIcon في الأعلى |
| responsiveness | `max-w-md` + `p-4` |
