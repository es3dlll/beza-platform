# 12. نظام الوكلاء (مكاتب الصرافة - Agents)

## 12.1 آلية عمل السحب النقدي (عبر الوكيل)

1. العميل يفتح تطبيق Beza ويختار "سحب نقدي" ويحدد المبلغ
2. النظام يولد رمزاً مكوناً من 6 أرقام صالح لمدة ساعة
3. العميل يذهب إلى أقرب وكيل ويعطيه الرمز والمبلغ
4. الوكيل يفتح تطبيق الوكيل، يضغط "سحب نقدي"، يمسح رمز QR أو يدخل الرقم
5. النظام يتحقق من صلاحية الرمز ورصيد العميل
6. العميل يدخل PIN للتأكيد
7. الوكيل يصرف النقد للعميل
8. يتم خصم المبلغ من محفظة العميل، وإضافة المبلغ إلى رصيد الوكيل النقدي
9. كلا الطرفين يتلقى إشعاراً

## 12.2 هيكل تطبيق الوكيل (Flutter)

```
agent-app/
├── screens/
│   ├── AgentDashboard.dart
│   ├── CashInScreen.dart
│   ├── CashOutScreen.dart
│   ├── TransactionHistoryScreen.dart
│   └── ProfileScreen.dart
├── services/
│   └── AgentApiService.dart
└── models/
    └── AgentTransaction.dart
```

## 12.3 واجهة الوكيل الرئيسية

```dart
class AgentDashboard extends StatelessWidget {
  @override
  Widget build(BuildContext context) {
    return Scaffold(
      body: Padding(
        padding: EdgeInsets.all(16),
        child: Column(
          children: [
            Card(
              child: ListTile(
                title: Text('رصيد نقدي SYP'),
                trailing: Text('${agent.cashBalanceSyp} ل.س'),
              ),
            ),
            Card(
              child: ListTile(
                title: Text('رصيد نقدي USD'),
                trailing: Text('\$${agent.cashBalanceUsd}'),
              ),
            ),
            SizedBox(height: 20),
            Row(
              children: [
                Expanded(child: ElevatedButton.icon(
                  onPressed: () => scanQrForCashOut(),
                  icon: Icon(Icons.qr_code_scanner),
                  label: Text('سحب نقدي')
                )),
                SizedBox(width: 10),
                Expanded(child: ElevatedButton.icon(
                  onPressed: () => cashInForm(),
                  icon: Icon(Icons.attach_money),
                  label: Text('إيداع نقدي')
                )),
              ],
            ),
            SizedBox(height: 20),
            Expanded(child: RecentAgentTransactions()),
          ],
        ),
      ),
    );
  }
}
```
