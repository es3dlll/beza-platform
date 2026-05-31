# وحدة شبكة الوكلاء وإدارة السيولة (Agent & Liquidity Network Core)

## الهدف
إدارة هوية الوكلاء المعتمدين، توزيع نقاط الخدمة، مراقبة السيولة النقدية والرقمية، حساب العمولات المتدرجة، التسوية اليومية، والامتثال للوائح الوكلاء المعتمدين.

## المبادئ
- **لا استدعاءات مباشرة بين الوحدات**: كل تفاعل مع Wallet، Ledger، أو Compliance عبر EventBus
- **السيولة محمية**: لا تنفيذ عند انخفاض السيولة عن الحد الأدنى
- **العمولات آلية**: حسب المستوى ونوع العملية
- **التسوية يومية**: مع سجل تدقيق كامل

## التدفق الرئيسي
1. تسجيل وكيل → AgentActivated → تعيين حد السيولة
2. تنفيذ عملية → validateTransaction → FloatBalance → AgentTransactionValidated
3. إتمام عملية → AgentTransactionCompleted → FloatUpdated + CommissionCalculated
4. التسوية → TriggerAgentSettlement → LedgerSettlementTransfer
5. الامتثال → AgentSuspendedCompliance → تجميد فوري
