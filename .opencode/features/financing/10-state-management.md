# إدارة الحالة — State Management

## State Architecture
State is managed using **Zustand** with React Query (TanStack Query) for server state.

## Store Structure

### 1. financingStore (Zustand)
```typescript
interface FinancingState {
  // Current application flow
  currentApplication: Partial<FinancingApplication> | null;
  applicationStep: ApplicationStep;
  
  // Active loans
  activeLoans: LoanSummary[];
  selectedLoanId: number | null;
  
  // Credit score
  creditScore: CreditScore | null;
  creditScoreHistory: ScorePoint[];
  
  // Product catalog
  products: FinancingProduct[];
  selectedProduct: FinancingProduct | null;
  
  // Application draft
  draft: ApplicationDraft;
  
  // UI state
  isLoading: boolean;
  error: string | null;
  showOfferModal: boolean;
  showPaymentSheet: boolean;
}
```

### 2. Application Draft (Persisted to AsyncStorage)
```typescript
interface ApplicationDraft {
  productType: ProductType | null;
  amount: number;
  termDays: number;
  purpose: string;
  documents: Document[];
  guarantorId: string | null;
  guarantorPhone: string;
  lastSavedAt: string;
  isSynced: boolean;
}
```

## React Query Keys
```typescript
export const financingKeys = {
  all: ['financing'] as const,
  products: () => [...financingKeys.all, 'products'] as const,
  applications: () => [...financingKeys.all, 'applications'] as const,
  application: (id: number) => [...financingKeys.applications(), id] as const,
  active: () => [...financingKeys.all, 'active'] as const,
  schedule: (contractId: number) => [...financingKeys.all, 'schedule', contractId] as const,
  creditScore: () => [...financingKeys.all, 'credit-score'] as const,
  scoreHistory: () => [...financingKeys.all, 'score-history'] as const,
};
```

## Data Flow

### Application Submission
```
User fills form → draft persisted locally
User submits → POST /financing/apply
  → Optimistic update: status = "submitted"
  → Real-time polling: GET /financing/applications/{id} (every 10s)
  → On approval: show offer modal
  → User accepts → POST /financing/applications/{id}/accept
  → On disbursement: invalidate active loans query
```

### Repayment
```
Auto-deduction at 08:00 AM
  → Server processes via queue
  → Push notification to user
  → Invalidate schedule query
Manual payment:
  → User taps "سدد الآن"
  → Show PaymentConfirmationSheet
  → POST /financing/{id}/pay
  → Optimistic update of schedule
  → On success: invalidate schedule + active loans + credit score
```

## Side Effects
| Action | Side Effect |
|--------|-------------|
| Application accepted | Schedule charity donation push notification |
| Payment missed | Schedule escalation sequence |
| Disbursement complete | Trigger welcome message + schedule push |
| Credit score change | Check eligibility for pre-approved offers |
| Contract completed | Generate completion certificate + score boost |
