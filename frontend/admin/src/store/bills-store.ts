import { create } from 'zustand';

export interface BillProvider {
  id: string;
  name: string;
  category: string;
  external_id: string;
  is_active: boolean;
  support_phone: string | null;
  config: Record<string, unknown> | null;
}

export interface Bill {
  id: string;
  user_id: string;
  bill_provider_id: string;
  provider_name?: string;
  account_number: string;
  amount_fils: number;
  due_date: string;
  status: string;
  paid_at: string | null;
  receipt_reference: string | null;
}

export interface ScheduledPayment {
  id: string;
  user_id: string;
  bill_provider_id: string;
  provider_name?: string;
  account_number: string;
  amount_fils: number;
  recurrence: string;
  recurrence_day: number;
  next_execution_date: string;
  last_executed_at: string | null;
  is_active: boolean;
}

interface BillsState {
  providers: BillProvider[];
  bills: Bill[];
  schedules: ScheduledPayment[];
  loading: boolean;
  setProviders: (providers: BillProvider[]) => void;
  setBills: (bills: Bill[]) => void;
  setSchedules: (schedules: ScheduledPayment[]) => void;
  addProvider: (provider: BillProvider) => void;
  updateProvider: (id: string, data: Partial<BillProvider>) => void;
  toggleSchedule: (id: string) => void;
  setLoading: (v: boolean) => void;
}

export const useBillsStore = create<BillsState>((set) => ({
  providers: [],
  bills: [],
  schedules: [],
  loading: false,
  setProviders: (providers) => set({ providers }),
  setBills: (bills) => set({ bills }),
  setSchedules: (schedules) => set({ schedules }),
  addProvider: (provider) => set((s) => ({ providers: [...s.providers, provider] })),
  updateProvider: (id, data) => set((s) => ({
    providers: s.providers.map((p) => (p.id === id ? { ...p, ...data } : p)),
  })),
  toggleSchedule: (id) => set((s) => ({
    schedules: s.schedules.map((sc) =>
      sc.id === id ? { ...sc, is_active: !sc.is_active } : sc
    ),
  })),
  setLoading: (loading) => set({ loading }),
}));
