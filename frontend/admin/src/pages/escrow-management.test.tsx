import React from 'react';
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';

interface EscrowTransaction {
  id: string;
  buyer_name: string;
  seller_name: string;
  product_name: string;
  amount_fils: number;
  fee_fils: number;
  status: string;
  created_at: string;
  dispute_id?: string;
}

interface DisputeCase {
  id: string;
  transaction_id: string;
  raised_by: string;
  reason: string;
  description: string;
  status: string;
  decision?: string;
  resolved_at?: string;
}

const mockTransactions: EscrowTransaction[] = [
  { id: 'esc-1', buyer_name: 'أحمد', seller_name: 'متجر الإلكترونيات', product_name: 'هاتف ذكي', amount_fils: 12_500_000, fee_fils: 125_000, status: 'funded', created_at: '2026-06-01T10:00:00Z' },
  { id: 'esc-2', buyer_name: 'سارة', seller_name: 'متجر الإلكترونيات', product_name: 'حاسوب', amount_fils: 35_000_000, fee_fils: 350_000, status: 'released', created_at: '2026-05-28T14:00:00Z' },
  { id: 'esc-3', buyer_name: 'خالد', seller_name: 'أثاث منزلي', product_name: 'كنبة', amount_fils: 8_000_000, fee_fils: 80_000, status: 'disputed', created_at: '2026-05-30T09:00:00Z' },
  { id: 'esc-4', buyer_name: 'نور', seller_name: 'مطعم الشام', product_name: 'وجبة', amount_fils: 750_000, fee_fils: 7_500, status: 'refunded', created_at: '2026-05-25T16:00:00Z' },
  { id: 'esc-5', buyer_name: 'ليلى', seller_name: 'الأزياء العصرية', product_name: 'فستان', amount_fils: 1_500_000, fee_fils: 15_000, status: 'initiated', created_at: '2026-06-02T08:00:00Z' },
];

const mockDisputes: DisputeCase[] = [
  { id: 'disp-1', transaction_id: 'esc-3', raised_by: 'خالد', reason: 'منتج غير مطابق للوصف', description: 'الكنبة مختلفة عن الصورة', status: 'open' },
];

function formatMoney(fils: number): string {
  return `${(fils / 1_000_000).toFixed(2)} M` + ' ل.س';
}

// ─── Component: Escrow Monitor ─────────────────────────────────────

function EscrowMonitor({ transactions = mockTransactions, onRelease, onRefund }: {
  transactions?: EscrowTransaction[];
  onRelease?: (id: string) => void;
  onRefund?: (id: string) => void;
}) {
  const [statusFilter, setStatusFilter] = React.useState('all');

  const filtered = statusFilter === 'all'
    ? transactions
    : transactions.filter(t => t.status === statusFilter);

  const activeCount = transactions.filter(t => ['funded', 'shipped', 'delivered'].includes(t.status)).length;
  const totalHeld = transactions.filter(t => ['funded', 'shipped', 'delivered'].includes(t.status))
    .reduce((sum, t) => sum + t.amount_fils + t.fee_fils, 0);

  return (
    <div>
      <h1>مراقبة الضمانات</h1>
      <div style={{ display: 'flex', gap: 16, marginBottom: 16 }}>
        <div data-testid="active-count" style={{ padding: 12, background: '#e3f2fd', borderRadius: 8 }}>
          <strong>محجوزة حالياً:</strong> {activeCount}
        </div>
        <div data-testid="total-held" style={{ padding: 12, background: '#fff3e0', borderRadius: 8 }}>
          <strong>إجمالي المحجوز:</strong> {formatMoney(totalHeld)}
        </div>
      </div>
      <select data-testid="status-filter" value={statusFilter} onChange={e => setStatusFilter(e.target.value)}>
        <option value="all">الكل</option>
        <option value="initiated">قيد الإنشاء</option>
        <option value="funded">محجوزة</option>
        <option value="released">مفرج عنها</option>
        <option value="disputed">في النزاع</option>
        <option value="refunded">مستردة</option>
      </select>
      <table>
        <thead><tr><th>المنتج</th><th>المشتري</th><th>البائع</th><th>المبلغ</th><th>الرسوم</th><th>الحالة</th><th>إجراء</th></tr></thead>
        <tbody>
          {filtered.map(t => (
            <tr key={t.id} data-testid={`escrow-row-${t.id}`}>
              <td>{t.product_name}</td>
              <td>{t.buyer_name}</td>
              <td>{t.seller_name}</td>
              <td>{formatMoney(t.amount_fils)}</td>
              <td>{formatMoney(t.fee_fils)}</td>
              <td data-testid={`escrow-status-${t.id}`}>
                <span style={{
                  padding: '4px 8px', borderRadius: 4, fontSize: 12, color: 'white',
                  background: t.status === 'funded' ? '#1976d2' : t.status === 'released' ? '#388e3c' : t.status === 'disputed' ? '#d32f2f' : t.status === 'refunded' ? '#7b1fa2' : '#757575',
                }}>
                  {t.status === 'initiated' ? 'قيد الإنشاء' : t.status === 'funded' ? 'محجوزة' : t.status === 'released' ? 'مفرج عنها' : t.status === 'disputed' ? 'في النزاع' : t.status === 'refunded' ? 'مستردة' : t.status}
                </span>
              </td>
              <td>
                {t.status === 'funded' && (
                  <button data-testid={`release-escrow-${t.id}`} onClick={() => onRelease?.(t.id)} style={{ marginRight: 8 }}>إطلاق</button>
                )}
                {t.status === 'funded' && (
                  <button data-testid={`refund-escrow-${t.id}`} onClick={() => onRefund?.(t.id)} style={{ color: 'red' }}>إرجاع</button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

// ─── Component: Dispute Resolver ───────────────────────────────────

function DisputeResolver({ disputes, transactionMap, onResolve }: {
  disputes: DisputeCase[];
  transactionMap: Record<string, EscrowTransaction>;
  onResolve?: (disputeId: string, decision: string, reason: string) => void;
}) {
  const [selectedDispute, setSelectedDispute] = React.useState<string | null>(null);
  const [decision, setDecision] = React.useState('buyer');
  const [reason, setReason] = React.useState('');

  const activeDisputes = disputes.filter(d => d.status === 'open' || d.status === 'under_review');

  return (
    <div>
      <h1>فض النزاعات</h1>
      {activeDisputes.length === 0 ? (
        <p>لا توجد نزاعات مفتوحة</p>
      ) : (
        <table>
          <thead><tr><th>الطلب</th><th>مقدم النزاع</th><th>السبب</th><th>الحالة</th><th>إجراء</th></tr></thead>
          <tbody>
            {activeDisputes.map(d => {
              const tx = transactionMap[d.transaction_id];
              return (
                <tr key={d.id} data-testid={`dispute-row-${d.id}`}>
                  <td>{tx?.product_name ?? d.transaction_id}</td>
                  <td>{d.raised_by}</td>
                  <td>{d.reason}</td>
                  <td data-testid={`dispute-status-${d.id}`}>{d.status === 'open' ? 'مفتوح' : 'قيد المراجعة'}</td>
                  <td>
                    <button data-testid={`resolve-dispute-${d.id}`} onClick={() => setSelectedDispute(d.id)}>فض النزاع</button>
                  </td>
                </tr>
              );
            })}
          </tbody>
        </table>
      )}

      {selectedDispute && (
        <div data-testid="resolve-form" style={{ marginTop: 24, padding: 16, border: '1px solid #ddd', borderRadius: 8 }}>
          <h2>فض النزاع</h2>
          <div style={{ marginBottom: 12 }}>
            <label>القرار: </label>
            <select data-testid="decision-select" value={decision} onChange={e => setDecision(e.target.value)}>
              <option value="buyer">لصالح المشتري (إرجاع)</option>
              <option value="seller">لصالح التاجر (إطلاق)</option>
              <option value="split">تقسيم 50/50</option>
            </select>
          </div>
          <div style={{ marginBottom: 12 }}>
            <label>سبب القرار: </label>
            <input data-testid="decision-reason-input" value={reason} onChange={e => setReason(e.target.value)} placeholder="سبب القرار..." style={{ width: '100%', padding: 8 }} />
          </div>
          <button data-testid="confirm-resolve-btn" onClick={() => {
            if (reason.trim()) {
              onResolve?.(selectedDispute, decision, reason);
              setSelectedDispute(null);
              setReason('');
            }
          }}>تأكيد القرار</button>
          <button onClick={() => setSelectedDispute(null)} style={{ marginRight: 8 }}>إلغاء</button>
        </div>
      )}
    </div>
  );
}

// ─── Unit helpers ──────────────────────────────────────────────────

function calculateTotalHeld(transactions: EscrowTransaction[]): number {
  return transactions
    .filter(t => ['funded', 'shipped', 'delivered'].includes(t.status))
    .reduce((sum, t) => sum + t.amount_fils + t.fee_fils, 0);
}

function countOpenDisputes(disputes: DisputeCase[]): number {
  return disputes.filter(d => d.status === 'open' || d.status === 'under_review').length;
}

// ─── Tests ─────────────────────────────────────────────────────────

describe('Escrow Monitor', () => {
  it('renders active count and total held', () => {
    render(<EscrowMonitor />);
    expect(screen.getByTestId('active-count').textContent).toContain('محجوزة حالياً');
    expect(screen.getByTestId('total-held').textContent).toContain('إجمالي المحجوز');
  });

  it('filters transactions by status', () => {
    render(<EscrowMonitor />);
    expect(screen.getByTestId('escrow-row-esc-1')).toBeDefined();
    expect(screen.getByTestId('escrow-row-esc-5')).toBeDefined();

    fireEvent.change(screen.getByTestId('status-filter'), { target: { value: 'funded' } });
    expect(screen.getByTestId('escrow-row-esc-1')).toBeDefined();
    expect(screen.queryByTestId('escrow-row-esc-5')).toBeNull();
  });

  it('shows release and refund buttons for funded transactions', () => {
    render(<EscrowMonitor />);
    expect(screen.getByTestId('release-escrow-esc-1')).toBeDefined();
    expect(screen.getByTestId('refund-escrow-esc-1')).toBeDefined();
  });

  it('calls onRelease when release button clicked', () => {
    const onRelease = vi.fn();
    render(<EscrowMonitor onRelease={onRelease} />);
    fireEvent.click(screen.getByTestId('release-escrow-esc-1'));
    expect(onRelease).toHaveBeenCalledWith('esc-1');
  });

  it('calls onRefund when refund button clicked', () => {
    const onRefund = vi.fn();
    render(<EscrowMonitor onRefund={onRefund} />);
    fireEvent.click(screen.getByTestId('refund-escrow-esc-1'));
    expect(onRefund).toHaveBeenCalledWith('esc-1');
  });

  it('does not show release buttons for non-funded transactions', () => {
    render(<EscrowMonitor />);
    expect(screen.queryByTestId('release-escrow-esc-2')).toBeNull();
    expect(screen.queryByTestId('release-escrow-esc-3')).toBeNull();
  });
});

describe('Dispute Resolver', () => {
  const transactionMap: Record<string, EscrowTransaction> = {};
  mockTransactions.forEach(t => { transactionMap[t.id] = t; });

  it('renders open dispute cases', () => {
    render(<DisputeResolver disputes={mockDisputes} transactionMap={transactionMap} />);
    expect(screen.getByTestId('dispute-row-disp-1')).toBeDefined();
    expect(screen.getByText('منتج غير مطابق للوصف')).toBeDefined();
  });

  it('shows empty message when no open disputes', () => {
    render(<DisputeResolver disputes={[]} transactionMap={transactionMap} />);
    expect(screen.getByText('لا توجد نزاعات مفتوحة')).toBeDefined();
  });

  it('opens resolve form when resolve button clicked', () => {
    render(<DisputeResolver disputes={mockDisputes} transactionMap={transactionMap} />);
    fireEvent.click(screen.getByTestId('resolve-dispute-disp-1'));
    expect(screen.getByTestId('resolve-form')).toBeDefined();
  });

  it('calls onResolve with correct decision', () => {
    const onResolve = vi.fn();
    render(<DisputeResolver disputes={mockDisputes} transactionMap={transactionMap} onResolve={onResolve} />);
    fireEvent.click(screen.getByTestId('resolve-dispute-disp-1'));
    fireEvent.change(screen.getByTestId('decision-select'), { target: { value: 'split' } });
    fireEvent.change(screen.getByTestId('decision-reason-input'), { target: { value: 'مسؤولية مشتركة' } });
    fireEvent.click(screen.getByTestId('confirm-resolve-btn'));
    expect(onResolve).toHaveBeenCalledWith('disp-1', 'split', 'مسؤولية مشتركة');
  });
});

describe('Unit Helpers', () => {
  it('calculates total held correctly', () => {
    const total = calculateTotalHeld(mockTransactions);
    // Only esc-1 is funded: 12,500,000 + 125,000 = 12,625,000
    expect(total).toBe(12_625_000);
  });

  it('counts open disputes correctly', () => {
    expect(countOpenDisputes(mockDisputes)).toBe(1);
    expect(countOpenDisputes([])).toBe(0);
  });

  it('formats money correctly', () => {
    expect(formatMoney(12_500_000)).toContain('12.50');
    expect(formatMoney(750_000)).toContain('0.75');
  });
});
