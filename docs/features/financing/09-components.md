# مكتبة المكونات — Component Library

## 1. ProductCard
```
<ProductCard
  productType="qard_hasan" | "murabaha" | "micro"
  nameAr="قرض حسن"
  nameEn="Qard Hasan"
  icon={HandCoin}
  minAmount={50000}
  maxAmount={500000}
  profitRate="0%"
  termRange="30–180 يوم"
  description="قرض بدون فائدة... "
  ctaLabel="تقدم بطلب"
  onPress={handleApply}
  isEligible={boolean}
  badgeText="شائع"
/>
```

## 2. ApplicationWizard
```
<ApplicationWizard
  steps={[
    { id: 'basic-info', title: 'معلومات أساسية', completed: true },
    { id: 'documents', title: 'المستندات', completed: false },
    { id: 'guarantor', title: 'الضامن', completed: false },
    { id: 'review', title: 'مراجعة', completed: false }
  ]}
  currentStep={1}
  onNext={handleNext}
  onBack={handleBack}
>
  {children}
</ApplicationWizard>
```

## 3. AmountSlider
```
<AmountSlider
  min={50000}
  max={500000}
  step={10000}
  value={amount}
  onChange={setAmount}
  currency="SYP"
  formatArabic={true}
  showInstallmentPreview={true}
  term={90}
  productType="qard_hasan"
/>
```

## 4. CreditScoreGauge
```
<CreditScoreGauge
  score={680}
  maxScore={850}
  minScore={300}
  showLabel={true}
  size="lg" | "md" | "sm"
  trend="up" | "down" | "stable"
  trendValue={15}
  factors={[
    { label: 'نشاط المعاملات', value: 70 },
    { label: 'الادخار', value: 40 },
    { label: 'دفع الفواتير', value: 90 }
  ]}
  tips={['نصيحة ١', 'نصيحة ٢']}
/>
```

## 5. LoanProgressCard
```
<LoanProgressCard
  loanId={123}
  productType="qard_hasan"
  principal={300000}
  paidAmount={120000}
  nextPaymentDate="2026-06-10"
  nextPaymentAmount={3333}
  progress={40}
  status="active" | "overdue" | "completed" | "defaulted"
  overdueDays={0}
  onPay={handlePay}
  onViewSchedule={handleViewSchedule}
/>
```

## 6. PaymentScheduleTable
```
<PaymentScheduleTable
  installments={[
    { number: 1, dueDate: '2026-05-10', amount: 3333, status: 'paid', paidAt: '2026-05-10T08:00:00Z' },
    { number: 2, dueDate: '2026-05-11', amount: 3333, status: 'pending' }
  ]}
  currency="SYP"
  showPrincipal={false}
  showProfit={false}
  compact={false}
/>
```

## 7. ContractPreview
```
<ContractPreview
  contractNumber="BZ-QH-2026-00001"
  productType="qard_hasan"
  lender="شركة بيزا للتقنية المالية"
  borrowerName="ليلى أحمد"
  borrowerId="100-1234567"
  principal={300000}
  profit={0}
  adminFee={3000}
  totalAmount={300000}
  termDays={90}
  installmentCount={90}
  installmentAmount={3333}
  guaranteeClause="..."
  shariaClause="..."
  onSign={handleSign}
  documentUrl="..."
/>
```

## 8. PaymentConfirmationSheet
```
<PaymentConfirmationSheet
  visible={showSheet}
  paymentAmount={3333}
  sourceWallet="المحفظة الرئيسية"
  sourceBalance={1250000}
  destination="قرض حسن #BZ-QH-2026-00001"
  shariaNotice="يتم تحويل رسوم التأخير إلى حساب الصدقات"
  onConfirm={handlePay}
  onCancel={() => setShowSheet(false)}
  loading={isPaying}
/>
```

## 9. RestructureForm
```
<RestructureForm
  contractId={456}
  remainingPrincipal={180000}
  remainingProfit={0}
  currentTerm={90}
  currentInstallment={3333}
  options={[
    { type: 'extend', label: 'تمديد المدة', newTerm: 135, newInstallment: 2222 },
    { type: 'holiday', label: 'مهلة ٣٠ يوم', newTerm: 120, newInstallment: 3000 }
  ]}
  onSelect={handleRestructure}
  fee={25000}
/>
```

## 10. CollectionCard (Admin)
```
<CollectionCard
  contractId={456}
  borrowerName="نادية محمد"
  borrowerPhone="+963 93 123 4567"
  overdueDays={14}
  overdueAmount={140000}
  totalDebt={300000}
  score={580}
  priority="high" | "medium" | "low"
  lastContactDate="2026-05-25"
  lastContactResult="no_answer"
  onCall={handleCall}
  onMessage={handleMessage}
  onRestructure={handleRestructure}
  onEscalate={handleEscalate}
/>
```

## Style Tokens
```json
{
  "colors": {
    "primary": "#1B5E20",
    "primaryLight": "#4CAF50",
    "gold": "#FFD700",
    "white": "#FFFFFF",
    "background": "#F5F5F5",
    "text": "#212121",
    "error": "#D32F2F",
    "success": "#388E3C",
    "warning": "#F57C00"
  },
  "typography": {
    "arabic": "Cairo",
    "latin": "Inter",
    "headings": "bold 24px",
    "body": "regular 14px",
    "caption": "regular 12px"
  },
  "spacing": { "xs": 4, "sm": 8, "md": 16, "lg": 24, "xl": 32 },
  "borderRadius": { "sm": 8, "md": 12, "lg": 16 }
}
```
