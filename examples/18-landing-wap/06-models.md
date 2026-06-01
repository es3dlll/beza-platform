# Models المستخدمة — WAP

WAP يعيد استخدام Models الموجودة — لا ينشئ جديدة:

```php
// app/Models/User.php — موجود
User::find($id);           // معلومات المستخدم + دوره
User::where('role', 'agent')->get();   // وكلاء

// app/Models/Wallet.php — موجود
Wallet::where('user_id', $id)->get();  // محافظ المستخدم

// app/Modules/Transaction/Models/Transaction.php — موجود
Transaction::where('sender_wallet_id', $wallet->id)
    ->orWhere('receiver_wallet_id', $wallet->id)
    ->latest()
    ->paginate(20);
```

> **القاعدة:** لا تنشئ Models مكررة. إذا احتجت querys خاص بـ WAP، استخدم `Scopes` أو `Local Query Macros`.
