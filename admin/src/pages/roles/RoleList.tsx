import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { roleApi, Role, PaginatedResponse } from '../../services/api';

const RoleList: React.FC = () => {
  const [roles, setRoles] = useState<Role[]>([]);
  const [meta, setMeta] = useState<PaginatedResponse<Role>['meta'] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');

  const fetchRoles = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params: Record<string, unknown> = { page, per_page: 20 };
      if (search) params.search = search;
      const result = await roleApi.list(params);
      setRoles(result.data);
      setMeta(result.meta);
    } catch {
      setError('فشل في تحميل الأدوار');
    } finally {
      setLoading(false);
    }
  }, [page, search]);

  useEffect(() => {
    fetchRoles();
  }, [fetchRoles]);

  const handleDelete = async (id: string, name: string) => {
    if (!window.confirm(`هل أنت متأكد من حذف الدور "${name}"؟`)) return;
    try {
      await roleApi.remove(id);
      fetchRoles();
    } catch {
      alert('فشل في حذف الدور');
    }
  };

  const totalPages = meta ? Math.ceil(meta.total / meta.per_page) : 1;

  return (
    <div style={styles.container}>
      <div style={styles.header}>
        <h1 style={styles.title}>إدارة الأدوار</h1>
        <Link to="/admin/roles/new" style={styles.addBtn}>
          إضافة دور جديد
        </Link>
      </div>

      <div style={styles.filtersRow}>
        <input
          type="text"
          placeholder="بحث..."
          value={search}
          onChange={(e) => {
            setSearch(e.target.value);
            setPage(1);
          }}
          style={styles.searchInput}
        />
      </div>

      {loading ? (
        <div style={styles.centerState}>
          <div style={styles.spinner} />
          <p>جاري التحميل...</p>
        </div>
      ) : error ? (
        <div style={styles.centerState}>
          <p style={{ color: '#C62828' }}>{error}</p>
          <button onClick={fetchRoles} style={styles.retryBtn}>إعادة المحاولة</button>
        </div>
      ) : roles.length === 0 ? (
        <div style={styles.centerState}>
          <p>لا توجد أدوار</p>
        </div>
      ) : (
        <>
          <div style={styles.tableWrapper}>
            <table style={styles.table}>
              <thead>
                <tr>
                  <th style={styles.th}>الاسم</th>
                  <th style={styles.th}>الوصف</th>
                  <th style={styles.th}>عدد الصلاحيات</th>
                  <th style={styles.th}>عدد المستخدمين</th>
                  <th style={styles.th}>نظام</th>
                  <th style={styles.th}>تاريخ الإنشاء</th>
                  <th style={styles.th}>الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                {roles.map((role) => (
                  <tr key={role.id} style={styles.tr}>
                    <td style={styles.td}>
                      <Link to={`/admin/roles/${role.id}/edit`} style={styles.link}>
                        {role.name}
                      </Link>
                    </td>
                    <td style={styles.td}>{role.description || '—'}</td>
                    <td style={styles.td}>{role.permissions_count}</td>
                    <td style={styles.td}>{role.users_count}</td>
                    <td style={styles.td}>
                      {role.is_system && (
                        <span style={{ ...styles.badge, color: '#E65100', backgroundColor: '#FFF3E0' }}>
                          نظام
                        </span>
                      )}
                    </td>
                    <td style={styles.td}>{new Date(role.created_at).toLocaleDateString('ar-SA')}</td>
                    <td style={styles.td}>
                      <div style={styles.actions}>
                        <Link
                          to={`/admin/roles/${role.id}/edit`}
                          style={{ ...styles.actionBtn, backgroundColor: '#E8F5E9', color: '#2E7D32' }}
                        >
                          تعديل
                        </Link>
                        {!role.is_system && (
                          <button
                            onClick={() => handleDelete(role.id, role.name)}
                            style={{ ...styles.actionBtn, backgroundColor: '#FFEBEE', color: '#C62828' }}
                          >
                            حذف
                          </button>
                        )}
                      </div>
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
  addBtn: {
    padding: '10px 20px',
    borderRadius: '8px',
    border: 'none',
    backgroundColor: '#2E7D32',
    color: '#fff',
    fontSize: '14px',
    fontWeight: 600,
    cursor: 'pointer',
    textDecoration: 'none',
  },
  filtersRow: {
    marginBottom: '20px',
  },
  searchInput: {
    padding: '10px 16px',
    borderRadius: '8px',
    border: '1px solid #E0E0E0',
    fontSize: '14px',
    minWidth: '250px',
    outline: 'none',
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
  link: {
    color: '#2E7D32',
    textDecoration: 'none',
    fontWeight: 500,
  },
  badge: {
    display: 'inline-block',
    padding: '4px 12px',
    borderRadius: '20px',
    fontSize: '12px',
    fontWeight: 600,
  },
  actions: {
    display: 'flex',
    gap: '8px',
  },
  actionBtn: {
    padding: '6px 14px',
    borderRadius: '6px',
    border: 'none',
    fontSize: '13px',
    fontWeight: 500,
    cursor: 'pointer',
    textDecoration: 'none',
    display: 'inline-block',
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

export default RoleList;
