import React, { useState, useEffect, useCallback } from 'react';
import { agentApi, Agent, PaginatedResponse } from '../../services/api';

const STATUS_MAP: Record<string, { label: string; color: string; bg: string }> = {
  pending: { label: 'قيد الانتظار', color: '#E65100', bg: '#FFF3E0' },
  approved: { label: 'معتمد', color: '#1B5E20', bg: '#E8F5E9' },
  suspended: { label: 'موقوف', color: '#C62828', bg: '#FFEBEE' },
  rejected: { label: 'مرفوض', color: '#616161', bg: '#F5F5F5' },
};

const AgentList: React.FC = () => {
  const [agents, setAgents] = useState<Agent[]>([]);
  const [meta, setMeta] = useState<PaginatedResponse<Agent>['meta'] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [actionLoading, setActionLoading] = useState<string | null>(null);

  const fetch = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await agentApi.list({ page, per_page: 15 });
      setAgents(result.data);
      setMeta(result.meta);
    } catch {
      setError('فشل في تحميل الوكلاء');
    } finally {
      setLoading(false);
    }
  }, [page]);

  useEffect(() => { fetch(); }, [fetch]);

  const handleApprove = async (id: string) => {
    setActionLoading(id);
    try { await agentApi.approve(id); fetch(); }
    catch { setError('فشل في اعتماد الوكيل'); }
    finally { setActionLoading(null); }
  };

  const handleSuspend = async (id: string) => {
    const reason = window.prompt('سبب الإيقاف:');
    if (!reason) return;
    setActionLoading(id);
    try { await agentApi.suspend(id, reason); fetch(); }
    catch { setError('فشل في إيقاف الوكيل'); }
    finally { setActionLoading(null); }
  };

  const formatCurrency = (n: number) => n.toLocaleString('ar-SA');

  return (
    <div style={{ padding: '24px', direction: 'rtl' }}>
      <h1 style={styles.title}>إدارة الوكلاء</h1>

      {error && <div style={styles.error}>{error}</div>}

      {loading ? (
        <div style={styles.loading}>جاري التحميل...</div>
      ) : agents.length === 0 ? (
        <div style={styles.empty}>لا يوجد وكلاء</div>
      ) : (
        <>
          <table style={styles.table}>
            <thead>
              <tr>
                <th style={styles.th}>الاسم</th>
                <th style={styles.th}>المتجر</th>
                <th style={styles.th}>الجوال</th>
                <th style={styles.th}>المحافظة</th>
                <th style={styles.th}>الحالة</th>
                <th style={styles.th}>الرصيد</th>
                <th style={styles.th}>إجراءات</th>
              </tr>
            </thead>
            <tbody>
              {agents.map((a) => {
                const s = STATUS_MAP[a.status];
                return (
                  <tr key={a.id} style={styles.tr}>
                    <td style={styles.td}>{a.full_name}</td>
                    <td style={styles.td}>{a.shop_name}</td>
                    <td style={styles.td}>{a.phone}</td>
                    <td style={styles.td}>{a.governorate}</td>
                    <td style={styles.td}>
                      <span style={{ ...styles.badge, backgroundColor: s.bg, color: s.color }}>
                        {s.label}
                      </span>
                    </td>
                    <td style={styles.td}>{formatCurrency(a.float_balance)} SYP</td>
                    <td style={styles.td}>
                      {a.status === 'pending' && (
                        <button
                          style={styles.approveBtn}
                          disabled={actionLoading === a.id}
                          onClick={() => handleApprove(a.id)}
                        >
                          اعتماد
                        </button>
                      )}
                      {a.status === 'approved' && (
                        <button
                          style={styles.suspendBtn}
                          disabled={actionLoading === a.id}
                          onClick={() => handleSuspend(a.id)}
                        >
                          إيقاف
                        </button>
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>

          {meta && (
            <div style={styles.pagination}>
              <button disabled={page <= 1} onClick={() => setPage((p) => p - 1)} style={styles.pageBtn}>السابق</button>
              <span>صفحة {meta.current_page} من {meta.last_page}</span>
              <button disabled={page >= meta.last_page} onClick={() => setPage((p) => p + 1)} style={styles.pageBtn}>التالي</button>
            </div>
          )}
        </>
      )}
    </div>
  );
};

const styles: Record<string, React.CSSProperties> = {
  title: { fontSize: 24, fontWeight: 700, marginBottom: 24, color: '#1B5E20' },
  error: { backgroundColor: '#FFEBEE', color: '#C62828', padding: 12, borderRadius: 8, marginBottom: 16 },
  loading: { textAlign: 'center', padding: 48, color: '#666' },
  empty: { textAlign: 'center', padding: 48, color: '#666', fontSize: 18 },
  table: { width: '100%', borderCollapse: 'collapse', backgroundColor: '#fff', borderRadius: 12, overflow: 'hidden', boxShadow: '0 2px 8px rgba(0,0,0,0.06)' },
  th: { textAlign: 'right', padding: '12px 16px', backgroundColor: '#F5F5F5', fontWeight: 600, fontSize: 14, borderBottom: '2px solid #E0E0E0' },
  tr: { borderBottom: '1px solid #F0F0F0' },
  td: { padding: '12px 16px', fontSize: 14 },
  badge: { display: 'inline-block', padding: '4px 12px', borderRadius: 12, fontSize: 12, fontWeight: 600 },
  approveBtn: { padding: '6px 16px', borderRadius: 8, border: 'none', backgroundColor: '#1B5E20', color: '#fff', fontSize: 13, cursor: 'pointer' },
  suspendBtn: { padding: '6px 16px', borderRadius: 8, border: 'none', backgroundColor: '#C62828', color: '#fff', fontSize: 13, cursor: 'pointer' },
  pagination: { display: 'flex', justifyContent: 'center', alignItems: 'center', gap: 16, marginTop: 24 },
  pageBtn: { padding: '8px 24px', borderRadius: 8, border: '1px solid #D0D0D0', backgroundColor: '#fff', cursor: 'pointer', fontSize: 14 },
};

export default AgentList;
