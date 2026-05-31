# Localization & Internationalization

## Language Strategy

- **Primary**: Arabic (ar-SY) — Syrian Arabic dialect with Modern Standard Arabic fallback
- **Secondary**: English (en) — for international products and non-Arabic speakers
- **Future**: Kurdish (ku), Turkish (tr), Armenian (hy)

## RTL Layout

- Full right-to-left layout as default
- LTR only when English is selected
- Mixed RTL/LTR handling for phone numbers, prices, codes
- Direction-aware CSS (inline-start, inline-end)

## Number Formatting

| Locale | Number | Currency | Date | Time |
|---|---|---|---|---|
| ar-SY | ١٢٣٬٤٥٦ | ١٢٣٬٤٥٦ ل.س | ٢٩ مايو ٢٠٢٦ | ١٠:٣٠ ص |
| en | 123,456 | 123,456 SYP | May 29, 2026 | 10:30 AM |

## Translation Keys — Marketplace Module

```json
{
  "marketplace": {
    "title": "السوق",
    "search_placeholder": "ابحث عن منتج...",
    "categories": "التصنيفات",
    "top_up": "شحن رصيد",
    "gift_cards": "بطاقات هدايا",
    "digital_goods": "سلع رقمية",
    "internet_packages": "باقات نت",
    "bill_payment": "فواتير",
    "my_orders": "طلباتي",
    "wallet_balance": "رصيد المحفظة",
    "add_to_cart": "أضف إلى السلة",
    "out_of_stock": "نفد من المخزون",
    "insufficient_balance": "الرصيد غير كافٍ",
    "confirm_payment": "تأكيد الدفع",
    "order_success": "تم تأكيد الطلب",
    "copy_code": "نسخ الرمز",
    "send_gift": "إرسال هدية",
    "redeem_now": "استخدم الآن",
    "expires_on": "تنتهي في",
    "invalid_number": "رقم غير صحيح",
    "network_detected": "تم اكتشاف الشبكة: {network}",
    "topup_success": "تم شحن {amount} ل.س للرقم {number}",
    "delivery_instant": "توصيل فوري",
    "promo_code": "رمز الخصم",
    "invalid_promo": "رمز غير صالح",
    "order_history": "سجل الطلبات",
    "re_order": "إعادة الطلب"
  }
}
```

## Content Moderation

- All vendor-generated content (titles, descriptions) must pass through Arabic language validation
- Profanity filtering in Arabic and English
- Price display always in Arabic numerals (١٢٣) for Arabic locale
- Product slugs transliterated from Arabic to Latin characters
