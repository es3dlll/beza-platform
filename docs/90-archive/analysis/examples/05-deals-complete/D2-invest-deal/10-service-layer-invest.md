# 10 - InvestService كامل

_(أنظر 09-service-layer-deal.md — InvestService هو الخدمة الرئيسية لهذه العملية)_

## تدفق InvestService

```
1. تحقق من حالة الصفقة (active/filled)
         │
2. منع الاستثمار في صفقة المستخدم نفسه
         │
3. تحقق من الحد الأدنى (10 USD)
         │
4. تحقق من عدم تجاوز المبلغ المتبقي
         │
5. تحقق من رصيد المحفظة
         │
6. DB::transaction {
    ├── lockForUpdate(wallet)
    ├── decrement(wallet, amount)
    ├── incrementCurrentAmount(deal)
    ├── DealInvestment::create()
    └── if current_amount >= target_amount → status = filled
   }
         │
7. dispatch(InvestmentMade)
         │
8. Return response
```

## قائمة الاستثناءات

| الاستثناء | الشرط |
|-----------|-------|
| DealNotActiveException | الصفقة غير نشطة |
| DealFullyFundedException | الصفقة اكتملت بالفعل |
| CannotInvestInOwnDealException | المستثمر هو منشئ الصفقة |
| AmountExceedsRemainingException | المبلغ يتجاوز المتبقي |
| InsufficientBalanceException | رصيد غير كافٍ |
