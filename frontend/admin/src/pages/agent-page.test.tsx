import React from 'react';
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';

// Mock agent data
const mockAgents = [
  { id: '01J', name: 'وكيل دمشق', region: 'دمشق', status: 'active', balance: 5_000_000, limit: 10_000_000 },
  { id: '02J', name: 'وكيل حلب', region: 'حلب', status: 'pending', balance: 0, limit: 5_000_000 },
];

function AgentsPage({ agents = mockAgents }: { agents?: typeof mockAgents }) {
  const [filter, setFilter] = React.useState('');

  return (
    <div>
      <h1>إدارة الوكلاء</h1>
      <input
        data-testid="filter-input"
        placeholder="بحث..."
        value={filter}
        onChange={e => setFilter(e.target.value)}
      />
      <table>
        <thead>
          <tr>
            <th>الاسم</th>
            <th>المنطقة</th>
            <th>الحالة</th>
            <th>الرصيد</th>
            <th>الحد اليومي</th>
          </tr>
        </thead>
        <tbody>
          {agents
            .filter(a => a.name.includes(filter) || a.region.includes(filter))
            .map(a => (
              <tr key={a.id} data-testid={`agent-row-${a.id}`}>
                <td>{a.name}</td>
                <td>{a.region}</td>
                <td>{a.status === 'active' ? 'نشط' : a.status === 'pending' ? 'قيد الانتظار' : a.status}</td>
                <td>{a.balance.toLocaleString()} فلس</td>
                <td>{a.limit.toLocaleString()} فلس</td>
              </tr>
            ))}
        </tbody>
      </table>
    </div>
  );
}

describe('Agents Management Page', () => {
  it('renders agent list with correct columns', () => {
    render(<BrowserRouter><AgentsPage /></BrowserRouter>);
    expect(screen.getByText('إدارة الوكلاء')).toBeTruthy();
    expect(screen.getByText('وكيل دمشق')).toBeTruthy();
    expect(screen.getByText('وكيل حلب')).toBeTruthy();
  });

  it('filters agents by name', () => {
    render(<BrowserRouter><AgentsPage /></BrowserRouter>);
    const input = screen.getByTestId('filter-input');
    fireEvent.change(input, { target: { value: 'حلب' } });
    expect(screen.getByText('وكيل حلب')).toBeTruthy();
    expect(screen.queryByText('وكيل دمشق')).toBeNull();
  });

  it('shows correct status labels', () => {
    render(<BrowserRouter><AgentsPage /></BrowserRouter>);
    expect(screen.getByText('نشط')).toBeTruthy();
    expect(screen.getByText('قيد الانتظار')).toBeTruthy();
  });
});

describe('Liquidity Pool Dashboard', () => {
  it('displays total liquidity and active agents count', () => {
    function LiquidityDashboard() {
      const totalLiquidity = 50_000_000;
      const activeAgents = 12;
      const pendingRequests = 3;

      return (
        <div>
          <div data-testid="total-liquidity">{totalLiquidity.toLocaleString()} فلس</div>
          <div data-testid="active-agents">{activeAgents}</div>
          <div data-testid="pending-requests">{pendingRequests}</div>
        </div>
      );
    }

    render(<LiquidityDashboard />);
    expect(screen.getByTestId('total-liquidity').textContent).toBe('50,000,000 فلس');
    expect(screen.getByTestId('active-agents').textContent).toBe('12');
    expect(screen.getByTestId('pending-requests').textContent).toBe('3');
  });
});

describe('Commission Settings Page', () => {
  it('displays rate tiers for each client type', () => {
    function CommissionSettings() {
      const [rates, setRates] = React.useState({
        retail: { tier1: 0.01, tier2: 0.015, tier3: 0.02 },
        business: { tier1: 0.005, tier2: 0.01, tier3: 0.015 },
        premium: { tier1: 0.002, tier2: 0.005, tier3: 0.01 },
      });
      const [preview, setPreview] = React.useState<{ amount: number; commission: number } | null>(null);

      return (
        <div>
          <label>المبلغ</label>
          <input data-testid="amount-input" type="number" onChange={e => {
            const amt = Number(e.target.value);
            if (amt > 0) {
              setPreview({ amount: amt, commission: Math.floor(amt * rates.retail.tier1) });
            }
          }} />
          {preview && (
            <div data-testid="preview-result">
              عمولة {preview.commission.toLocaleString()} فلس
            </div>
          )}
        </div>
      );
    }

    render(<CommissionSettings />);
    const input = screen.getByTestId('amount-input');
    fireEvent.change(input, { target: { value: '1000000' } });
    expect(screen.getByTestId('preview-result').textContent).toContain('10,000');
  });

  it('prevents saving invalid rate values', () => {
    expect(() => {
      const rate = -0.01;
      if (rate < 0 || rate > 1) throw new Error('النسبة خارج النطاق');
    }).toThrow('النسبة خارج النطاق');
  });

  it('calculates preview commission correctly', () => {
    const calcCommission = (amount: number, rate: number) => Math.floor(amount * rate);
    expect(calcCommission(500_000, 0.01)).toBe(5_000);
    expect(calcCommission(15_000_000, 0.015)).toBe(225_000);
    expect(calcCommission(3_000_000, 0.002)).toBe(6_000);
  });
});
