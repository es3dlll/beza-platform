# Frontend Engineering Spec — React Admin Panel

## Technology Stack

| Component        | Choice                       | Version |
| ---------------- | ---------------------------- | ------- |
| Framework        | React                        | 18.x    |
| Language         | TypeScript                   | 5.x     |
| State Management | Redux Toolkit + RTK Query    | Latest  |
| UI Library       | MUI (Material UI)            | 5.x     |
| Forms            | React Hook Form              | Latest  |
| Charts           | Recharts                     | Latest  |
| Maps             | Mapbox GL JS                 | Latest  |
| Table            | TanStack Table (React Table) | Latest  |
| Testing          | Vitest + Playwright          | Latest  |

## Architecture

```
src/
├── api/                     # RTK Query API definitions
│   ├── baseApi.ts           # Base API with auth interceptor
│   ├── authApi.ts           # Auth endpoints
│   ├── walletApi.ts         # Wallet management
│   ├── agentApi.ts          # Agent network
│   ├── merchantApi.ts       # Merchant management
│   ├── userApi.ts           # User management
│   ├── transactionApi.ts    # Transaction management
│   ├── complianceApi.ts     # KYC, AML, sanctions
│   └── reportingApi.ts      # Reports dashboard
│
├── components/              # Shared UI components
│   ├── layout/
│   │   ├── AppLayout.tsx
│   │   ├── Sidebar.tsx
│   │   ├── TopBar.tsx
│   │   └── Navigation.tsx
│   ├── data/
│   │   ├── DataTable.tsx
│   │   ├── FilterPanel.tsx
│   │   ├── ExportButton.tsx
│   │   └── StatusBadge.tsx
│   ├── forms/
│   │   ├── FormTextField.tsx
│   │   ├── FormSelect.tsx
│   │   ├── FormDatePicker.tsx
│   │   └── FormAmountInput.tsx
│   └── common/
│       ├── LoadingState.tsx
│       ├── EmptyState.tsx
│       ├── ErrorState.tsx
│       ├── ConfirmDialog.tsx
│       └── Notification.tsx
│
├── features/                # Feature-specific pages
│   ├── dashboard/
│   │   ├── DashboardPage.tsx
│   │   ├── KPICards.tsx
│   │   ├── RevenueChart.tsx
│   │   └── TransactionVolume.tsx
│   ├── users/
│   │   ├── UserListPage.tsx
│   │   ├── UserDetailPage.tsx
│   │   ├── UserKycPage.tsx
│   │   └── UserTransactionsPage.tsx
│   ├── agents/
│   │   ├── AgentListPage.tsx
│   │   ├── AgentDetailPage.tsx
│   │   ├── AgentTransactionsPage.tsx
│   │   └── AgentFloatPage.tsx
│   ├── transactions/
│   │   ├── TransactionListPage.tsx
│   │   ├── TransactionDetailPage.tsx
│   │   └── TransactionReversalPage.tsx
│   ├── compliance/
│   │   ├── KYCPendingPage.tsx
│   │   ├── AMLAlertsPage.tsx
│   │   ├── SanctionsPage.tsx
│   │   └── ReportsPage.tsx
│   └── settings/
│       ├── SystemConfigPage.tsx
│       ├── RolesPage.tsx
│       ├── RateLimitsPage.tsx
│       └── AuditLogPage.tsx
│
├── hooks/                   # Custom hooks
│   ├── useAuth.ts
│   ├── usePermissions.ts
│   └── useWebSocket.ts
│
├── store/                   # Redux store
│   ├── index.ts
│   ├── authSlice.ts
│   ├── uiSlice.ts
│   └── notificationSlice.ts
│
├── types/                   # TypeScript types
│   ├── api.ts
│   ├── user.ts
│   ├── transaction.ts
│   ├── agent.ts
│   └── common.ts
│
├── utils/                   # Utilities
│   ├── formatters.ts
│   ├── validators.ts
│   ├── permissions.ts
│   └── constants.ts
│
├── App.tsx
├── router.tsx
└── main.tsx
```

## Key Pages

### Dashboard

```tsx
// Routes: /admin/dashboard
// Sections:
//   - KPI Row: Active Users (12,450 ↑12%), TP Today (845M SYP),
//              Success Rate (99.7%), Pending KYC (342)
//   - Chart: Transaction Volume (7d, 30d, 90d)
//   - Chart: Revenue Breakdown (fees, FX, MDR, interchange)
//   - Table: Recent Failed Transactions
//   - Table: Pending Compliance Alerts
//   - Map: Live Agent Activity (hotspots)
```

### User Management

```tsx
// Routes: /admin/users, /admin/users/:id
// DataTable columns: ID, Name, Phone, KYC Level, Status, Balance, Last Active
// Actions: View, Suspend, Freeze Wallet, Impersonate, Export
// Detail tabs: Profile, Wallets, Transactions, KYC Documents, Devices, Compliance
```

### Transaction Monitoring

```tsx
// Routes: /admin/transactions, /admin/transactions/:id
// Filters: Type, Status, Amount Range, Date Range, User, Phone, Reference
// DataTable: ID, Type, Sender, Recipient, Amount, Fee, Status, Timestamp
// Detail: Full transaction breakdown, CFE reference, hold/posting IDs, reversal option
// Actions: Reverse (within 24h), Flag for Compliance, Export Receipt
```

## Permissions (RBAC)

```typescript
const PERMISSIONS = {
  users: {
    view: ["super_admin", "ops_manager", "compliance_officer", "support_agent"],
    suspend: ["super_admin", "compliance_officer"],
    delete: ["super_admin"],
  },
  transactions: {
    view: ["super_admin", "ops_manager", "compliance_officer"],
    reverse: ["super_admin", "ops_manager"],
    export: ["super_admin", "ops_manager", "compliance_officer"],
  },
  agents: {
    view: ["super_admin", "ops_manager"],
    approve: ["super_admin", "ops_manager"],
    suspend: ["super_admin", "ops_manager", "compliance_officer"],
  },
  compliance: {
    view: ["super_admin", "compliance_officer"],
    approve_kyc: ["super_admin", "compliance_officer"],
    file_str: ["super_admin", "compliance_officer"],
  },
} as const;
```
