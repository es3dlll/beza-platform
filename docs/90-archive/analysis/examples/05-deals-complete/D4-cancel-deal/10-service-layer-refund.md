# 10 - RefundService كامل

_(أنظر 09-service-layer-deal.md — RefundService هو الخدمة الرئيسية)_

## تدفق الاسترجاع

```
1. تحقق من أن الصفقة قابلة للإلغاء
         │
2. اجلب جميع المستثمرين النشطين
         │
3. DB::transaction {
    ├── لكل مستثمر:
    │   ├── getWallet(investor)
    │   ├── increment(wallet, investment.amount)
    │   ├── Transaction::create(type: refund)
    │   └── update deal_investment → status: refunded
    │
    ├── deal.markAsCancelled(reason)
   }
         │
4. dispatch(DealCancelled)
         │
5. Return summary
```

## مثال

| المستثمر | المبلغ المسترجع |
|----------|----------------|
| أحمد | 10,000 USD |
| سارة | 25,000 USD |
| محمد | 15,000 USD |
| **المجموع** | **50,000 USD** |
