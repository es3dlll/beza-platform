import React, { useState, useEffect, useCallback } from 'react';
import { Link } from 'react-router-dom';
import { userApi, User, PaginatedResponse } from '../../services/api';

const STATUS_MAP: Record<string, { label: string; color: string; bg: string }> = {
  active: { label: 'نشط', color: '#1B5E20', bg: '#E8F5E9' },
  pending: { label: 'قيد الانتظار', color: '#E65100', bg: '#FFF3E0' },
  blocked: { label: 'محظور', color: '#C62828', bg: '#FFEBEE' },
  closed: { label: 'مغلق', color: '#616161', bg: '#F5F5F5' },
};

const KYC_MAP: Record<string, { label: string; color: string; bg: string }> = {
  none: { label: 'غير موثق', color: '#9E9E9E', bg: '#FAFAFA' },
  basic: { label: 'أساسي', color: '#1565C0', bg: '#E3F2FD' },
  verified: { label: 'موثق', color: '#2E7D32', bg: '#E8F5E9' },
  premium: { label: 'ممتاز', color: '#6A1B9A', bg: '#F3E5F5' },
};

interface Filters {
  status: string;
  kyc_tier: string;
  date_from: string;
  date_to: string;
  search: string;
}

const UserList: React.FC = () => {
  const [users, setUsers] = useState<User[]>([]);
  const [meta, setMeta] = useState<PaginatedResponse<User>['meta'] | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [page, setPage] = useState(1);
  const [filters, setFilters] = useState<Filters>({
    status: '',
    kyc_tier: '',
    date_from: '',
    date_to: '',
    search: '',
  });

  const fetchUsers = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const params: Record<string, unknown> = { page, per_page: 15 };
      if (filters.status) params.status = filters.status;
      if (filters.kyc_tier) params.kyc_tier = filters.kyc_tier;
      if (filters.date_from) params.date_from = filters.date_from;
      if (filters.date_to) params.date_to = filters.date_to;
      if (filters.search) params.search = filters.search;

      const result = await userApi.list(params);
      setUsers(result.data);
      setMeta(result.meta);
    } catch (err) {
      setError('فشل في تحميل المستخدمين');
      console.error(err);
    } finally {
      setLoading(false);
    }
  }, [page, filters]);

  useEffect(() => {
    fetchUsers();
  }, [fetchUsers]);

  const handleFilterChange = (key: keyof Filters, value: string) => {
    setFilters((prev) => ({ ...prev, [key]: value }));
    setPage(1);
  };

  const handleStatusAction = async (user: User) => {
    try {
      if (user.status === 'blocked' || user.status === 'closed') {
        await userApi.activate(user.id);
      } else {
        await userApi.suspend(user.id);
      }
      fetchUsers();
    } catch (err) {
      console.error(err);
    }
  };

  const formatDate = (dateStr: string | null): string => {
    if (!dateStr) return '—';
    const d = new Date(dateStr);
    return d.toLocaleDateString('ar-SA', {
      year: 'numeric',
      month: 'short',
      day: 'numeric',
      hour: '2-digit',
      minute: '2-digit',
    });
  };

  const totalPages = meta ? Math.ceil(meta.total / meta.per_page) : 1;

  return (
    <div style={styles.container}>
      <div style={styles.header}>
        <h1 style={styles.title}>إدارة المستخدمين</h1>
        <span style={styles.count}>
          {meta ? `إجمالي ${meta.total} مستخدم` : ''}
        </span>
      </div>

      <div style={styles.filtersRow}>
        <input
          type="text"
          placeholder="بحث بالهاتف أو الاسم..."
          value={filters.search}
          onChange={(e) => handleFilterChange('search', e.target.value)}
          style={styles.searchInput}
        />
        <select
          value={filters.status}
          onChange={(e) => handleFilterChange('status', e.target.value)}
          style={styles.select}
        >
          <option value="">كل الحالات</option>
          <option value="active">نشط</option>
          <option value="pending">قيد الانتظار</option>
          <option value="blocked">محظور</option>
          <option value="closed">مغلق</option>
        </select>
        <select
          value={filters.kyc_tier}
          onChange={(e) => handleFilterChange('kyc_tier', e.target.value)}
          style={styles.select}
        >
          <option value="">كل مستويات التوثيق</option>
          <option value="none">غير موثق</option>
          <option value="basic">أساسي</option>
          <option value="verified">موثق</option>
          <option value="premium">ممتاز</option>
        </select>
        <input
          type="date"
          value={filters.date_from}
          onChange={(e) => handleFilterChange('date_from', e.target.value)}
          style={styles.dateInput}
        />
        <span style={styles.dateSep}>إلى</span>
        <input
          type="date"
          value={filters.date_to}
          onChange={(e) => handleFilterChange('date_to', e.target.value)}
          style={styles.dateInput}
        />
      </div>

      {loading ? (
        <div style={styles.loadingState}>
          <div style={styles.spinner} />
          <p>جاري التحميل...</p>
        </div>
      ) : error ? (
        <div style={styles.errorState}>
          <p style={styles.errorText}>{error}</p>
          <button onClick={fetchUsers} style={styles.retryBtn}>إعادة المحاولة</button>
        </div>
      ) : users.length === 0 ? (
        <div style={styles.emptyState}>
          <p>لا يوجد مستخدمين</p>
        </div>
      ) : (
        <>
          <div style={styles.tableWrapper}>
            <table style={styles.table}>
              <thead>
                <tr>
                  <th style={styles.th}>رقم الهاتف</th>
                  <th style={styles.th}>الاسم الكامل</th>
                  <th style={styles.th}>مستوى التوثيق</th>
                  <th style={styles.th}>الحالة</th>
                  <th style={styles.th}>تاريخ التسجيل</th>
                  <th style={styles.th}>آخر دخول</th>
                  <th style={styles.th}>الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                {users.map((user) => {
                  const statusInfo = STATUS_MAP[user.status] || STATUS_MAP.pending;
                  const kycInfo = KYC_MAP[user.kyc_tier] || KYC_MAP.none;
                  return (
                    <tr key={user.id} style={styles.tr}>
                      <td style={styles.td}>
                        <Link to={`/admin/users/${user.id}`} style={styles.link}>
                          {user.phone}
                        </Link>
                      </td>
                      <td style={styles.td}>{user.full_name || '—'}</td>
                      <td style={styles.td}>
                        <span
                          style={{
                            ...styles.badge,
                            color: kycInfo.color,
                            backgroundColor: kycInfo.bg,
                          }}
                        >
                          {kycInfo.label}
                        </span>
                      </td>
                      <td style={styles.td}>
                        <span
                          style={{
                            ...styles.badge,
                            color: statusInfo.color,
                            backgroundColor: statusInfo.bg,
                          }}
                        >
                          {statusInfo.label}
                        </span>
                      </td>
                      <td style={styles.td}>{formatDate(user.created_at)}</td>
                      <td style={styles.td}>{formatDate(user.last_login_at)}</td>
                      <td style={styles.td}>
                        <div style={styles.actions}>
                          <Link
                            to={`/admin/users/${user.id}`}
                            style={styles.actionBtn}
                          >
                            عرض
                          </Link>
                          <button
                            onClick={() => handleStatusAction(user)}
                            style={{
                              ...styles.actionBtn,
                              backgroundColor:
                                user.status === 'blocked' || user.status === 'closed'
                                  ? '#E8F5E9'
                                  : '#FFEBEE',
                              color:
                                user.status === 'blocked' || user.status === 'closed'
                                  ? '#2E7D32'
                                  : '#C62828',
                            }}
                          >
                            {user.status === 'blocked' || user.status === 'closed'
                              ? 'تفعيل'
                              : 'تعليق'}
                          </button>
                        </div>
                      </td>
                    </tr>
                  );
                })}
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
              style={{
                ...styles.pageBtn,
                opacity: page >= totalPages ? 0.5 : 1,
              }}
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
  count: {
    fontSize: '14px',
    color: '#757575',
  },
  filtersRow: {
    display: 'flex',
    gap: '12px',
    marginBottom: '20px',
    flexWrap: 'wrap',
    alignItems: 'center',
  },
  searchInput: {
    padding: '10px 16px',
    borderRadius: '8px',
    border: '1px solid #E0E0E0',
    fontSize: '14px',
    minWidth: '200px',
    outline: 'none',
  },
  select: {
    padding: '10px 16px',
    borderRadius: '8px',
    border: '1px solid #E0E0E0',
    fontSize: '14px',
    backgroundColor: '#fff',
    minWidth: '140px',
    outline: 'none',
  },
  dateInput: {
    padding: '10px 12px',
    borderRadius: '8px',
    border: '1px solid #E0E0E0',
    fontSize: '14px',
    outline: 'none',
  },
  dateSep: {
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
    backgroundColor: '#E8F5E9',
    color: '#2E7D32',
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
  loadingState: {
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
  errorState: {
    textAlign: 'center',
    padding: '60px 0',
  },
  errorText: {
    color: '#C62828',
    fontSize: '16px',
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
  emptyState: {
    textAlign: 'center',
    padding: '80px 0',
    color: '#9E9E9E',
    fontSize: '16px',
  },
};

export default UserList;
