import React, { useState, useEffect, useCallback } from 'react';
import { fraudApi, FraudCase, PaginatedResponse } from '../../services/api';

const STATUS_MAP: Record<string, { label: string; color: string; bg: string }> = {
  open: { label: 'مفتوحة', color: '#E65100', bg: '#FFF3E0' },
  investigating: { label: 'قيد التحقيق', color: '#1565C0', bg: '#E3F2FD' },
  resolved: { label: 'تم الحل', color: '#1B5E20', bg: '#E8F5E9' },
  dismissed: { label: 'مرفوضة', color: '#616161', bg: '#F5F5F5' },
};

const DECISION_MAP: Record<string, string> = {
  allow: 'سماح',
  block: 'حظر',
  review: 'مراجعة',
};

const getScoreColor = (score: number) => {
  if (score >= 900) return '#C62828';
  if (score >= 700) return '#E65100';
  return '#2E7D32';
};

const FraudCases: React.FC = () => {
  const [cases, setCases] = useState<FraudCase[]>([]);
  const [meta, setMeta] = useState<PaginatedResponse<FraudCase>['meta'] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);

  const fetch = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await fraudApi.listCases({ page, per_page: 15 });
      setCases(result.data);
      setMeta(result.meta);
    } catch {
      setError('فشل في تحميل حالات الاحتيال');
    } finally {
      setLoading(false);
    }
  }, [page]);

  useEffect(() => { fetch(); }, [fetch]);

  const handleReview = async (caseId: string, decision: string) => {
    const notes = window.prompt('ملاحظات (اختياري):');
    try {
      await fraudApi.reviewCase(caseId, { decision, notes: notes || undefined });
      fetch();
    } catch {
      setError('فشل في تحديث الحالة');
    }
  };

  return (
    <div style={{ padding: '24px', direction: 'rtl' }}>
      <h1 style={styles.title}>حالات الاحتيال</h1>

      {error && <div style={styles.error}>{error}</div>}

      {loading ? (
        <div style={styles.loading}>جاري التحميل...</div>
      ) : cases.length === 0 ? (
        <div style={styles.empty}>لا توجد حالات احتيال</div>
      ) : (
        <>
          <table style={styles.table}>
            <thead>
              <tr>
                <th style={styles.th}>رقم الحالة</th>
                <th style={styles.th}>المستخدم</th>
                <th style={styles.th}>درجة الخطورة</th>
                <th style={styles.th}>القاعدة</th>
                <th style={styles.th}>الحالة</th>
                <th style={styles.th}>القرار</th>
                <th style={styles.th}>التاريخ</th>
                <th style={styles.th}>إجراءات</th>
              </tr>
            </thead>
            <tbody>
              {cases.map((c) => {
                const s = STATUS_MAP[c.status];
                return (
                  <tr key={c.id} style={styles.tr}>
                    <td style={styles.td}>{c.id.slice(0, 8)}</td>
                    <td style={styles.td}>{c.user_phone}</td>
                    <td style={styles.td}>
                      <span style={{
                        ...styles.score,
                        backgroundColor: getScoreColor(c.risk_score) + '20',
                        color: getScoreColor(c.risk_score),
                      }}>
                        {c.risk_score}
                      </span>
                    </td>
                    <td style={styles.td}>{c.rule_name}</td>
                    <td style={styles.td}>
                      <span style={{ ...styles.badge, backgroundColor: s.bg, color: s.color }}>{s.label}</span>
                    </td>
                    <td style={styles.td}>{c.decision ? DECISION_MAP[c.decision] : '-'}</td>
                    <td style={styles.td}>{new Date(c.created_at).toLocaleDateString('ar')}</td>
                    <td style={styles.td}>
                      {c.status !== 'resolved' && c.status !== 'dismissed' && (
                        <div style={{ display: 'flex', gap: 4 }}>
                          <button style={styles.allowBtn} onClick={() => handleReview(c.id, 'allow')}>سماح</button>
                          <button style={styles.blockBtn} onClick={() => handleReview(c.id, 'block')}>حظر</button>
                          <button style={styles.reviewBtn} onClick={() => handleReview(c.id, 'review')}>مراجعة</button>
                        </div>
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
  score: { display: 'inline-block', padding: '4px 10px', borderRadius: 8, fontSize: 13, fontWeight: 700 },
  badge: { display: 'inline-block', padding: '4px 12px', borderRadius: 12, fontSize: 12, fontWeight: 600 },
  allowBtn: { padding: '4px 12px', borderRadius: 6, border: 'none', backgroundColor: '#1B5E20', color: '#fff', fontSize: 12, cursor: 'pointer' },
  blockBtn: { padding: '4px 12px', borderRadius: 6, border: 'none', backgroundColor: '#C62828', color: '#fff', fontSize: 12, cursor: 'pointer' },
  reviewBtn: { padding: '4px 12px', borderRadius: 6, border: 'none', backgroundColor: '#E65100', color: '#fff', fontSize: 12, cursor: 'pointer' },
  pagination: { display: 'flex', justifyContent: 'center', alignItems: 'center', gap: 16, marginTop: 24 },
  pageBtn: { padding: '8px 24px', borderRadius: 8, border: '1px solid #D0D0D0', backgroundColor: '#fff', cursor: 'pointer', fontSize: 14 },
};

export default FraudCases;
