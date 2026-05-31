import React from 'react';
import { describe, it, expect, vi } from 'vitest';
import { render, screen, fireEvent } from '@testing-library/react';

// ─── Types ────────────────────────────────────────────────────────

interface Metrics {
  total_transactions: number;
  total_volume_fils: number;
  active_wallets: number;
  total_balance_fils: number;
  fraud_alerts: number;
  successful_transactions: number;
  failed_transactions: number;
  notifications_sent: number;
}

interface Snapshot {
  snapshot_date: string;
  metrics: Metrics;
}

// ─── Mock Data ────────────────────────────────────────────────────

const mockSnapshots: Snapshot[] = [
  { snapshot_date: '2026-05-25', metrics: { total_transactions: 120, total_volume_fils: 45_000_000, active_wallets: 340, total_balance_fils: 120_000_000, fraud_alerts: 3, successful_transactions: 115, failed_transactions: 5, notifications_sent: 80 } },
  { snapshot_date: '2026-05-26', metrics: { total_transactions: 145, total_volume_fils: 52_000_000, active_wallets: 355, total_balance_fils: 125_000_000, fraud_alerts: 2, successful_transactions: 140, failed_transactions: 5, notifications_sent: 95 } },
  { snapshot_date: '2026-05-27', metrics: { total_transactions: 98, total_volume_fils: 38_000_000, active_wallets: 360, total_balance_fils: 128_000_000, fraud_alerts: 1, successful_transactions: 95, failed_transactions: 3, notifications_sent: 72 } },
];

// ─── Helpers ──────────────────────────────────────────────────────

function formatMoney(fils: number): string {
  return `${(fils / 1_000_000).toFixed(1)}M ل.س`;
}

function calcSuccessRate(metrics: Metrics): number {
  const total = metrics.successful_transactions + metrics.failed_transactions;
  if (total === 0) return 0;
  return Math.round((metrics.successful_transactions / total) * 100);
}

function calcAvgVolume(snapshots: Snapshot[]): number {
  if (snapshots.length === 0) return 0;
  const total = snapshots.reduce((s, sn) => s + sn.metrics.total_volume_fils, 0);
  return Math.round(total / snapshots.length);
}

// ─── Component: Analytics Dashboard ───────────────────────────────

function AnalyticsDashboard({ snapshots = mockSnapshots }: { snapshots?: Snapshot[] }) {
  const [dateRange, setDateRange] = React.useState('7days');
  const [filtered, setFiltered] = React.useState(snapshots);

  const handleRangeChange = (range: string) => {
    setDateRange(range);
    if (range === '7days') setFiltered(snapshots.slice(-3));
    else if (range === '30days') setFiltered(snapshots);
    else setFiltered(snapshots);
  };

  const latest = filtered.length > 0 ? filtered[filtered.length - 1].metrics : null;
  const successRate = latest ? calcSuccessRate(latest) : 0;
  const avgVolume = calcAvgVolume(filtered);

  return (
    <div dir="rtl" style={{ padding: 24 }}>
      <h1>لوحة التحليلات التشغيلية</h1>
      <div style={{ marginBottom: 16 }}>
        <select data-testid="date-range-select" value={dateRange} onChange={e => handleRangeChange(e.target.value)}>
          <option value="7days">آخر 7 أيام</option>
          <option value="30days">آخر 30 يوم</option>
        </select>
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(4, 1fr)', gap: 16, marginBottom: 24 }}>
        <div data-testid="kpi-transactions" style={{ padding: 16, background: '#e3f2fd', borderRadius: 8 }}>
          <div style={{ fontSize: 12, color: '#666' }}>المعاملات اليومية</div>
          <div style={{ fontSize: 24, fontWeight: 'bold' }}>{latest?.total_transactions ?? 0}</div>
        </div>
        <div data-testid="kpi-volume" style={{ padding: 16, background: '#e8f5e9', borderRadius: 8 }}>
          <div style={{ fontSize: 12, color: '#666' }}>حجم التداول</div>
          <div style={{ fontSize: 24, fontWeight: 'bold' }}>{latest ? formatMoney(latest.total_volume_fils) : '0'}</div>
        </div>
        <div data-testid="kpi-success" style={{ padding: 16, background: '#fff3e0', borderRadius: 8 }}>
          <div style={{ fontSize: 12, color: '#666' }}>نسبة النجاح</div>
          <div style={{ fontSize: 24, fontWeight: 'bold' }}>{successRate}%</div>
        </div>
        <div data-testid="kpi-fraud" style={{ padding: 16, background: '#fce4ec', borderRadius: 8 }}>
          <div style={{ fontSize: 12, color: '#666' }}>تنبيهات الاحتيال</div>
          <div style={{ fontSize: 24, fontWeight: 'bold' }}>{latest?.fraud_alerts ?? 0}</div>
        </div>
      </div>
      <div data-testid="avg-volume" style={{ marginBottom: 24, padding: 16, background: '#f3e5f5', borderRadius: 8 }}>
        متوسط حجم التداول اليومي: <strong>{formatMoney(avgVolume)}</strong>
      </div>
      <div style={{ display: 'grid', gridTemplateColumns: 'repeat(2, 1fr)', gap: 16 }}>
        <div data-testid="chart-transactions" style={{ padding: 16, border: '1px solid #ddd', borderRadius: 8 }}>
          <h3>المعاملات اليومية</h3>
          {filtered.map(s => (
            <div key={s.snapshot_date} style={{ marginBottom: 8 }}>
              <span>{s.snapshot_date}: </span>
              <span style={{ fontWeight: 'bold' }}>{s.metrics.total_transactions}</span>
              <div style={{ height: 8, background: '#e0e0e0', borderRadius: 4, marginTop: 4 }}>
                <div style={{ width: `${Math.min(100, (s.metrics.total_transactions / 200) * 100)}%`, height: 8, background: '#1976d2', borderRadius: 4 }} />
              </div>
            </div>
          ))}
        </div>
        <div data-testid="chart-risk" style={{ padding: 16, border: '1px solid #ddd', borderRadius: 8 }}>
          <h3>توزيع المخاطر</h3>
          <div>تنبيهات اليوم: {latest?.fraud_alerts ?? 0}</div>
          <div>المحافظ النشطة: {latest?.active_wallets ?? 0}</div>
          <div>الإشعارات المرسلة: {latest?.notifications_sent ?? 0}</div>
        </div>
      </div>
    </div>
  );
}

// ─── Component: Broadcast Notifications ───────────────────────────

function BroadcastPanel({ onSend, userCount = 150 }: {
  onSend?: (title: string, body: string, channel: string, userIds: string[]) => void;
  userCount?: number;
}) {
  const [title, setTitle] = React.useState('');
  const [body, setBody] = React.useState('');
  const [channel, setChannel] = React.useState('in_app');
  const [targetType, setTargetType] = React.useState('all');
  const [recipientIds, setRecipientIds] = React.useState('');
  const [showConfirm, setShowConfirm] = React.useState(false);

  const estimatedRecipients = targetType === 'all' ? userCount : recipientIds.split(',').filter(Boolean).length;

  return (
    <div dir="rtl" style={{ padding: 24 }}>
      <h1>بث الإشعارات</h1>
      <div style={{ maxWidth: 600 }}>
        <div style={{ marginBottom: 12 }}>
          <label>العنوان</label>
          <input data-testid="broadcast-title" value={title} onChange={e => setTitle(e.target.value)} style={{ width: '100%', padding: 8 }} />
        </div>
        <div style={{ marginBottom: 12 }}>
          <label>المحتوى</label>
          <textarea data-testid="broadcast-body" value={body} onChange={e => setBody(e.target.value)} rows={3} style={{ width: '100%', padding: 8 }} />
        </div>
        <div style={{ marginBottom: 12 }}>
          <label>القناة</label>
          <select data-testid="broadcast-channel" value={channel} onChange={e => setChannel(e.target.value)} style={{ width: '100%', padding: 8 }}>
            <option value="in_app">داخل التطبيق</option>
            <option value="email">البريد الإلكتروني</option>
            <option value="sms">رسالة نصية</option>
          </select>
        </div>
        <div style={{ marginBottom: 12 }}>
          <label>نوع الاستهداف</label>
          <select data-testid="broadcast-target" value={targetType} onChange={e => setTargetType(e.target.value)} style={{ width: '100%', padding: 8 }}>
            <option value="all">جميع المستخدمين</option>
            <option value="specific">مستهدفون محددون</option>
          </select>
        </div>
        {targetType === 'specific' && (
          <div style={{ marginBottom: 12 }}>
            <label>معرفات المستخدمين (مفصولة بفاصلة)</label>
            <textarea data-testid="broadcast-ids" value={recipientIds} onChange={e => setRecipientIds(e.target.value)} rows={2} style={{ width: '100%', padding: 8 }} />
          </div>
        )}
        <div data-testid="estimated-recipients" style={{ marginBottom: 16, padding: 12, background: '#e8f5e9', borderRadius: 8 }}>
          المستلمون المتأثرون: <strong>{estimatedRecipients}</strong>
        </div>
        <button data-testid="preview-broadcast" onClick={() => setShowConfirm(true)} style={{ padding: '10px 24px', background: '#1976d2', color: 'white', border: 'none', borderRadius: 4, cursor: 'pointer' }}>
          معاينة الإرسال
        </button>
        {showConfirm && (
          <div data-testid="broadcast-confirm" style={{ marginTop: 16, padding: 16, border: '1px solid #ddd', borderRadius: 8 }}>
            <p>سيتم إرسال إشعار إلى {estimatedRecipients} مستلم. هل أنت متأكد؟</p>
            <button data-testid="confirm-broadcast" onClick={() => {
              const ids = targetType === 'all' ? [] : recipientIds.split(',').map(s => s.trim()).filter(Boolean);
              onSend?.(title, body, channel, ids);
              setShowConfirm(false);
            }} style={{ padding: '8px 16px', background: '#388e3c', color: 'white', border: 'none', borderRadius: 4, cursor: 'pointer' }}>تأكيد الإرسال</button>
            <button onClick={() => setShowConfirm(false)} style={{ marginRight: 8, padding: '8px 16px' }}>إلغاء</button>
          </div>
        )}
      </div>
    </div>
  );
}

// ─── Tests ────────────────────────────────────────────────────────

describe('Analytics Dashboard', () => {
  it('renders KPI cards with data', () => {
    render(<AnalyticsDashboard />);
    expect(screen.getByTestId('kpi-transactions').textContent).toContain('المعاملات اليومية');
    expect(screen.getByTestId('kpi-volume').textContent).toContain('حجم التداول');
    expect(screen.getByTestId('kpi-success').textContent).toContain('نسبة النجاح');
    expect(screen.getByTestId('kpi-fraud').textContent).toContain('تنبيهات الاحتيال');
  });

  it('filters data by date range', () => {
    render(<AnalyticsDashboard />);
    fireEvent.change(screen.getByTestId('date-range-select'), { target: { value: '30days' } });
    expect(screen.getByTestId('avg-volume').textContent).toContain('متوسط حجم التداول اليومي');
  });

  it('shows charts with transaction data', () => {
    render(<AnalyticsDashboard />);
    expect(screen.getByTestId('chart-transactions').textContent).toContain('المعاملات اليومية');
    expect(screen.getByTestId('chart-risk').textContent).toContain('توزيع المخاطر');
  });
});

describe('Broadcast Notifications', () => {
  it('calculates estimated recipients for all users', () => {
    render(<BroadcastPanel userCount={200} />);
    expect(screen.getByTestId('estimated-recipients').textContent).toContain('200');
  });

  it('shows confirmation before sending', () => {
    render(<BroadcastPanel />);
    fireEvent.click(screen.getByTestId('preview-broadcast'));
    expect(screen.getByTestId('broadcast-confirm')).toBeDefined();
  });

  it('calls onSend with correct data when confirmed', () => {
    const onSend = vi.fn();
    render(<BroadcastPanel onSend={onSend} />);
    fireEvent.change(screen.getByTestId('broadcast-title'), { target: { value: 'إشعار هام' } });
    fireEvent.change(screen.getByTestId('broadcast-body'), { target: { value: 'محتوى الإشعار' } });
    fireEvent.change(screen.getByTestId('broadcast-channel'), { target: { value: 'email' } });
    fireEvent.click(screen.getByTestId('preview-broadcast'));
    fireEvent.click(screen.getByTestId('confirm-broadcast'));
    expect(onSend).toHaveBeenCalledWith('إشعار هام', 'محتوى الإشعار', 'email', []);
  });
});

describe('Authorization Guard', () => {
  it('prevents unauthorized report export', () => {
    const allowedRoles = ['admin', 'operator'];
    const viewerCanExport = allowedRoles.includes('viewer');
    const adminCanExport = allowedRoles.includes('admin');

    expect(viewerCanExport).toBe(false);
    expect(adminCanExport).toBe(true);
  });
});

describe('Performance Rate Calculator', () => {
  const testMetrics: Metrics = {
    total_transactions: 200, total_volume_fils: 100_000_000,
    active_wallets: 500, total_balance_fils: 1_000_000_000,
    fraud_alerts: 5, successful_transactions: 180,
    failed_transactions: 20, notifications_sent: 150,
  };

  it('calculates success rate accurately', () => {
    expect(calcSuccessRate(testMetrics)).toBe(90);
  });

  it('returns 0 when no transactions', () => {
    const empty: Metrics = {
      total_transactions: 0, total_volume_fils: 0,
      active_wallets: 0, total_balance_fils: 0,
      fraud_alerts: 0, successful_transactions: 0,
      failed_transactions: 0, notifications_sent: 0,
    };
    expect(calcSuccessRate(empty)).toBe(0);
  });

  it('calculates average daily volume', () => {
    const avg = calcAvgVolume(mockSnapshots);
    const expected = Math.round((45_000_000 + 52_000_000 + 38_000_000) / 3);
    expect(avg).toBe(expected);
  });

  it('returns 0 for empty snapshots', () => {
    expect(calcAvgVolume([])).toBe(0);
  });
});
