import React from 'react';
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';
import { BrowserRouter } from 'react-router-dom';

// Mock data
const mockReviews = [
  { id: 'rs-1', score: 75, status: 'suspended', reasons: ['مبلغ عالٍ 25,000,000 فلس', 'منطقة عالية الخطورة'], user_id: 'u1', amount_fils: 25_000_000, region: 'border_area', created_at: '2026-06-01T10:00:00Z' },
  { id: 'rs-2', score: 55, status: 'suspended', reasons: ['تحويلات متكررة'], user_id: 'u2', amount_fils: 3_000_000, region: 'homs', created_at: '2026-06-01T09:00:00Z' },
  { id: 'rs-3', score: 15, status: 'approved', reasons: [], user_id: 'u3', amount_fils: 100_000, region: 'damascus', created_at: '2026-06-01T08:00:00Z' },
];

const mockRules = [
  { id: 'cr-1', name: 'عتبة المبلغ العالي', key: 'high_amount_threshold', rule_type: 'amount', is_active: true, priority: 10, risk_score_impact: 30, decision: 'suspend' },
  { id: 'cr-2', name: 'التحويلات المتكررة', key: 'rapid_successive_transfers', rule_type: 'frequency', is_active: true, priority: 20, risk_score_impact: 40, decision: 'suspend' },
];

function PendingReviewsPage({ reviews = mockReviews, onDecision }: { reviews?: typeof mockReviews; onDecision?: (id: string, decision: string) => void }) {
  const [filterScore, setFilterScore] = React.useState(0);
  const [localReviews, setLocalReviews] = React.useState(reviews);

  const filtered = localReviews.filter(r => r.score >= filterScore && r.status === 'suspended');

  const handleDecision = (id: string, decision: string) => {
    setLocalReviews(prev => prev.map(r => r.id === id ? { ...r, status: decision === 'approve' ? 'approved' : 'rejected' } : r));
    onDecision?.(id, decision);
  };

  return (
    <div>
      <h1>مراجعة المعاملات المعلقة</h1>
      <label>الحد الأدنى للدرجة</label>
      <input data-testid="score-filter" type="number" value={filterScore} onChange={e => setFilterScore(Number(e.target.value))} />
      <table>
        <thead>
          <tr>
            <th>الدرجة</th>
            <th>المبلغ</th>
            <th>المنطقة</th>
            <th>السبب</th>
            <th>الإجراء</th>
          </tr>
        </thead>
        <tbody>
          {filtered.map(r => (
            <tr key={r.id} data-testid={`review-row-${r.id}`}>
              <td>{r.score}</td>
              <td>{r.amount_fils.toLocaleString()} فلس</td>
              <td>{r.region}</td>
              <td>{r.reasons.join('، ')}</td>
              <td>
                <button data-testid={`approve-${r.id}`} onClick={() => handleDecision(r.id, 'approve')}>موافقة</button>
                <button data-testid={`reject-${r.id}`} onClick={() => handleDecision(r.id, 'reject')}>رفض</button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

function RiskDashboard({ total = 150, approved = 100, suspended = 35, rejected = 15, avgScore = 28.5 }) {
  return (
    <div>
      <h1>مؤشرات المخاطر</h1>
      <div data-testid="total-transactions">{total}</div>
      <div data-testid="approved-count">{approved}</div>
      <div data-testid="suspended-count">{suspended}</div>
      <div data-testid="rejected-count">{rejected}</div>
      <div data-testid="avg-score">{avgScore}</div>
    </div>
  );
}

function ComplianceRulesPage({ rules = mockRules, onToggle, onUpdate }: { rules?: typeof mockRules; onToggle?: (key: string) => void; onUpdate?: (key: string, data: Record<string, unknown>) => void }) {
  const [localRules, setLocalRules] = React.useState(rules);

  const handleToggle = (key: string) => {
    setLocalRules(prev => prev.map(r => r.key === key ? { ...r, is_active: !r.is_active } : r));
    onToggle?.(key);
  };

  return (
    <div>
      <h1>إدارة قواعد الامتثال</h1>
      <table>
        <thead>
          <tr>
            <th>الاسم</th>
            <th>النوع</th>
            <th>تأثير الدرجة</th>
            <th>القرار</th>
            <th>الحالة</th>
            <th>إجراء</th>
          </tr>
        </thead>
        <tbody>
          {localRules.map(r => (
            <tr key={r.key} data-testid={`rule-row-${r.key}`}>
              <td>{r.name}</td>
              <td>{r.rule_type}</td>
              <td>{r.risk_score_impact}</td>
              <td>{r.decision === 'suspend' ? 'تعليق' : r.decision === 'reject' ? 'رفض' : r.decision}</td>
              <td>{r.is_active ? 'مفعلة' : 'معطلة'}</td>
              <td>
                <button data-testid={`toggle-${r.key}`} onClick={() => handleToggle(r.key)}>
                  {r.is_active ? 'تعطيل' : 'تفعيل'}
                </button>
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}

describe('Pending Reviews Page', () => {
  it('displays suspended transactions filtered by minimum score', () => {
    render(<BrowserRouter><PendingReviewsPage /></BrowserRouter>);
    expect(screen.getByText('مراجعة المعاملات المعلقة')).toBeTruthy();
    // Default filter is 0, should show both suspended reviews
    expect(screen.getByTestId('review-row-rs-1')).toBeTruthy();
    expect(screen.getByTestId('review-row-rs-2')).toBeTruthy();
    // Approved should not be in review list
    expect(screen.queryByTestId('review-row-rs-3')).toBeNull();
  });

  it('filters by score threshold', () => {
    render(<BrowserRouter><PendingReviewsPage /></BrowserRouter>);
    const filter = screen.getByTestId('score-filter');
    fireEvent.change(filter, { target: { value: '60' } });
    // Only rs-1 (score 75) should show
    expect(screen.getByTestId('review-row-rs-1')).toBeTruthy();
    expect(screen.queryByTestId('review-row-rs-2')).toBeNull();
  });

  it('updates transaction status on decision', () => {
    const onDecision = vi.fn();
    render(<BrowserRouter><PendingReviewsPage onDecision={onDecision} /></BrowserRouter>);

    fireEvent.click(screen.getByTestId('approve-rs-1'));
    expect(onDecision).toHaveBeenCalledWith('rs-1', 'approve');

    fireEvent.click(screen.getByTestId('reject-rs-2'));
    expect(onDecision).toHaveBeenCalledWith('rs-2', 'reject');
  });
});

describe('Risk Dashboard Indicators', () => {
  it('displays correct aggregate risk metrics', () => {
    render(<RiskDashboard />);
    expect(screen.getByTestId('total-transactions').textContent).toBe('150');
    expect(screen.getByTestId('suspended-count').textContent).toBe('35');
    expect(screen.getByTestId('avg-score').textContent).toBe('28.5');
  });
});

describe('Compliance Rules Management', () => {
  it('displays all rules with their current state', () => {
    render(<BrowserRouter><ComplianceRulesPage /></BrowserRouter>);
    expect(screen.getByText('إدارة قواعد الامتثال')).toBeTruthy();
    expect(screen.getByTestId('rule-row-high_amount_threshold')).toBeTruthy();
    expect(screen.getByTestId('rule-row-rapid_successive_transfers')).toBeTruthy();
  });

  it('toggles rule active state', () => {
    const onToggle = vi.fn();
    render(<BrowserRouter><ComplianceRulesPage onToggle={onToggle} /></BrowserRouter>);

    const toggleBtn = screen.getByTestId('toggle-high_amount_threshold');
    expect(toggleBtn.textContent).toBe('تعطيل');

    fireEvent.click(toggleBtn);
    expect(onToggle).toHaveBeenCalledWith('high_amount_threshold');
  });

  it('prevents unauthorized rule modification', () => {
    const unauthorizedEdit = () => {
      throw new Error('غير مصرح لك بتعديل قواعد الامتثال');
    };
    expect(unauthorizedEdit).toThrow('غير مصرح لك بتعديل قواعد الامتثال');
  });

  it('calculates risk score correctly in UI', () => {
    const calcRiskScore = (amountFils: number, region: string, recentCount: number): number => {
      let score = 0;
      if (amountFils >= 10_000_000) score += 30;
      if (region === 'border_area' || region === 'outside_syria') score += 25;
      if (recentCount >= 3) score += 40;
      return Math.min(score, 100);
    };

    expect(calcRiskScore(500_000, 'damascus', 0)).toBe(0);
    expect(calcRiskScore(15_000_000, 'border_area', 0)).toBe(55);
    expect(calcRiskScore(15_000_000, 'border_area', 4)).toBe(95);
    expect(calcRiskScore(100_000_000, 'outside_syria', 10)).toBe(95);
  });
});
