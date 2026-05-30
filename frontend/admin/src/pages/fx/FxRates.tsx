import React, { useState, useEffect, useCallback } from 'react';
import { fxApi, ExchangeRate, PaginatedResponse } from '../../services/api';

const SOURCE_MAP: Record<string, string> = {
  cbs: 'المصرف المركزي',
  market: 'السوق',
  manual: 'يدوي',
};

const FxRates: React.FC = () => {
  const [rates, setRates] = useState<ExchangeRate[]>([]);
  const [meta, setMeta] = useState<PaginatedResponse<ExchangeRate>['meta'] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [showForm, setShowForm] = useState(false);
  const [newRate, setNewRate] = useState({ base_currency: 'SYP', quote_currency: 'USD', rate: 0 });

  const fetch = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const result = await fxApi.listRates({ page, per_page: 15 });
      setRates(result.data);
      setMeta(result.meta);
    } catch {
      setError('فشل في تحميل أسعار الصرف');
    } finally {
      setLoading(false);
    }
  }, [page]);

  useEffect(() => { fetch(); }, [fetch]);

  const handleCreate = async () => {
    if (newRate.rate <= 0) { alert('الرجاء إدخال سعر صحيح'); return; }
    try {
      await fxApi.createRate({ ...newRate, source: 'manual' } as ExchangeRate);
      setShowForm(false);
      fetch();
    } catch {
      setError('فشل في إضافة السعر');
    }
  };

  return (
    <div style={{ padding: '24px', direction: 'rtl' }}>
      <div style={{ display: 'flex', justifyContent: 'space-between', alignItems: 'center', marginBottom: 24 }}>
        <h1 style={{ fontSize: 24, fontWeight: 700, color: '#1B5E20', margin: 0 }}>أسعار الصرف</h1>
        <button style={styles.addBtn} onClick={() => setShowForm(true)}>إضافة سعر يدوي</button>
      </div>

      {error && <div style={styles.error}>{error}</div>}

      {showForm && (
        <div style={styles.form}>
          <select style={styles.input} value={newRate.base_currency} onChange={(e) => setNewRate({ ...newRate, base_currency: e.target.value })}>
            <option value="SYP">SYP</option>
            <option value="USD">USD</option>
          </select>
          <span style={{ fontSize: 18 }}>→</span>
          <select style={styles.input} value={newRate.quote_currency} onChange={(e) => setNewRate({ ...newRate, quote_currency: e.target.value })}>
            <option value="USD">USD</option>
            <option value="SYP">SYP</option>
          </select>
          <input
            style={styles.input}
            type="number"
            step="0.01"
            placeholder="سعر الصرف"
            value={newRate.rate || ''}
            onChange={(e) => setNewRate({ ...newRate, rate: parseFloat(e.target.value) })}
          />
          <button style={styles.saveBtn} onClick={handleCreate}>حفظ</button>
          <button style={styles.cancelBtn} onClick={() => setShowForm(false)}>إلغاء</button>
        </div>
      )}

      {loading ? (
        <div style={styles.loading}>جاري التحميل...</div>
      ) : rates.length > 0 ? (
        <>
          <table style={styles.table}>
            <thead>
              <tr>
                <th style={styles.th}>زوج العملات</th>
                <th style={styles.th}>السعر</th>
                <th style={styles.th}>المصدر</th>
                <th style={styles.th}>الحالة</th>
                <th style={styles.th}>صالح حتى</th>
                <th style={styles.th}>التاريخ</th>
              </tr>
            </thead>
            <tbody>
              {rates.map((r) => (
                <tr key={r.id} style={styles.tr}>
                  <td style={styles.td}>{r.base_currency}/{r.quote_currency}</td>
                  <td style={styles.td}>{r.rate.toFixed(4)}</td>
                  <td style={styles.td}>{SOURCE_MAP[r.source] || r.source}</td>
                  <td style={styles.td}>
                    <span style={{
                      ...styles.badge,
                      backgroundColor: r.is_active ? '#E8F5E9' : '#FFEBEE',
                      color: r.is_active ? '#1B5E20' : '#C62828',
                    }}>
                      {r.is_active ? 'نشط' : 'غير نشط'}
                    </span>
                  </td>
                  <td style={styles.td}>{r.valid_until ? new Date(r.valid_until).toLocaleDateString('ar') : '-'}</td>
                  <td style={styles.td}>{new Date(r.created_at).toLocaleDateString('ar')}</td>
                </tr>
              ))}
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
      ) : (
        <div style={styles.empty}>لا توجد أسعار صرف</div>
      )}
    </div>
  );
};

const styles: Record<string, React.CSSProperties> = {
  error: { backgroundColor: '#FFEBEE', color: '#C62828', padding: 12, borderRadius: 8, marginBottom: 16 },
  loading: { textAlign: 'center', padding: 48, color: '#666' },
  empty: { textAlign: 'center', padding: 48, color: '#666', fontSize: 18 },
  addBtn: { padding: '10px 24px', borderRadius: 8, border: 'none', backgroundColor: '#1B5E20', color: '#fff', fontSize: 14, cursor: 'pointer' },
  form: { display: 'flex', gap: 8, alignItems: 'center', padding: 16, backgroundColor: '#F5F5F5', borderRadius: 12, marginBottom: 16, flexWrap: 'wrap' },
  input: { padding: '8px 12px', borderRadius: 6, border: '1px solid #D0D0D0', fontSize: 14, minWidth: 100 },
  saveBtn: { padding: '8px 20px', borderRadius: 6, border: 'none', backgroundColor: '#1B5E20', color: '#fff', fontSize: 14, cursor: 'pointer' },
  cancelBtn: { padding: '8px 20px', borderRadius: 6, border: '1px solid #D0D0D0', backgroundColor: '#fff', fontSize: 14, cursor: 'pointer' },
  table: { width: '100%', borderCollapse: 'collapse', backgroundColor: '#fff', borderRadius: 12, overflow: 'hidden', boxShadow: '0 2px 8px rgba(0,0,0,0.06)' },
  th: { textAlign: 'right', padding: '12px 16px', backgroundColor: '#F5F5F5', fontWeight: 600, fontSize: 14, borderBottom: '2px solid #E0E0E0' },
  tr: { borderBottom: '1px solid #F0F0F0' },
  td: { padding: '12px 16px', fontSize: 14 },
  badge: { display: 'inline-block', padding: '4px 12px', borderRadius: 12, fontSize: 12, fontWeight: 600 },
  pagination: { display: 'flex', justifyContent: 'center', alignItems: 'center', gap: 16, marginTop: 24 },
  pageBtn: { padding: '8px 24px', borderRadius: 8, border: '1px solid #D0D0D0', backgroundColor: '#fff', cursor: 'pointer', fontSize: 14 },
};

export default FxRates;
