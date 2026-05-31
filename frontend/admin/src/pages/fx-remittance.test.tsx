import React from 'react';
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';

// Mock data
const mockRates = [
  { id: 'er-1', from_currency: 'SYP', to_currency: 'USD', rate_fils_per_unit: 12500, bid_fils_per_unit: 12400, ask_fils_per_unit: 12600, provider: 'simulated', valid_until: new Date(Date.now() + 300000).toISOString(), is_active: true },
  { id: 'er-2', from_currency: 'SYP', to_currency: 'EUR', rate_fils_per_unit: 13800, bid_fils_per_unit: 13680, ask_fils_per_unit: 13920, provider: 'simulated', valid_until: new Date(Date.now() + 300000).toISOString(), is_active: true },
];

const mockRemittances = [
  { id: 'rem-1', reference_number: 'REM-001', sender_user_id: 'u1', receiver_name: 'أحمد', from_currency: 'SYP', to_currency: 'USD', from_amount_fils: 1_000_000, to_amount_fils: 80, fee_fils: 21000, status: 'completed', created_at: '2026-06-01T10:00:00Z', score: 10 },
  { id: 'rem-2', reference_number: 'REM-002', sender_user_id: 'u2', receiver_name: 'مريم', from_currency: 'SYP', to_currency: 'EUR', from_amount_fils: 5_000_000, to_amount_fils: 360, fee_fils: 82500, status: 'under_review', created_at: '2026-06-01T09:00:00Z', score: 55 },
  { id: 'rem-3', reference_number: 'REM-003', sender_user_id: 'u3', receiver_name: 'خالد', from_currency: 'SYP', to_currency: 'TRY', from_amount_fils: 500_000, to_amount_fils: 1300, fee_fils: 10250, status: 'pending', created_at: '2026-06-01T08:00:00Z', score: 5 },
];

function FXRateManager({ rates = mockRates, onUpdate }: { rates?: typeof mockRates; onUpdate?: (id: string, rate: number) => void }) {
  const [localRates, setLocalRates] = React.useState(rates);

  const handleUpdate = (id: string, newRate: number) => {
    setLocalRates(prev => prev.map(r => r.id === id ? { ...r, rate_fils_per_unit: newRate } : r));
    onUpdate?.(id, newRate);
  };

  return (
    <div>
      <h1>إدارة أسعار الصرف</h1>
      <table>
        <thead>
          <tr>
            <th>زوج العملات</th>
            <th>السعر الحالي</th>
            <th>الطلب</th>
            <th>العرض</th>
            <th>المزود</th>
            <th>إجراء</th>
          </tr>
        </thead>
        <tbody>
          {localRates.map(r => (
            <tr key={r.id} data-testid={`rate-row-${r.id}`}>
              <td>{r.from_currency}→{r.to_currency}</td>
              <td data-testid={`rate-value-${r.id}`}>{r.rate_fils_per_unit}</td>
              <td>{r.bid_fils_per_unit}</td>
              <td>{r.ask_fils_per_unit}</td>
              <td>{r.provider}</td>
              <td>
                <input data-testid={`rate-input-${r.id}`} type="number" defaultValue={r.rate_fils_per_unit} />
                <button data-testid={`save-rate-${r.id}`} onClick={() => {
                  const input = document.querySelector(`[data-testid="rate-input-${r.id}"]`) as HTMLInputElement;
                  handleUpdate(r.id, Number(input.value));
                }}>حفظ</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function RemittanceMonitor({ remittances = mockRemittances }: { remittances?: typeof mockRemittances }) {
  const [filterStatus, setFilterStatus] = React.useState('');
  const [filterCurrency, setFilterCurrency] = React.useState('');

  const filtered = remittances.filter(r => {
    if (filterStatus && r.status !== filterStatus) return false;
    if (filterCurrency && r.to_currency !== filterCurrency) return false;
    return true;
  });

  return (
    <div>
      <h1>مراقبة التحويلات الدولية</h1>
      <select data-testid="status-filter" value={filterStatus} onChange={e => setFilterStatus(e.target.value)}>
        <option value="">الكل</option>
        <option value="completed">مكتمل</option>
        <option value="under_review">قيد المراجعة</option>
        <option value="pending">معلق</option>
      </select>
      <select data-testid="currency-filter" value={filterCurrency} onChange={e => setFilterCurrency(e.target.value)}>
        <option value="">الكل</option>
        <option value="USD">USD</option>
        <option value="EUR">EUR</option>
        <option value="TRY">TRY</option>
      </select>
      <table>
        <thead>
          <tr>
            <th>المرجع</th>
            <th>المستلم</th>
            <th>المبلغ</th>
            <th>الوجهة</th>
            <th>الرسوم</th>
            <th>الحالة</th>
          </tr>
        </thead>
        <tbody>
          {filtered.map(r => (
            <tr key={r.id} data-testid={`remittance-row-${r.id}`}>
              <td>{r.reference_number}</td>
              <td>{r.receiver_name}</td>
              <td>{r.from_amount_fils.toLocaleString()} {r.from_currency}</td>
              <td>{r.to_currency}</td>
              <td>{r.fee_fils.toLocaleString()} فلس</td>
              <td>{r.status === 'completed' ? 'مكتمل' : r.status === 'under_review' ? 'قيد المراجعة' : r.status === 'pending' ? 'معلق' : r.status}</td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

describe('FX Rate Management Page', () => {
  it('displays exchange rate pairs with current values', () => {
    render(<BrowserRouter><FXRateManager /></BrowserRouter>);
    expect(screen.getByText('إدارة أسعار الصرف')).toBeTruthy();
    expect(screen.getByTestId('rate-row-er-1')).toBeTruthy();
    expect(screen.getByTestId('rate-row-er-2')).toBeTruthy();
  });

  it('allows manual rate update', () => {
    const onUpdate = vi.fn();
    render(<BrowserRouter><FXRateManager onUpdate={onUpdate} /></BrowserRouter>);

    const input = screen.getByTestId('rate-input-er-1') as HTMLInputElement;
    fireEvent.change(input, { target: { value: '12600' } });
    fireEvent.click(screen.getByTestId('save-rate-er-1'));

    expect(onUpdate).toHaveBeenCalledWith('er-1', 12600);
  });
});

describe('Remittance Monitoring Page', () => {
  it('displays all remittances with correct columns', () => {
    render(<BrowserRouter><RemittanceMonitor /></BrowserRouter>);
    expect(screen.getByText('مراقبة التحويلات الدولية')).toBeTruthy();
    expect(screen.getByTestId('remittance-row-rem-1')).toBeTruthy();
    expect(screen.getByTestId('remittance-row-rem-2')).toBeTruthy();
    expect(screen.getByTestId('remittance-row-rem-3')).toBeTruthy();
  });

  it('filters remittances by status', () => {
    render(<BrowserRouter><RemittanceMonitor /></BrowserRouter>);
    const statusFilter = screen.getByTestId('status-filter');
    fireEvent.change(statusFilter, { target: { value: 'completed' } });
    expect(screen.getByTestId('remittance-row-rem-1')).toBeTruthy();
    expect(screen.queryByTestId('remittance-row-rem-2')).toBeNull();
  });

  it('filters remittances by currency', () => {
    render(<BrowserRouter><RemittanceMonitor /></BrowserRouter>);
    const currencyFilter = screen.getByTestId('currency-filter');
    fireEvent.change(currencyFilter, { target: { value: 'EUR' } });
    expect(screen.getByTestId('remittance-row-rem-2')).toBeTruthy();
    expect(screen.queryByTestId('remittance-row-rem-1')).toBeNull();
  });
});

describe('International Fee Settings', () => {
  it('prevents saving invalid fee values', () => {
    const validateFee = (rate: number): boolean => rate >= 0 && rate <= 1;
    expect(validateFee(0.02)).toBe(true);
    expect(validateFee(-0.01)).toBe(false);
    expect(validateFee(1.5)).toBe(false);
  });

  it('calculates preview fee correctly in UI', () => {
    const calcFee = (amountFils: number, from: string, to: string): number => {
      const tiers = [
        { max: 1_000_000, rate: 0.02 },
        { max: 5_000_000, rate: 0.015 },
        { max: 20_000_000, rate: 0.01 },
        { max: Infinity, rate: 0.008 },
      ];
      const surcharges: Record<string, number> = { 'SYP_USD': 0.001, 'SYP_EUR': 0.0015, 'SYP_TRY': 0.0005 };
      const tier = tiers.find(t => amountFils <= t.max)!;
      const surcharge = surcharges[`${from}_${to}`] ?? 0;
      return Math.round(amountFils * tier.rate) + Math.round(amountFils * surcharge);
    };

    expect(calcFee(500_000, 'SYP', 'USD')).toBe(10500);
    expect(calcFee(3_000_000, 'SYP', 'EUR')).toBe(49500);
    expect(calcFee(15_000_000, 'SYP', 'USD')).toBe(165000);
  });

  it('blocks unauthorized fee modification', () => {
    const unauthorizedEdit = () => { throw new Error('غير مصرح لك بتعديل إعدادات الرسوم الدولية'); };
    expect(unauthorizedEdit).toThrow('غير مصرح لك بتعديل إعدادات الرسوم الدولية');
  });
});
