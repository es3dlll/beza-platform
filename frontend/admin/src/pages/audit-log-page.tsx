import { useState, useEffect, useCallback } from 'react';
import { apiClient } from '../core/api-client';
import { ApiRoutes } from '../core/constants';

interface AuditEntry {
  id: string;
  user_id: string;
  action: string;
  resource_type: string;
  resource_id: string;
  metadata: Record<string, unknown>;
  result: string;
  ip_address: string | null;
  created_at: string;
}

const statusColors: Record<string, string> = {
  success: '#1a6b4e',
  failure: '#c0392b',
  pending: '#e6a817',
};

const actionLabels: Record<string, string> = {
  wallet_transfer: 'تحويل محفظة',
  agent_registered: 'تسجيل وكيل',
  notification_sent: 'إشعار',
  notification_failed: 'فشل إشعار',
  remittance_initiated: 'بدء حوالة',
  remittance_completed: 'إتمام حوالة',
  bill_payment: 'دفع فاتورة',
};

export function AuditLogPage() {
  const [logs, setLogs] = useState<AuditEntry[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [actionFilter, setActionFilter] = useState('');
  const [resultFilter, setResultFilter] = useState('');
  const [page, setPage] = useState(1);
  const [totalPages, setTotalPages] = useState(1);
  const [detailEntry, setDetailEntry] = useState<AuditEntry | null>(null);

  const fetchLogs = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params = new URLSearchParams();
      if (actionFilter) params.set('action', actionFilter);
      if (resultFilter) params.set('result', resultFilter);
      params.set('page', String(page));
      params.set('per_page', '20');

      const res = await apiClient.get<AuditEntry[]>(`${ApiRoutes.auditLogs}?${params}`);
      if (res.success && res.data) {
        setLogs(res.data as AuditEntry[]);
        const resData = res as Record<string, unknown>;
        const meta = resData.meta as { current_page: number; last_page: number } | undefined;
        if (meta) {
          setTotalPages(meta.last_page);
        }
      } else {
        setError(res.message || 'فشل جلب سجل التدقيق');
      }
    } catch {
      setError('حدث خطأ في الاتصال بالخادم');
    } finally {
      setLoading(false);
    }
  }, [actionFilter, resultFilter, page]);

  useEffect(() => { fetchLogs(); }, [fetchLogs]);

  const renderMetadata = (meta: Record<string, unknown>) => {
    if (!meta || Object.keys(meta).length === 0) return '-';
    return (
      <div style={{ fontSize: 12 }}>
        {Object.entries(meta).map(([k, v]) => (
          <div key={k}><strong>{k}:</strong> {typeof v === 'object' ? JSON.stringify(v) : String(v)}</div>
        ))}
      </div>
    );
  };

  return (
    <div dir="rtl" style={{ padding: 24 }}>
      <h2>سجل التدقيق</h2>

      {detailEntry && (
        <div style={{
          position: 'fixed', top: 0, left: 0, right: 0, bottom: 0,
          background: 'rgba(0,0,0,0.5)', display: 'flex', alignItems: 'center', justifyContent: 'center', zIndex: 1000,
        }} onClick={() => setDetailEntry(null)}>
          <div style={{
            background: 'white', padding: 24, borderRadius: 8, maxWidth: 500, width: '90%',
            maxHeight: '80vh', overflow: 'auto',
          }} onClick={(e) => e.stopPropagation()}>
            <h3 style={{ marginTop: 0 }}>تفاصيل سجل التدقيق</h3>
            <table style={{ width: '100%', borderCollapse: 'collapse' }}>
              <tbody>
                {Object.entries(detailEntry).map(([k, v]) => (
                  <tr key={k} style={{ borderBottom: '1px solid #eee' }}>
                    <td style={{ padding: 6, fontWeight: 'bold', width: 120 }}>{k}</td>
                    <td style={{ padding: 6 }}>
                      {k === 'metadata' ? renderMetadata(v as Record<string, unknown>)
                        : k === 'created_at' ? new Date(v as string).toLocaleString('ar-SA')
                        : String(v)}
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
            <button
              onClick={() => { navigator.clipboard.writeText(detailEntry.id); }}
              style={{ marginTop: 16, padding: '8px 16px', cursor: 'pointer' }}
            >
              نسخ المعرف
            </button>
            <button
              onClick={() => setDetailEntry(null)}
              style={{ marginTop: 16, marginRight: 8, padding: '8px 16px', cursor: 'pointer' }}
            >
              إغلاق
            </button>
          </div>
        </div>
      )}

      {/* Filters */}
      <div style={{ marginBottom: 16, display: 'flex', gap: 8, flexWrap: 'wrap', alignItems: 'center' }}>
        <select
          value={actionFilter}
          onChange={(e) => { setActionFilter(e.target.value); setPage(1); }}
          style={{ padding: 8, borderRadius: 4, border: '1px solid #ccc' }}
        >
          <option value="">جميع الأنواع</option>
          <option value="wallet_transfer">تحويل محفظة</option>
          <option value="agent_registered">تسجيل وكيل</option>
          <option value="remittance_initiated">بدء حوالة</option>
          <option value="remittance_completed">إتمام حوالة</option>
          <option value="notification_sent">إشعار</option>
        </select>

        <select
          value={resultFilter}
          onChange={(e) => { setResultFilter(e.target.value); setPage(1); }}
          style={{ padding: 8, borderRadius: 4, border: '1px solid #ccc' }}
        >
          <option value="">جميع الحالات</option>
          <option value="success">نجاح</option>
          <option value="failure">فشل</option>
          <option value="pending">معلق</option>
        </select>

        <button onClick={() => { setPage(1); fetchLogs(); }} style={{ padding: '8px 16px', cursor: 'pointer' }}>
          تحديث
        </button>
      </div>

      {/* Table */}
      {loading ? (
        <p>جاري التحميل...</p>
      ) : error ? (
        <p style={{ color: 'red' }}>{error}</p>
      ) : logs.length === 0 ? (
        <p>لا توجد سجلات</p>
      ) : (
        <>
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <thead>
              <tr style={{ background: '#f5f5f5' }}>
                <th style={thStyle}>نوع العملية</th>
                <th style={thStyle}>الحالة</th>
                <th style={thStyle}>المبلغ (فلس)</th>
                <th style={thStyle}>المرجع</th>
                <th style={thStyle}>التاريخ</th>
                <th style={thStyle}>إجراءات</th>
              </tr>
            </thead>
            <tbody>
              {logs.map((entry) => {
                const meta = entry.metadata || {};
                return (
                  <tr key={entry.id} style={{ borderBottom: '1px solid #ddd' }}>
                    <td style={tdStyle}>{actionLabels[entry.action] || entry.action}</td>
                    <td style={tdStyle}>
                      <span style={{
                        padding: '4px 8px', borderRadius: 4, color: 'white',
                        background: statusColors[entry.result] || '#999', fontSize: 13,
                      }}>
                        {entry.result === 'success' ? 'نجاح' : entry.result === 'failure' ? 'فشل' : entry.result}
                      </span>
                    </td>
                    <td style={tdStyle}>{String(meta.amount_fils ?? '-')}</td>
                    <td style={tdStyle} title={entry.resource_id}>
                      {entry.resource_id ? `${entry.resource_id.substring(0, 16)}...` : '-'}
                    </td>
                    <td style={tdStyle}>{new Date(entry.created_at).toLocaleString('ar-SA')}</td>
                    <td style={tdStyle}>
                      <button onClick={() => setDetailEntry(entry)} style={{ padding: '4px 8px', cursor: 'pointer', fontSize: 12 }}>
                        تفاصيل
                      </button>
                    </td>
                  </tr>
                );
              })}
            </tbody>
          </table>

          {/* Pagination */}
          <div style={{ marginTop: 16, display: 'flex', justifyContent: 'center', gap: 8, alignItems: 'center' }}>
            <button disabled={page <= 1} onClick={() => setPage(page - 1)}
              style={{ padding: '8px 16px', cursor: page <= 1 ? 'not-allowed' : 'pointer', borderRadius: 4, border: '1px solid #ccc' }}>
              السابق
            </button>
            <span>{page} / {totalPages}</span>
            <button disabled={page >= totalPages} onClick={() => setPage(page + 1)}
              style={{ padding: '8px 16px', cursor: page >= totalPages ? 'not-allowed' : 'pointer', borderRadius: 4, border: '1px solid #ccc' }}>
              التالي
            </button>
          </div>
        </>
      )}
    </div>
  );
}

const thStyle: React.CSSProperties = { padding: 10, textAlign: 'right', borderBottom: '2px solid #ddd', fontSize: 14 };
const tdStyle: React.CSSProperties = { padding: 10, textAlign: 'right', fontSize: 13 };
