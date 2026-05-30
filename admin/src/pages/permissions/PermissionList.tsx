import React, { useState, useEffect, useCallback, useRef } from 'react';
import { permissionApi, Permission, PaginatedResponse } from '../../services/api';

const MODULES = [
  'all',
  'users',
  'roles',
  'permissions',
  'transactions',
  'wallets',
  'kyc',
  'reports',
  'settings',
  'notifications',
];

const MODULE_LABELS: Record<string, string> = {
  all: 'جميع الوحدات',
  users: 'المستخدمين',
  roles: 'الأدوار',
  permissions: 'الصلاحيات',
  transactions: 'المعاملات',
  wallets: 'المحافظ',
  kyc: 'التوثيق',
  reports: 'التقارير',
  settings: 'الإعدادات',
  notifications: 'الإشعارات',
};

const PermissionList: React.FC = () => {
  const [permissions, setPermissions] = useState<Permission[]>([]);
  const [meta, setMeta] = useState<PaginatedResponse<Permission>['meta'] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [moduleFilter, setModuleFilter] = useState('all');

  const fetchPermissions = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params: Record<string, unknown> = { page, per_page: 30 };
      if (moduleFilter !== 'all') params.module = moduleFilter;
      const result = await permissionApi.list(params);
      setPermissions(result.data);
      setMeta(result.meta);
    } catch {
      setError('فشل في تحميل الصلاحيات');
    } finally {
      setLoading(false);
    }
  }, [page, moduleFilter]);

  useEffect(() => {
    fetchPermissions();
  }, [fetchPermissions]);

  const handleDelete = async (id: string, name: string) => {
    if (!window.confirm(`هل أنت متأكد من حذف الصلاحية "${name}"؟`)) return;
    try {
      await permissionApi.remove(id);
      fetchPermissions();
    } catch {
      alert('فشل في حذف الصلاحية. تأكد من أنها غير مرتبطة بدور.');
    }
  };

  const totalPages = meta ? Math.ceil(meta.total / meta.per_page) : 1;

  return (
    <div style={styles.container}>
      <div style={styles.header}>
        <h1 style={styles.title}>إدارة الصلاحيات</h1>
      </div>

      <div style={styles.filtersRow}>
        <select
          value={moduleFilter}
          onChange={(e) => {
            setModuleFilter(e.target.value);
            setPage(1);
          }}
          style={styles.select}
        >
          {MODULES.map((mod) => (
            <option key={mod} value={mod}>
              {MODULE_LABELS[mod] || mod}
            </option>
          ))}
        </select>
        {meta && (
          <span style={styles.count}>إجمالي {meta.total} صلاحية</span>
        )}
      </div>

      {loading ? (
        <div style={styles.centerState}>
          <div style={styles.spinner} />
          <p>جاري التحميل...</p>
        </div>
      ) : error ? (
        <div style={styles.centerState}>
          <p style={{ color: '#C62828' }}>{error}</p>
          <button onClick={fetchPermissions} style={styles.retryBtn}>
            إعادة المحاولة
          </button>
        </div>
      ) : permissions.length === 0 ? (
        <div style={styles.centerState}>
          <p>لا توجد صلاحيات</p>
        </div>
      ) : (
        <>
          <div style={styles.tableWrapper}>
            <table style={styles.table}>
              <thead>
                <tr>
                  <th style={styles.th}>الاسم</th>
                  <th style={styles.th}>الوحدة</th>
                  <th style={styles.th}>الوصف</th>
                  <th style={styles.th}>تاريخ الإنشاء</th>
                  <th style={styles.th}>الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                {permissions.map((perm) => (
                  <tr key={perm.id} style={styles.tr}>
                    <td style={styles.td}>
                      <span style={styles.permName}>{perm.name}</span>
                    </td>
                    <td style={styles.td}>
                      <span
                        style={{
                          ...styles.badge,
                          color: '#1565C0',
                          backgroundColor: '#E3F2FD',
                        }}
                      >
                        {MODULE_LABELS[perm.module] || perm.module}
                      </span>
                    </td>
                    <td style={styles.td}>{perm.description || '—'}</td>
                    <td style={styles.td}>
                      {new Date(perm.created_at).toLocaleDateString('ar-SA')}
                    </td>
                    <td style={styles.td}>
                      <button
                        onClick={() => handleDelete(perm.id, perm.name)}
                        style={{
                          ...styles.actionBtn,
                          backgroundColor: '#FFEBEE',
                          color: '#C62828',
                        }}
                      >
                        حذف
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>

          <div style={styles.pagination}>
            <button
              disabled={page <= 1}
              onClick={() => setPage((p) => Math.max(1, p - 1))}
              style={{ ...styles.pageBtn, opacity: page <= 1 ? 0.5 : 1 }}
            >
              السابق
            </button>
            <span style={styles.pageInfo}>
              صفحة {page} من {totalPages}
            </span>
            <button
              disabled={page >= totalPages}
              onClick={() => setPage((p) => Math.min(totalPages, p + 1))}
              style={{ ...styles.pageBtn, opacity: page >= totalPages ? 0.5 : 1 }}
            >
              التالي
            </button>
          </div>
        </>
      )}
    </div>
  );
};

const styles: Record<string, React.CSSProperties> = {
  container: {
    direction: 'rtl',
    padding: '24px',
    fontFamily: "'Noto Naskh Arabic', 'Tajawal', sans-serif",
  },
  header: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: '24px',
  },
  title: {
    fontSize: '24px',
    fontWeight: 700,
    color: '#1B5E20',
    margin: 0,
  },
  filtersRow: {
    display: 'flex',
    gap: '12px',
    marginBottom: '20px',
    alignItems: 'center',
  },
  select: {
    padding: '10px 16px',
    borderRadius: '8px',
    border: '1px solid #E0E0E0',
    fontSize: '14px',
    backgroundColor: '#fff',
    minWidth: '180px',
    outline: 'none',
  },
  count: {
    fontSize: '14px',
    color: '#757575',
  },
  tableWrapper: {
    overflowX: 'auto',
    borderRadius: '12px',
    border: '1px solid #E0E0E0',
    backgroundColor: '#fff',
  },
  table: {
    width: '100%',
    borderCollapse: 'collapse',
    fontSize: '14px',
  },
  th: {
    padding: '14px 16px',
    textAlign: 'right',
    backgroundColor: '#F5F5F5',
    color: '#424242',
    fontWeight: 600,
    borderBottom: '2px solid #E0E0E0',
    whiteSpace: 'nowrap',
  },
  tr: {
    borderBottom: '1px solid #F0F0F0',
  },
  td: {
    padding: '14px 16px',
    textAlign: 'right',
    color: '#212121',
  },
  permName: {
    fontWeight: 500,
    color: '#212121',
  },
  badge: {
    display: 'inline-block',
    padding: '4px 12px',
    borderRadius: '20px',
    fontSize: '12px',
    fontWeight: 600,
  },
  actionBtn: {
    padding: '6px 14px',
    borderRadius: '6px',
    border: 'none',
    fontSize: '13px',
    fontWeight: 500,
    cursor: 'pointer',
  },
  pagination: {
    display: 'flex',
    justifyContent: 'center',
    alignItems: 'center',
    gap: '16px',
    marginTop: '20px',
  },
  pageBtn: {
    padding: '8px 20px',
    borderRadius: '8px',
    border: '1px solid #E0E0E0',
    backgroundColor: '#fff',
    cursor: 'pointer',
    fontSize: '14px',
    color: '#212121',
  },
  pageInfo: {
    fontSize: '14px',
    color: '#616161',
  },
  centerState: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
    padding: '80px 0',
    color: '#757575',
  },
  spinner: {
    width: '40px',
    height: '40px',
    border: '4px solid #E0E0E0',
    borderTop: '4px solid #2E7D32',
    borderRadius: '50%',
    animation: 'spin 1s linear infinite',
    marginBottom: '16px',
  },
  retryBtn: {
    padding: '10px 24px',
    borderRadius: '8px',
    border: 'none',
    backgroundColor: '#2E7D32',
    color: '#fff',
    fontSize: '14px',
    cursor: 'pointer',
  },
};

export default PermissionList;
