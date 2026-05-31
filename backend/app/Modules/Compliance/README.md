# وحدة الامتثال ومكافحة الاحتيال (Fraud & Compliance Core)

## الهدف
مراقبة جميع المعاملات المالية في الوقت الفعلي، تطبيق قواعد الكشف عن الاحتيال، فحص القوائم المحظورة، إدارة الحالات الرقابية، وتوليد تنبيهات فورية مع حظر وقائي.

## المبادئ
- **قراءة فقط**: المحرك يقرأ الأحداث فقط، لا يعدّل أرصدة ولا يحذف سجلات
- **غير متزامن**: لا تأثير على سرعة المعاملة الأساسية
- **سجل تدقيق**: كل تقييم قاعدة يُسجل بشكل غير قابل للتعديل

## التدفق الرئيسي
1. TransactionCompleted → TransactionMonitorListener → Rule Engine → RiskScore
2. RiskScore < MEDIUM: مراقبة فقط
3. RiskScore MEDIUM: ComplianceReviewRequired + مؤقت 24 ساعة
4. RiskScore ≥ HIGH: AutoBlockTriggered → تعليق حساب فوري
5. Case فتح → مراجعة → تصنيف → إغلاق

## حالات الحالة الرقابية
OPEN → UNDER_REVIEW → ESCALATED
                      → RESOLVED_FALSE_POSITIVE
                      → RESOLVED_TRUE_POSITIVE
                      → CLOSED
