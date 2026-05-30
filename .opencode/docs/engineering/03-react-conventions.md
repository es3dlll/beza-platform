# React Admin Conventions — Beza Platform

## Overview

The admin panel is a React SPA serving internal operations, compliance officers, and customer support for the Syria fintech platform. Built with TypeScript, React Query, and Tailwind CSS. Fully bilingual (Arabic/English) with RTL support.

## Directory Structure

```
admin/
├── public/
│   ├── locales/               # i18n JSON files (ar.json, en.json)
│   └── assets/
├── src/
│   ├── components/            # Reusable UI components
│   │   ├── ui/                # Primitives: Button, Input, Modal, Table
│   │   ├── forms/             # Form components: PhoneInput, AmountInput
│   │   ├── charts/            # Chart components: TransactionChart, AgentMap
│   │   └── layout/            # Sidebar, Header, Breadcrumbs
│   ├── pages/                 # Screen-level components (1 per route)
│   │   ├── Dashboard/
│   │   ├── Wallets/
│   │   ├── Transactions/
│   │   ├── Agents/
│   │   ├── Compliance/
│   │   ├── FX/
│   │   └── Settings/
│   ├── services/              # API service layer (React Query hooks)
│   ├── hooks/                 # Custom React hooks
│   ├── utils/                 # Helper functions, formatters, validators
│   ├── types/                 # TypeScript interfaces/types
│   ├── layouts/               # Page layout components
│   ├── contexts/              # React contexts (Auth, Theme, Locale)
│   └── routes/                # Route definitions and guards
├── tailwind.config.ts
├── vite.config.ts
└── tsconfig.json
```

## Conventions

### TypeScript (Mandatory)
- ALL files are TypeScript. No `.js` or `.jsx` files in `src/`.
- Strict mode enabled in `tsconfig.json`.
- Interfaces preferred over types for object shapes. Types used for unions, tuples, and primitives.
- All API responses have typed interfaces in `types/`.
- No `any` unless interfacing with untyped third-party library (with eslint-disable comment).
- Use `unknown` instead of `any` for truly dynamic data, with type narrowing.

### React Query for ALL API Calls
- No raw `fetch()` or `axios` calls outside of service files.
- Every API endpoint has a corresponding React Query hook in `services/`.
- Mutations use `useMutation` with `onSuccess`/`onError` handlers.
- Cache invalidation uses query keys — never force-refetch without reason.

```tsx
// ✅ CORRECT
export function useTransfer() {
  const queryClient = useQueryClient();

  return useMutation({
    mutationFn: (data: TransferRequest) => api.post('/wallet/transfers', data),
    onSuccess: (_, variables) => {
      queryClient.invalidateQueries({ queryKey: ['wallet', variables.senderWalletId] });
      queryClient.invalidateQueries({ queryKey: ['transactions'] });
    },
  });
}

// ❌ WRONG — raw fetch in component
const response = await fetch('/api/wallet/transfers', { method: 'POST', body: JSON.stringify(data) });
```

### Component Structure
- One component per file, exported as default.
- Components are organized by domain (pages/) or reuse (components/).
- Component files are named in PascalCase matching the component name.
- Test files co-located: `WalletCard.tsx` / `WalletCard.test.tsx`.
- Each component has a clear responsibility. Split if exceeding 150 lines.

### Custom Hooks
- Shared stateful logic extracted to custom hooks in `hooks/`.
- Hook names start with `use` and describe the behavior.
- Hooks return objects with named properties, never arrays.
- Example hooks: `useWalletBalance`, `usePagination`, `useDebounce`, `useSyriaPhoneInput`.

### Error Boundaries
- Error boundaries wrap each page section (sidebar, main content, modals).
- A global error boundary catches unhandled errors and displays a fallback UI in Arabic.
- Error boundary fallbacks offer a "retry" button that resets the error state.

### Arabic & RTL Support
- ALL user-facing strings come from translation files (`public/locales/{ar,en}.json`).
- No hardcoded Arabic or English strings in components.
- Use `t()` function from i18next or similar for all labels.
- RTL layout support via Tailwind's `rtl:` prefix.
- Number formatting respects locale (Arabic numerals / Hindu-Arabic numerals).
- Syrian Pound amounts formatted with SYP symbol and comma separators: `١٢٬٣٤٥ ل.س`.

```tsx
// ✅ CORRECT
<p>{t('wallet.balance', { amount: formatCurrency(balance, 'SYP') })}</p>

// ❌ WRONG
<p>الرصيد: {balance} ل.س</p>
```

### State Management
- React Query handles server state (API data).
- React context handles client state (auth, theme, locale).
- No Redux, Zustand, or other state managers unless justified by performance profiling.
- Form state managed by React Hook Form with Zod validation schemas.

### API Service Layer Pattern

```ts
// services/walletService.ts
export const walletService = {
  getWallets: (params: WalletQueryParams): Promise<PaginatedResponse<Wallet>> =>
    api.get('/wallet/wallets', { params }),

  getWallet: (id: string): Promise<Wallet> =>
    api.get(`/wallet/wallets/${id}`),

  createWallet: (data: CreateWalletDto): Promise<Wallet> =>
    api.post('/wallet/wallets', data),
};

// services/useWalletQuery.ts
export function useWallets(params: WalletQueryParams) {
  return useQuery({
    queryKey: ['wallets', params],
    queryFn: () => walletService.getWallets(params),
  });
}
```

### Syria-Specific Validation (Admin Panel)
- Syrian phone input component with +963 prefix locked.
- National ID field with format validation and check-digit verification.
- Amount fields in SYP reject decimal input (integers only).
- Agent selection with geographic filtering (Damascus, Aleppo, Homs, etc.).
- Date pickers default to Syria timezone (UTC+3).
- Export functionality includes Arabic column headers and RTL formatting.

### Performance Requirements
- P99 page load < 2 seconds on Syria-origin networks (account for potential throttling).
- List pages paginate at 25 rows. Virtual scrolling for >500 rows.
- All API calls have loading states (skeleton loaders, not spinners).
- Infinite scroll or pagination for transaction history.
- Bundle size monitored via CI — alert if >500KB (gzip).
