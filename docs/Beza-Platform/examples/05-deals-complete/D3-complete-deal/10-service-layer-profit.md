# 10 - ProfitDistributionService كامل

_(أنظر 09-service-layer-deal.md — ProfitDistributionService هو الخدمة الرئيسية)_

## تدفق توزيع الأرباح

```
1. تحقق من أن الصفقة قابلة للإتمام
         │
2. احسب إجمالي الربح: target_amount × profit_actual / 100
         │
3. اجلب جميع المستثمرين النشطين
         │
4. DB::transaction {
    ├── لكل مستثمر:
    │   ├── احسب حصة الربح:
    │   │   ratio = investment.amount / total_invested
    │   │   profit_share = ratio × total_profit
    │   ├── increment(wallet, profit_share)
    │   ├── Transaction::create(type: investment_profit)
    │   └── update deal_investment.profit_earned
    │
    ├── deal.markAsCompleted()
   }
         │
5. dispatch(DealCompleted)
         │
6. Return summary
```

## مثال حساب الأرباح

| المستثمر | المبلغ | النسبة | الربح (15%) |
|----------|--------|--------|-------------|
| أحمد | 10,000 USD | 20% | 1,500 USD |
| سارة | 25,000 USD | 50% | 3,750 USD |
| محمد | 15,000 USD | 30% | 2,250 USD |
| **المجموع** | **50,000 USD** | **100%** | **7,500 USD** |
