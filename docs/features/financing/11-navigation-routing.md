# التنقل والمسارات — Navigation & Routing

## Mobile Navigation Structure

```
TabNavigator
├── Home (الرئيسية)
│   └── FinancingHub (التمويل)
├── Financing (التمويل) [Tab]
│   ├── FinancingHub
│   ├── ActiveLoans
│   ├── ApplicationStatus
│   └── CreditScore
├── Wallet (المحفظة)
├── More (المزيد)
└── ...existing tabs
```

## Screen Routes

### User-Facing Routes
| Route | Screen | Params |
|-------|--------|--------|
| `/financing` | FinancingHub | — |
| `/financing/apply` | ApplicationForm | `productType?` (pre-select) |
| `/financing/apply/:id` | ApplicationStatus | `id: number` |
| `/financing/offer/:id` | OfferAcceptance | `id: number` |
| `/financing/active` | ActiveLoans | — |
| `/financing/:contractId` | LoanDetail | `contractId: number` |
| `/financing/:contractId/schedule` | PaymentSchedule | `contractId: number` |
| `/financing/:contractId/pay` | ManualPayment | `contractId: number, installmentNumber?: number` |
| `/financing/:contractId/restructure` | RestructureRequest | `contractId: number` |
| `/financing/credit-score` | CreditScoreDashboard | — |
| `/financing/products/:type` | ProductDetail | `type: ProductType` |

### Admin Routes
| Route | Screen | Params |
|-------|--------|--------|
| `/admin/financing` | AdminDashboard | — |
| `/admin/financing/applications` | ApplicationList | `status?, productType?` |
| `/admin/financing/applications/:id` | ApplicationDetail | `id: number` |
| `/admin/financing/applications/:id/review` | UnderwritingView | `id: number` |
| `/admin/financing/contracts` | ContractList | `status?` |
| `/admin/financing/collections` | CollectionQueue | `priority?, agent?` |
| `/admin/financing/collections/:contractId` | CollectionDetail | `contractId: number` |
| `/admin/financing/reports` | ReportsDashboard | — |
| `/admin/financing/settings` | FinancingSettings | — |

## Deep Linking
```typescript
// Universal links / push notification deep links
{
  "financing_offer": "/financing/offer/{applicationId}",
  "financing_payment_due": "/financing/{contractId}/pay",
  "financing_approved": "/financing/apply/{applicationId}",
  "financing_disbursed": "/financing/{contractId}",
  "financing_overdue": "/financing/{contractId}",
  "financing_guarantor_request": "/financing/guarantee/{applicationId}"
}
```

## Navigation Guards
| Condition | Action |
|-----------|--------|
| User not KYC level 2 | Redirect to KYC upgrade before apply |
| User has active overdue > 30 days | Block new application |
| User has max active loans (3) | Block new application |
| Offer expired | Show "انتهت صلاحية العرض" screen |
| Guarantor not yet joined Beza | Send SMS invite to download Beza |
