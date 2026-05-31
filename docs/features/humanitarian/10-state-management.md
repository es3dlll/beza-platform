# State Management

## Architecture

| Concern | Tool | Rationale |
|---------|------|-----------|
| Server state (programs, beneficiaries, distributions) | TanStack Query | Auto-caching, background refetch, optimistic updates |
| Client state (UI modals, wizard state, filters) | Zustand | Lightweight, no boilerplate, works outside React tree |
| Form state | React Hook Form | Performance with large beneficiary lists |
| Offline queue | Zustand persist (IndexedDB) | Agent app must queue verifications when offline |

## Zustand Stores

### `useProgramStore`
```typescript
interface ProgramStore {
  currentWizardStep: number;
  wizardData: Partial<AidProgram>;
  setWizardStep: (step: number) => void;
  updateWizardData: (data: Partial<AidProgram>) => void;
  resetWizard: () => void;
}
```

### `useAgentVerificationStore`
```typescript
interface AgentVerificationStore {
  offlineQueue: VerificationRecord[];
  addToQueue: (record: VerificationRecord) => void;
  syncQueue: () => Promise<void>;
  syncStatus: 'idle' | 'syncing' | 'error';
}
```

### `useDistributionStore`
```typescript
interface DistributionStore {
  activeDistributions: Map<string, DistributionProgress>;
  updateProgress: (batchId: string, progress: DistributionProgress) => void;
  clearCompleted: () => void;
}
```

## TanStack Query Keys

| Query Key | Data | Cache Time | Stale Time |
|-----------|------|------------|------------|
| `['programs', { status }]` | Program list | 5 min | 30 sec |
| `['programs', id]` | Program detail | 5 min | 30 sec |
| `['beneficiaries', programId, { page, filters }]` | Beneficiary table | 2 min | 1 min |
| `['distributions', programId, { page }]` | Distribution history | 30 sec | 10 sec |
| `['spending', programId, { from, to }]` | Spending analytics | 1 min | 1 min |
| `['reports', { ngoId, from, to }]` | Donor report | 10 min | 5 min |

## Offline Strategy (Agent App)
- Agent app verifies beneficiary → adds `VerificationRecord` to offline queue
- Queue persisted in IndexedDB via Zustand `persist` middleware
- When online, background sync process sends queue to server
- Server validates idempotency (no double-credit) via `verification_id` UUID
- Beneficiary receives credit even if agent was offline at time of verification
