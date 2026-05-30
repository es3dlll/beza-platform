import React, { useState, useEffect, useCallback } from 'react';
import { userApi, User, PaginatedResponse } from '../../services/api';

const KYC_TIERS = ['basic', 'verified', 'premium'] as const;

const KycReview: React.FC = () => {
  const [users, setUsers] = useState<User[]>([]);
  const [meta, setMeta] = useState<PaginatedResponse<User>['meta'] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [actionLoading, setActionLoading] = useState<string | null>(null);

  const fetch = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await userApi.list({ page, per_page: 15, kyc_status: 'pending' });
      setUsers(result.data);
      setMeta(result.meta);
    } catch {
      setError('فشل في تحميل طلبات التوثيق');
    } finally {
      setLoading(false);
    }
  }, [page]);

  useEffect(() => { fetch(); }, [fetch]);

  const handleApprove = async (userId: string, tier: string) => {
    setActionLoading(userId);
    try {
      await userApi.approveKyc(userId, tier);
      fetch();
    } catch {
      setError('فشل في قبول الطلب');
    } finally {
      setActionLoading(null);
    }
  };

  const handleReject = async (userId: string) => {
    const reason = window.prompt('سبب الرفض:');
    if (!reason) return;
    setActionLoading(userId);
    try {
      await userApi.rejectKyc(userId, reason);
      fetch();
    } catch {
      setError('فشل في رفض الطلب');
    } finally {
      setActionLoading(null);
    }
  };

  return (
    <div style={{ padding: '24px', direction: 'rtl' }}>
      <h1 style={styles.title}>مراجعة توثيق الهوية (KYC)</h1>

      {error && <div style={styles.error}>{error}</div>}

      {loading ? (
        <div style={styles.loading}>جاري التحميل...</div>
      ) : users.length === 0 ? (
        <div style={styles.empty}>لا توجد طلبات توثيق معلقة</div>
      ) : (
        <>
          <table style={styles.table}>
            <thead>
              <tr>
                <th style={styles.th}>الاسم</th>
                <th style={styles.th}>رقم الجوال</th>
                <th style={styles.th}>المستوى الحالي</th>
                <th style={styles.th}>تاريخ التسجيل</th>
                <th style={styles.th}>إجراءات</th>
              </tr>
            </thead>
            <tbody>
              {users.map((u) => (
                <tr key={u.id} style={styles.tr}>
                  <td style={styles.td}>{u.full_name || '-'}</td>
                  <td style={styles.td}>{u.phone}</td>
                  <td style={styles.td}>
                    <span style={{
                      ...styles.badge,
                      backgroundColor: '#FFF3E0',
                      color: '#E65100',
                    }}>{u.kyc_tier}</span>
                  </td>
                  <td style={styles.td}>{new Date(u.created_at).toLocaleDateString('ar')}</td>
                  <td style={styles.td}>
                    <select
                      style={styles.select}
                      disabled={actionLoading === u.id}
                      onChange={(e) => handleApprove(u.id, e.target.value)}
                      defaultValue=""
                    >
                      <option value="" disabled>رفع إلى...</option>
                      {KYC_TIERS.map((t) => (
                        <option key={t} value={t}>{t}</option>
                      ))}
                    </select>
                    <button
                      style={styles.rejectBtn}
                      disabled={actionLoading === u.id}
                      onClick={() => handleReject(u.id)}
                    >
                      رفض
                    </button>
                  </td>
                </tr>
              ))}
            </tbody>
          </table>

          {meta && (
            <div style={styles.pagination}>
              <button
                disabled={page <= 1}
                onClick={() => setPage((p) => p - 1)}
                style={styles.pageBtn}
              >
                السابق
              </button>
              <span>صفحة {meta.current_page} من {meta.last_page}</span>
              <button
                disabled={page >= meta.last_page}
                onClick={() => setPage((p) => p + 1)}
                style={styles.pageBtn}
              >
                التالي
              </button>
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
  select: { padding: '6px 12px', borderRadius: 8, border: '1px solid #D0D0D0', fontSize: 13, cursor: 'pointer', marginRight: 8 },
  rejectBtn: { padding: '6px 16px', borderRadius: 8, border: 'none', backgroundColor: '#C62828', color: '#fff', fontSize: 13, cursor: 'pointer' },
  pagination: { display: 'flex', justifyContent: 'center', alignItems: 'center', gap: 16, marginTop: 24 },
  pageBtn: { padding: '8px 24px', borderRadius: 8, border: '1px solid #D0D0D0', backgroundColor: '#fff', cursor: 'pointer', fontSize: 14 },
};

export default KycReview;
