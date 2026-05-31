# T7 - سحب نقدي من وكيل (Agent Cash Out)

## الوصف
سحب نقدي عبر مكاتب الصرافة (الوكلاء). المستخدم يولد رمزاً، والوكيل يصرف النقد.

## جزء أ: المستخدم يولد رمز السحب

### المدخلات
| الحقل | النوع |
|-------|-------|
| amount | decimal, min:1 |
| currency | enum: SYP, USD |

### سير العمل (User Side)
1. التحقق من رصيد كافٍ
2. تجميد المبلغ: balance -= amount, frozen_balance += amount
3. توليد رمز عشوائي 6 أرقام
4. إنشاء AgentTransaction (type: cash_out, status: pending)
5. Response: رمز السحب (صالح 1 ساعة)

### API Endpoint
`POST /api/v1/withdraw/agent/create-code`

## جزء ب: الوكيل يصرف الرمز

### المدخلات
| الحقل | النوع |
|-------|-------|
| reference_code | string, size:6 |
| pin | string (PIN العميل), size:4 |

### سير العمل (Agent Side)
1. البحث عن AgentTransaction بـ reference_code حيث status = pending
2. التحقق من PIN العميل
3. التحقق من رصيد العميل (frozen_balance)
4. DB::beginTransaction()
5. خصم frozen_balance
6. إضافة cash_balance للوكيل
7. تحديث AgentTransaction → completed
8. إنشاء Transaction رئيسي
9. DB::commit()
10. إشعار الطرفين

### API Endpoint
`POST /api/v1/agent/cash-out`

## قواعد العمل
- رمز السحب صالح لمدة 60 دقيقة
- رمز السحب للاستخدام لمرة واحدة
- عمولة الوكيل: 1% من المبلغ (تخصم من العميل)
- رسوم السحب: 1% (بحد أدنى 1 USD)

## جداول قاعدة البيانات
- agent_transactions (type: cash_out)
- wallets (balance, frozen_balance)
- agents (cash_balance_syp, cash_balance_usd)
- transactions (type: agent_cash_out)

## واجهات المستخدم
- Flutter: WithdrawCodeScreen, AgentCashOutScreen
- React SPA: WithdrawPage

## اختبارات
- إنشاء رمز سحب ← 200 (مع رمز)
- صرف رمز صحيح ← 200
- صرف رمز منتهي الصلاحية ← 400
- صرف برمز غير موجود ← 404
- إدخال PIN خاطئ ← 400
- صرف الرمز مرتين ← 400 (مستخدم)
