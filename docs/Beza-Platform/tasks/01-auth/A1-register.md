# A1 - تسجيل مستخدم جديد (Register)

## الوصف
إنشاء حساب جديد في المنصة. يتم إنشاء مستخدم + محفظتين (SYP/USD) تلقائياً.

## المدخلات
| الحقل | النوع | المتطلبات |
|-------|-------|-----------|
| name | string | max:255 |
| phone | string | unique, regex:/^09[0-9]{8}$/ |
| password | string | min:8, confirmed |
| pin_code | string | size:4, confirmed |

## المخرجات
| الحقل | الوصف |
|-------|-------|
| user | id, name, phone, status |
| wallets | مصفوفة من محفظتين (SYP + USD) |
| token | Bearer token للمصادقة |

## سير العمل
1. Validate inputs
2. DB::beginTransaction()
3. User::create — uuid, name, phone, password (Hash), pin_code (Hash), status:pending
4. Wallet::create × 2 — SYP (رصيد 0) + USD (رصيد 5 هدية ترحيب)
5. DB::commit()
6. NotificationService::sendWelcomeSms
7. إنشاء Sanctum token
8. Response success

## قواعد العمل
- phone يجب أن يبدأ بـ 09 ويتكون من 10 أرقام
- هدية الترحيب 5 USD تضاف تلقائياً لمحفظة USD
- حالة الحساب تبقى pending حتى إكمال KYC
- يتم إرسال رسالة SMS ترحيبية

## جداول قاعدة البيانات
- users
- wallets

## API Endpoint
`POST /api/v1/auth/register`

## واجهات المستخدم
- Flutter: RegisterScreen
- React SPA: RegisterPage

## اختبارات
- تسجيل بمعلومات صحيحة ← 200
- تسجيل برقم هاتف موجود ← 400
- تسجيل بكلمة سر قصيرة ← 422
- تسجيل برمز PIN غير صحيح ← 422
- التحقق من وجود محفظتين بعد التسجيل
