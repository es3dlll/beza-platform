# Frontend Components

## Component Tree (Humanitarian Feature)

```
<HumanitarianLayout>                    // RTL-aware layout shell
  ├─ <ProgramManager>                   // NGO program dashboard
  │   ├─ <ProgramList>
  │   │   └─ <ProgramCard>             // Budget bar, status badge, actions
  │   ├─ <ProgramCreateWizard>
  │   │   ├─ <ProgramTypeSelector>      // MPC / CCT / Voucher cards
  │   │   ├─ <ProgramDetailsForm>       // Name (Ar/En), budget, dates
  │   │   ├─ <DistributionRulesForm>    // Amount, frequency, conditional logic
  │   │   ├─ <BeneficiaryUploader>      // CSV drag-drop + validation table
  │   │   └─ <ReviewConfirmPanel>
  │   └─ <ProgramDetailPage>
  │       ├─ <BeneficiaryTable>         // Searchable, filterable, sortable
  │       ├─ <DistributionHistory>
  │       │   └─ <DistributionRow>      // Date, amount, status, retry button
  │       ├─ <SpendingDashboard>
  │       │   ├─ <CategoryPieChart>
  │       │   ├─ <BurnRateLineChart>
  │       │   └─ <SpendingFilterBar>    // Date range, governorate
  │       └─ <ProgramActions>
  │           ├─ <DistributeButton>
  │           ├─ <PauseProgramButton>
  │           └─ <ExportReportButton>
  │
  ├─ <AgentVerificationApp>             // Mobile-first
  │   ├─ <AgentLogin>                   // PIN + biometric
  │   ├─ <BeneficiarySearch>
  │   │   ├─ <ScanBarcode>             // UNHCR ID barcode scanner
  │   │   └─ <ManualSearchForm>         // Name / phone / UNHCR ID
  │   ├─ <BiometricCapture>
  │   │   ├─ <FingerprintScanner>
  │   │   └─ <FaceCapture>
  │   └─ <VerificationResult>
  │       ├─ <SuccessScreen>            // ✅ Confetti + next steps
  │       └─ <FailureScreen>            // ❌ Fallback options
  │
  ├─ <MerchantVoucherApp>               // Mobile-first
  │   ├─ <VoucherRedeem>
  │   │   ├─ <VoucherCodeInput>         // 12-digit or QR scan
  │   │   ├─ <VoucherDetailCard>        // Value, items, expiry
  │   │   ├─ <ItemSelectionGrid>        // Item photo + price + quantity
  │   │   └─ <RedemptionConfirm>
  │   └─ <SettlementHistory>
  │       └─ <SettlementRow>
  │
  └─ <DonorReportPortal>
      ├─ <ReportFilters>                // NGO, program, date range
      ├─ <ReportPreview>
      │   ├─ <KpiCards>                 // Total disbursed, beneficiaries, avg
      │   ├─ <SpendingBreakdownChart>
      │   └─ <BeneficiaryReachTable>    // By governorate
      └─ <ReportExportBar>              // PDF / CSV / XLSX
```

## Key Component Specifications

| Component | Props | Notes |
|-----------|-------|-------|
| `BeneficiaryUploader` | `onUpload(file)`, `onValidationError(errors[])` | Handles CSV parse, client-side validation, preview of first 20 rows |
| `BiometricCapture` | `onCapture(fingerprint, face)`, `isOffline` | Works offline — queues verifications |
| `VoucherRedeem` | `voucherCode`, `onRedeemed(transaction)` | Supports partial redemption (remaining balance preserved) |
| `SpendingDashboard` | `programId`, `dateRange`, `governorate` | Real-time via WebSocket for live updates |
