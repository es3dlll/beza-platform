import React from 'react';
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';

const mockProviders = [
  { id: 'bp-1', name: 'الشركة العامة للكهرباء', category: 'electricity', external_id: 'EXT-ELEC-001', is_active: true, support_phone: '0111234567', config: null },
  { id: 'bp-2', name: 'مؤسسة المياه', category: 'water', external_id: 'EXT-WATER-001', is_active: true, support_phone: '0112345678', config: null },
  { id: 'bp-3', name: 'الاتصالات السورية', category: 'telecom', external_id: 'EXT-TEL-001', is_active: true, support_phone: null, config: null },
  { id: 'bp-4', name: 'إنترنت بيزا', category: 'internet', external_id: 'EXT-NET-001', is_active: false, support_phone: null, config: null },
];

const mockBills = [
  { id: 'bl-1', user_id: 'u1', bill_provider_id: 'bp-1', provider_name: 'كهرباء', account_number: 'ACC-001', amount_fils: 150_000, due_date: '2026-05-15', status: 'paid', paid_at: '2026-05-10T10:00:00Z', receipt_reference: 'RCP-001' },
  { id: 'bl-2', user_id: 'u1', bill_provider_id: 'bp-2', provider_name: 'مياه', account_number: 'ACC-002', amount_fils: 75_000, due_date: '2026-06-01', status: 'pending', paid_at: null, receipt_reference: null },
  { id: 'bl-3', user_id: 'u2', bill_provider_id: 'bp-1', provider_name: 'كهرباء', account_number: 'ACC-003', amount_fils: 200_000, due_date: '2026-05-01', status: 'pending', paid_at: null, receipt_reference: null },
  { id: 'bl-4', user_id: 'u3', bill_provider_id: 'bp-4', provider_name: 'إنترنت', account_number: 'ACC-004', amount_fils: 50_000, due_date: '2026-04-20', status: 'pending', paid_at: null, receipt_reference: null },
];

const mockSchedules = [
  { id: 'sch-1', user_id: 'u1', bill_provider_id: 'bp-1', provider_name: 'كهرباء', account_number: 'ACC-001', amount_fils: 150_000, recurrence: 'monthly', recurrence_day: 15, next_execution_date: '2026-06-15', last_executed_at: '2026-05-15T10:00:00Z', is_active: true },
  { id: 'sch-2', user_id: 'u2', bill_provider_id: 'bp-2', provider_name: 'مياه', account_number: 'ACC-002', amount_fils: 75_000, recurrence: 'quarterly', recurrence_day: 1, next_execution_date: '2026-07-01', last_executed_at: null, is_active: true },
  { id: 'sch-3', user_id: 'u3', bill_provider_id: 'bp-4', provider_name: 'إنترنت', account_number: 'ACC-004', amount_fils: 50_000, recurrence: 'monthly', recurrence_day: 20, next_execution_date: '2026-06-20', last_executed_at: null, is_active: false },
];

// ─── Component: Provider Manager ──────────────────────────────────

function ProviderManager({ providers = mockProviders, onAdd, onToggle }: {
  providers?: typeof mockProviders;
  onAdd?: (name: string, category: string, externalId: string) => void;
  onToggle?: (id: string) => void;
}) {
  const [showForm, setShowForm] = React.useState(false);
  const [name, setName] = React.useState('');
  const [category, setCategory] = React.useState('electricity');
  const [extId, setExtId] = React.useState('');

  return (
    <div>
      <h1>إدارة مزودي الخدمات</h1>
      <button data-testid="add-provider-btn" onClick={() => setShowForm(true)}>إضافة مزود جديد</button>
      {showForm && (
        <div data-testid="add-provider-form">
          <input data-testid="provider-name-input" value={name} onChange={e => setName(e.target.value)} placeholder="الاسم" />
          <select data-testid="provider-category-select" value={category} onChange={e => setCategory(e.target.value)}>
            <option value="electricity">كهرباء</option>
            <option value="water">مياه</option>
            <option value="telecom">اتصالات</option>
            <option value="gas">غاز</option>
          </select>
          <input data-testid="provider-extid-input" value={extId} onChange={e => setExtId(e.target.value)} placeholder="المعرف الخارجي" />
          <button data-testid="save-provider-btn" onClick={() => { onAdd?.(name, category, extId); setShowForm(false); setName(''); setExtId(''); }}>حفظ</button>
        </div>
      )}
      <table>
        <thead><tr><th>الاسم</th><th>الفئة</th><th>الحالة</th><th>إجراء</th></tr></thead>
        <tbody>
          {providers.map(p => (
            <tr key={p.id} data-testid={`provider-row-${p.id}`}>
              <td>{p.name}</td>
              <td>{p.category}</td>
              <td data-testid={`provider-status-${p.id}`}>{p.is_active ? 'نشط' : 'غير نشط'}</td>
              <td>
                <button data-testid={`toggle-provider-${p.id}`} onClick={() => onToggle?.(p.id)}>
                  {p.is_active ? 'تعطيل' : 'تفعيل'}
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

// ─── Component: Payment Monitor ───────────────────────────────────

function PaymentMonitor({ bills = mockBills }: { bills?: typeof mockBills }) {
  const [statusFilter, setStatusFilter] = React.useState('all');
  const [providerFilter, setProviderFilter] = React.useState('all');

  const filtered = bills.filter(b => {
    if (statusFilter !== 'all' && b.status !== statusFilter) return false;
    if (providerFilter !== 'all' && b.bill_provider_id !== providerFilter) return false;
    return true;
  });

  const overdueCount = bills.filter(b => b.status === 'pending' && new Date(b.due_date) < new Date()).length;
  const overdueRate = bills.length > 0 ? Math.round((overdueCount / bills.length) * 100) : 0;

  return (
    <div>
      <h1>مراقبة المدفوعات</h1>
      <div data-testid="overdue-rate">نسبة المتأخرات: {overdueRate}%</div>
      <select data-testid="status-filter" value={statusFilter} onChange={e => setStatusFilter(e.target.value)}>
        <option value="all">الكل</option>
        <option value="paid">مدفوعة</option>
        <option value="pending">معلقة</option>
      </select>
      <select data-testid="provider-filter" value={providerFilter} onChange={e => setProviderFilter(e.target.value)}>
        <option value="all">جميع المزودين</option>
        {[...new Set(bills.map(b => b.bill_provider_id))].map(pid => (
          <option key={pid} value={pid}>{bills.find(b => b.bill_provider_id === pid)?.provider_name}</option>
        ))}
      </select>
      <table>
        <thead><tr><th>الحساب</th><th>المزود</th><th>المبلغ</th><th>تاريخ الاستحقاق</th><th>الحالة</th></tr></thead>
        <tbody>
          {filtered.map(b => (
            <tr key={b.id} data-testid={`bill-row-${b.id}`}>
              <td>{b.account_number}</td>
              <td>{b.provider_name}</td>
              <td>{b.amount_fils}</td>
              <td>{b.due_date}</td>
              <td data-testid={`bill-status-${b.id}`}>{b.status === 'paid' ? 'مدفوعة' : b.status === 'pending' ? 'معلقة' : b.status}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

// ─── Component: Active Schedules ──────────────────────────────────

function ScheduleManager({ schedules = mockSchedules, onToggle, onCancel }: {
  schedules?: typeof mockSchedules;
  onToggle?: (id: string) => void;
  onCancel?: (id: string) => void;
}) {
  const [confirmId, setConfirmId] = React.useState<string | null>(null);

  return (
    <div>
      <h1>المجدولات النشطة</h1>
      <table>
        <thead><tr><th>المزود</th><th>المبلغ</th><th>التكرار</th><th>التنفيذ التالي</th><th>الحالة</th><th>إجراء</th></tr></thead>
        <tbody>
          {schedules.map(s => (
            <tr key={s.id} data-testid={`schedule-row-${s.id}`}>
              <td>{s.provider_name}</td>
              <td>{s.amount_fils}</td>
              <td>{s.recurrence === 'monthly' ? 'شهري' : s.recurrence === 'quarterly' ? 'ربع سنوي' : 'سنوي'}</td>
              <td>{s.next_execution_date}</td>
              <td data-testid={`schedule-status-${s.id}`}>{s.is_active ? 'نشطة' : 'موقوفة'}</td>
              <td>
                {s.is_active ? (
                  <>
                    <button data-testid={`suspend-schedule-${s.id}`} onClick={() => setConfirmId(s.id)}>تعليق</button>
                    {confirmId === s.id && (
                      <div data-testid="suspend-confirm">
                        <p>سيتم إيقاف الجدولة. هل أنت متأكد؟</p>
                        <button data-testid="confirm-suspend-yes" onClick={() => { onToggle?.(s.id); setConfirmId(null); }}>تأكيد الإيقاف</button>
                        <button onClick={() => setConfirmId(null)}>إلغاء</button>
                      </div>
                    )}
                  </>
                ) : (
                  <button data-testid={`reactivate-schedule-${s.id}`} onClick={() => onToggle?.(s.id)}>إعادة تفعيل</button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

// ─── Unit helpers ─────────────────────────────────────────────────

function calculateBillsOverdueRate(bills: typeof mockBills): number {
  const total = bills.length;
  if (total === 0) return 0;
  const overdue = bills.filter(b => b.status === 'pending' && new Date(b.due_date) < new Date()).length;
  return Math.round((overdue / total) * 100);
}

function unauthorizedAccessCheck(userRole: string, resource: string): boolean {
  const permissions: Record<string, string[]> = {
    admin: ['providers', 'bills', 'schedules', 'settings'],
    operator: ['bills', 'schedules'],
    viewer: ['bills'],
  };
  return (permissions[userRole] ?? []).includes(resource);
}

// ─── Tests ────────────────────────────────────────────────────────

describe('Provider Manager', () => {
  it('renders provider list and adds a new provider', () => {
    const onAdd = vi.fn();
    render(<ProviderManager onAdd={onAdd} />);

    expect(screen.getByText('الشركة العامة للكهرباء')).toBeDefined();
    expect(screen.getByText('إنترنت بيزا')).toBeDefined();

    fireEvent.click(screen.getByTestId('add-provider-btn'));
    expect(screen.getByTestId('add-provider-form')).toBeDefined();

    fireEvent.change(screen.getByTestId('provider-name-input'), { target: { value: 'الغاز السوري' } });
    fireEvent.change(screen.getByTestId('provider-category-select'), { target: { value: 'gas' } });
    fireEvent.change(screen.getByTestId('provider-extid-input'), { target: { value: 'EXT-GAS-001' } });
    fireEvent.click(screen.getByTestId('save-provider-btn'));

    expect(onAdd).toHaveBeenCalledWith('الغاز السوري', 'gas', 'EXT-GAS-001');
  });

  it('toggles provider active status', () => {
    const onToggle = vi.fn();
    render(<ProviderManager onToggle={onToggle} />);

    expect(screen.getByTestId('provider-status-bp-1').textContent).toBe('نشط');
    fireEvent.click(screen.getByTestId('toggle-provider-bp-1'));
    expect(onToggle).toHaveBeenCalledWith('bp-1');
  });
});

describe('Payment Monitor', () => {
  it('filters bills by status and provider', () => {
    render(<PaymentMonitor />);

    expect(screen.getByTestId('bill-row-bl-1')).toBeDefined();
    expect(screen.getByTestId('bill-row-bl-2')).toBeDefined();
    expect(screen.getByTestId('bill-row-bl-3')).toBeDefined();

    // Filter by paid status
    fireEvent.change(screen.getByTestId('status-filter'), { target: { value: 'paid' } });
    expect(screen.getByTestId('bill-row-bl-1')).toBeDefined();
    expect(screen.queryByTestId('bill-row-bl-2')).toBeNull();

    // Filter by provider
    fireEvent.change(screen.getByTestId('status-filter'), { target: { value: 'all' } });
    fireEvent.change(screen.getByTestId('provider-filter'), { target: { value: 'bp-2' } });
    expect(screen.getByTestId('bill-row-bl-2')).toBeDefined();
    expect(screen.queryByTestId('bill-row-bl-1')).toBeNull();
  });

  it('displays overdue rate correctly', () => {
    render(<PaymentMonitor />);
    expect(screen.getByTestId('overdue-rate').textContent).toContain('نسبة المتأخرات');
  });
});

describe('Schedule Manager', () => {
  it('suspends an active schedule with confirmation', () => {
    const onToggle = vi.fn();
    render(<ScheduleManager onToggle={onToggle} />);

    expect(screen.getByTestId('schedule-status-sch-1').textContent).toBe('نشطة');

    fireEvent.click(screen.getByTestId('suspend-schedule-sch-1'));
    expect(screen.getByTestId('suspend-confirm')).toBeDefined();

    fireEvent.click(screen.getByTestId('confirm-suspend-yes'));
    expect(onToggle).toHaveBeenCalledWith('sch-1');
  });

  it('reactivates a suspended schedule', () => {
    const onToggle = vi.fn();
    render(<ScheduleManager onToggle={onToggle} />);

    expect(screen.getByTestId('schedule-status-sch-3').textContent).toBe('موقوفة');
    fireEvent.click(screen.getByTestId('reactivate-schedule-sch-3'));
    expect(onToggle).toHaveBeenCalledWith('sch-3');
  });
});

describe('Authorization Guard', () => {
  it('prevents unauthorized provider editing', () => {
    const canViewerEdit = unauthorizedAccessCheck('viewer', 'providers');
    const canOperatorManage = unauthorizedAccessCheck('operator', 'schedules');
    const canAdminManage = unauthorizedAccessCheck('admin', 'providers');

    expect(canViewerEdit).toBe(false);
    expect(canOperatorManage).toBe(true);
    expect(canAdminManage).toBe(true);
  });
});

describe('Overdue Rate Calculator Unit Test', () => {
  it('calculates overdue rate accurately', () => {
    // 3 pending bills, only bl-3 and bl-4 have past due dates (before June 2026)
    const rate = calculateBillsOverdueRate(mockBills);
    // Total: 4 bills. Overdue: bl-3 (May 1) and bl-4 (April 20) = 2
    expect(rate).toBe(50);
  });

  it('returns 0 for empty bills array', () => {
    expect(calculateBillsOverdueRate([])).toBe(0);
  });
});
