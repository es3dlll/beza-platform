# C2 - إدارة البطاقة

## الوصف
تجميد/إلغاء تجميد، تغيير PIN، تعيين حدود، تصريح سفر.

## تجميد البطاقة

### المدخلات
| الحقل | النوع |
|-------|-------|
| card_id | id |
| action | enum: freeze, unfreeze |

### API Endpoint
`POST /api/v1/cards/{id}/freeze`
`POST /api/v1/cards/{id}/unfreeze`

## تغيير PIN

### المدخلات
| الحقل | النوع |
|-------|-------|
| card_id | id |
| current_pin | string, size:4 |
| new_pin | string, size:4 |

### API Endpoint
`POST /api/v1/cards/{id}/pin`

## تعيين حدود

### المدخلات
| الحقل | النوع |
|-------|-------|
| card_id | id |
| daily_limit | decimal |
| monthly_limit | decimal |
| per_transaction_limit | decimal |

### API Endpoint
`POST /api/v1/cards/{id}/limits`

## تصريح سفر

### المدخلات
| الحقل | النوع |
|-------|-------|
| card_id | id |
| countries | array (رمز الدولة) |
| from_date | date |
| to_date | date |

### API Endpoint
`POST /api/v1/cards/{id}/travel-permit`

## قواعد العمل
- البطاقة المجمدة لا تعمل لأي معاملة
- تغيير PIN يتطلب PIN الحالي
- الحدود الافتراضية: 500 USD/day, 3000 USD/month
- تصريح السفر مؤقت (حسب التواريخ المدخلة)

## جداول قاعدة البيانات
- virtual_cards (status, daily_limit, monthly_limit, per_transaction_limit)
- card_travel_permits

## اختبارات
- تجميد بطاقة ← 200
- معاملة على بطاقة مجمدة ← 403
- تغيير PIN ← 200
- تغيير PIN خاطئ ← 400
- تعيين حدود ← 200
- إضافة تصريح سفر ← 200
