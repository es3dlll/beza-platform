import React, { useState, useCallback } from 'react';
import { transactionApi, Transaction, PaginatedResponse } from '../../services/api';

const STATUS_MAP: Record<string, { label: string; color: string; bg: string }> = {
  pending: { label: 'معلق', color: '#E65100', bg: '#FFF3E0' },
  completed: { label: 'مكتمل', color: '#1B5E20', bg: '#E8F5E9' },
  failed: { label: 'فاشل', color: '#C62828', bg: '#FFEBEE' },
  reversed: { label: 'ملغي', color: '#616161', bg: '#F5F5F5' },
};

const TransactionSearch: React.FC = () => {
  const [transactions, setTransactions] = useState<Transaction[]>([]);
  const [meta, setMeta] = useState<PaginatedResponse<Transaction>['meta'] | null>(null);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [status, setStatus] = useState('');
  const [dateFrom, setDateFrom] = useState('');
  const [dateTo, setDateTo] = useState('');

  const searchTransactions = useCallback(async (p = 1) => {
    setLoading(true);
    setError(null);
    try {
      const params: Record<string, unknown> = { page: p, per_page: 15 };
      if (search) params.search = search;
      if (status) params.status = status;
      if (dateFrom) params.date_from = dateFrom;
      if (dateTo) params.date_to = dateTo;
      const result = await transactionApi.list(params);
      setTransactions(result.data);
      setMeta(result.meta);
      setPage(p);
    } catch {
      setError('فشل في البحث');
    } finally {
      setLoading(false);
    }
  }, [search, status, dateFrom, dateTo]);

  const handleReverse = async (id: string) => {
    const reason = window.prompt('سبب الإلغاء:');
    if (!reason) return;
    try {
      await transactionApi.reverse(id, reason);
      alert('تم إلغاء العملية');
      searchTransactions(page);
    } catch {
      setError('فشل في إلغاء العملية');
    }
  };

  const formatCurrency = (n: number) => n.toLocaleString('ar-SA');

  return (
    <div style={{ padding: '24px', direction: 'rtl' }}>
      <h1 style={styles.title}>البحث في العمليات</h1>

      <div style={styles.filters}>
        <input
          style={styles.input}
          placeholder="رقم الجوال أو رقم العملية"
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
        <select style={styles.input} value={status} onChange={(e) => setStatus(e.target.value)}>
          <option value="">جميع الحالات</option>
          <option value="pending">معلق</option>
          <option value="completed">مكتمل</option>
          <option value="failed">فاشل</option>
          <option value="reversed">ملغي</option>
        </select>
        <input type="date" style={styles.input} value={dateFrom} onChange={(e) => setDateFrom(e.target.value)} />
        <input type="date" style={styles.input} value={dateTo} onChange={(e) => setDateTo(e.target.value)} />
        <button style={styles.searchBtn} onClick={() => searchTransactions()}>بحث</button>
      </div>

      {error && <div style={styles.error}>{error}</div>}

      {loading ? (
        <div style={styles.loading}>جاري التحميل...</div>
      ) : transactions.length > 0 ? (
        <>
          <table style={styles.table}>
            <thead>
              <tr>
                <th style={styles.th}>النوع</th>
                <th style={styles.th}>المبلغ</th>
                <th style={styles.th}>المرسل</th>
                <th style={styles.th}>المستلم</th>
                <th style={styles.th}>الحالة</th>
                <th style={styles.th}>التاريخ</th>
                <th style={styles.th}>إجراءات</th>
              </tr>
            </thead>
            <tbody>
              {transactions.map((t) => {
                const s = STATUS_MAP[t.status] || STATUS_MAP.pending;
                return (
                  <tr key={t.id} style={styles.tr}>
                    <td style={styles.td}>{t.type}</td>
                    <td style={styles.td}>{formatCurrency(t.amount)} {t.currency}</td>
                    <td style={styles.td}>{t.sender_phone}</td>
                    <td style={styles.td}>{t.receiver_phone || '-'}</td>
                    <td style={styles.td}>
                      <span style={{ ...styles.badge, backgroundColor: s.bg, color: s.color }}>{s.label}</span>
                    </td>
                    <td style={styles.td}>{new Date(t.created_at).toLocaleDateString('ar')}</td>
                    <td style={styles.td}>
                      {t.status === 'completed' && (
                        <button style={styles.reverseBtn} onClick={() => handleReverse(t.id)}>إلغاء</button>
                      )}
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>

          {meta && (
            <div style={styles.pagination}>
              <button disabled={page <= 1} onClick={() => searchTransactions(page - 1)} style={styles.pageBtn}>السابق</button>
              <span>صفحة {meta.current_page} من {meta.last_page}</span>
              <button disabled={page >= meta.last_page} onClick={() => searchTransactions(page + 1)} style={styles.pageBtn}>التالي</button>
            </div>
          )}
        </>
      ) : (
        <div style={styles.empty}>استخدم البحث لعرض العمليات</div>
      )}
    </div>
  );
};

const styles: Record<string, React.CSSProperties> = {
  title: { fontSize: 24, fontWeight: 700, marginBottom: 24, color: '#1B5E20' },
  error: { backgroundColor: '#FFEBEE', color: '#C62828', padding: 12, borderRadius: 8, marginBottom: 16 },
  loading: { textAlign: 'center', padding: 48, color: '#666' },
  empty: { textAlign: 'center', padding: 48, color: '#666', fontSize: 18 },
  filters: { display: 'flex', gap: 12, marginBottom: 24, flexWrap: 'wrap' },
  input: { padding: '10px 16px', borderRadius: 8, border: '1px solid #D0D0D0', fontSize: 14, minWidth: 160 },
  searchBtn: { padding: '10px 24px', borderRadius: 8, border: 'none', backgroundColor: '#1B5E20', color: '#fff', fontSize: 14, cursor: 'pointer' },
  table: { width: '100%', borderCollapse: 'collapse', backgroundColor: '#fff', borderRadius: 12, overflow: 'hidden', boxShadow: '0 2px 8px rgba(0,0,0,0.06)' },
  th: { textAlign: 'right', padding: '12px 16px', backgroundColor: '#F5F5F5', fontWeight: 600, fontSize: 14, borderBottom: '2px solid #E0E0E0' },
  tr: { borderBottom: '1px solid #F0F0F0' },
  td: { padding: '12px 16px', fontSize: 14 },
  badge: { display: 'inline-block', padding: '4px 12px', borderRadius: 12, fontSize: 12, fontWeight: 600 },
  reverseBtn: { padding: '6px 16px', borderRadius: 8, border: 'none', backgroundColor: '#E65100', color: '#fff', fontSize: 13, cursor: 'pointer' },
  pagination: { display: 'flex', justifyContent: 'center', alignItems: 'center', gap: 16, marginTop: 24 },
  pageBtn: { padding: '8px 24px', borderRadius: 8, border: '1px solid #D0D0D0', backgroundColor: '#fff', cursor: 'pointer', fontSize: 14 },
};

export default TransactionSearch;
