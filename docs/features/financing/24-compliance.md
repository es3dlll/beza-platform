# الامتثال الشرعي والتنظيمي — Sharia & Regulatory Compliance

## 1. Sharia Compliance Framework

### Sharia Governance
| Role | Responsibility | Appointed By |
|------|---------------|--------------|
| Sharia Board (3 scholars) | Approve products, review contracts, annual audit | Board of Directors |
| Internal Sharia Auditor | Monthly compliance review | Sharia Board |
| Sharia Compliance Officer | Day-to-day compliance oversight | CEO |

### Sharia Rulings (فتاوى)

#### Ruling 1: Qard Hasan Permissibility
```
الحكم: قرض حسن جائز شرعاً
الدليل: قال تعالى "مَنْ ذَا الَّذِي يُقْرِضُ اللَّهَ قَرْضًا حَسَنًا"
الشروط:
  1. لا زيادة على رأس المال (بدون فائدة)
  2. الرسوم الإدارية تغطي التكلفة الفعلية فقط
  3. لا يجوز اشتراط غرامة تأخير كدخل للجهة المقرضة
```

#### Ruling 2: Murabaha Permissibility
```
الحكم: المرابحة جائزة شرعاً
الشروط:
  1. تملك الجهة الممولة للسلعة قبل بيعها للعميل
  2. الإفصاح الكامل عن ثمن الشراء ومبلغ الربح
  3. الربح ثابت لا يتغير طوال مدة العقد
  4. تحمل الجهة الممولة مخاطر الملكية بين الشراء والبيع
  5. العقدان منفصلان: عقد شراء من المورد وعقد بيع للعميل
```

#### Ruling 3: Late Fee to Charity
```
الحكم: غرامة التأخير تذهب للصدقات وليست دخلاً للشركة
الأساس:
  1. لا يجوز للدائن أن يستفيد من تأخير المدين
  2. الغرامة رادع وليست مصدر دخل
  3. توزع الأموال المجمعة على المؤسسات الخيرية المسجلة
  4. يجب توثيق جميع التحويلات الخيرية للتدقيق الشرعي
```

### Sharia Compliance Checklist
| Requirement | Status | Evidence |
|-------------|--------|----------|
| No interest (riba) on any product | ✅ | Product specs confirm 0% APR |
| Profit disclosure in Murabaha | ✅ | Contract template includes cost price |
| Two-contract structure (Murabaha) | ✅ | Purchase from supplier + sale to customer |
| Ownership risk (Murabaha) | ✅ | Beza owns item before sale to customer |
| Late fees to charity | ✅ | Separate charity liability account |
| Admin fees = actual cost | ✅ | Fee schedule approved by Sharia board |
| No compounding | ✅ | Fixed profit, no late fee compounding |
| Gharar avoidance | ✅ | All terms disclosed upfront |
| Contract in Arabic | ✅ | Primary contract language is Arabic |
| Sharia audit trail | ✅ | Immutable ledger for all contracts |

---

## 2. Central Bank of Syria (CBS) Compliance

### Regulatory Framework
| Regulation | Requirement | Beza Compliance |
|------------|-------------|-----------------|
| Consumer Protection Law 2019 | Full disclosure of terms, right to cancel within 7 days | ✅ Offer screen shows all terms, cooling-off period |
| AML/CFT Law 2013 | KYC, transaction monitoring, suspicious activity reporting | ✅ KYC Level 2 required, transaction monitoring |
| Electronic Payment Law 2020 | Digital contracts validity, e-signature recognition | ✅ E-signature with OTP verification |
| Data Protection Law 2022 | User consent, data minimization, right to deletion | ✅ Opt-in consent, data retention policy |
| Lending Regulations | Capital adequacy, provisioning, reporting | ✅ CAR > 12%, monthly CBS reports |

### Capital Adequacy Requirements
```yaml
cbs_requirements:
  minimum_capital: SYP 5,000,000,000
  capital_adequacy_ratio: 
    minimum: 12%
    target: 15%
  
  risk_weighted_assets:
    qard_hasan: 100%
    murabaha: 50% (secured by asset)
    micro_enterprise: 100% (unsecured)

  provisioning:
    standard: 1% of portfolio
    special_mention: 5% (30-60 days overdue)
    substandard: 25% (60-90 days overdue)
    doubtful: 50% (90-180 days overdue)
    loss: 100% (180+ days overdue)

  reporting:
    daily: Transaction file to CBS
    weekly: Portfolio summary
    monthly: 
      - Financial statements
      - NPL report
      - Capital adequacy calculation
      - Large exposures report
    quarterly:
      - Audited financial statements
      - Sharia compliance report
      - Risk management report
    annually:
      - External audit
      - Sharia audit
      - Actuarial valuation
```

### Interest Rate / Profit Rate Caps
```yaml
cbs_rate_limits:
  qard_hasan:
    profit: 0% (mandatory)
    admin_fee: ≤ 2% of principal or SYP 10,000 (whichever lower)
  
  murabaha:
    max_profit_rate: 12.5% flat (CBS consumer lending cap)
    admin_fee: ≤ 1% of total or SYP 25,000
  
  micro_enterprise:
    max_profit_rate: 15% flat (CBS SME lending cap)
    admin_fee: ≤ 1.5% of principal or SYP 50,000
```

---

## 3. Charity Fee Management

### Charity Account Structure
```sql
-- Separate bank account for charity fees
Account: BEZA-CHARITY-SYP-001
Owner: Beza Platform (fiduciary for charity)
Signatories: Sharia Board Chair + CEO (dual signature)
```

### Fee Collection Flow
```
1. Late fee incurred → Dr. User Wallet / Cr. Charity Liability
2. End of quarter: Liability balance calculated
3. Sharia Board approves charity recipients
4. Funds transferred to registered charity organizations
5. Full documentation published on Beza transparency page
```

### Quarterly Disbursement Documentation
```json
{
  "quarter": "Q2-2026",
  "period": "April - June 2026",
  "total_fees_collected": "SYP 2,450,000",
  "number_of_contracts_with_late_fees": 342,
  "charities": [
    {
      "name_ar": "جمعية البر والخدمات الاجتماعية",
      "registration": "1234",
      "amount": "SYP 1,000,000",
      "purpose": "مساعدات عاجلة للأسر المتعففة"
    },
    {
      "name_ar": "مؤسسة اليتيم الخيرية",
      "registration": "5678",
      "amount": "SYP 725,000",
      "purpose": "كفالة الأيتام"
    },
    {
      "name_ar": "صندوق الزكاة والصدقات",
      "registration": "9012",
      "amount": "SYP 725,000",
      "purpose": "دعم المشاريع الصغيرة"
    }
  ],
  "auditor": "شركة التدقيق الشرعي"
}
```

### Charity Fee Accounting
```yaml
# Double-entry for late fees
entry_1:
  debit: User Wallet (SYP 10,000)
  credit: Charity Liability Account (SYP 10,000)
  note: "Late fee Day 7 - Contract BZ-QH-2026-00001"

entry_2 (at disbursement):
  debit: Charity Liability Account (SYP 1,000,000)
  credit: Bank Account - Charity (SYP 1,000,000)
  note: "Q2 2026 disbursement to Charity Org #1234"
```

---

## 4. Consumer Protection

### Disclosure Requirements
| Item | Where Displayed | Format |
|------|-----------------|--------|
| Total amount to repay | Offer screen | Bold, largest font |
| Profit amount (Murabaha) | Offer screen | Separated line item |
| Admin fees | Offer screen + contract | Itemized |
| Late fee amount | Terms section | Per-day rate |
| Charity destination | Terms section | "لا تعود للشركة" |
| Cooling-off period | After offer acceptance | 7-day notice |

### Complaint Handling
| TAT | Action |
|-----|--------|
| 24 hours | Acknowledge receipt |
| 5 business days | Investigate and respond |
| 15 business days | Escalate to CBS if unresolved |
| 30 days | Final resolution or regulatory referral |
