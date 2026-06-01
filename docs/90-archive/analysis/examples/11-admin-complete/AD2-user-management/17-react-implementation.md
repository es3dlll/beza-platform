# 17 - تطبيق React (React Implementation) - إدارة المستخدمين (Admin User Management)

## UserList Page

```jsx
// src/pages/admin/UserList.jsx
import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { adminApi } from '../../services/api';
import StatusBadge from '../../components/admin/StatusBadge';
import Pagination from '../../components/common/Pagination';

export default function UserList() {
  const [page, setPage] = useState(1);
  const [search, setSearch] = useState('');
  const [statusFilter, setStatusFilter] = useState('all');
  const [selectedUser, setSelectedUser] = useState(null);

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['admin-users', page, search, statusFilter],
    queryFn: () => adminApi.getUsers({ page, search, status: statusFilter }),
    keepPreviousData: true,
  });

  const suspendMutation = useMutation({
    mutationFn: (id) => adminApi.suspendUser(id),
    onSuccess: () => refetch(),
  });

  const activateMutation = useMutation({
    mutationFn: (id) => adminApi.activateUser(id),
    onSuccess: () => refetch(),
  });

  if (isLoading) return <div>جاري التحميل...</div>;

  return (
    <div className="user-management">
      <h1>إدارة المستخدمين</h1>

      <div className="filters">
        <input
          type="text"
          placeholder="بحث بالاسم أو الهاتف"
          value={search}
          onChange={(e) => { setSearch(e.target.value); setPage(1); }}
          className="search-input"
        />
        <select
          value={statusFilter}
          onChange={(e) => { setStatusFilter(e.target.value); setPage(1); }}
        >
          <option value="all">جميع الحالات</option>
          <option value="active">نشط</option>
          <option value="suspended">معلق</option>
          <option value="blocked">محظور</option>
          <option value="pending">قيد الانتظار</option>
        </select>
      </div>

      <table className="users-table">
        <thead>
          <tr>
            <th>الاسم</th>
            <th>الهاتف</th>
            <th>البريد</th>
            <th>الحالة</th>
            <th>KYC</th>
            <th>الدور</th>
            <th>تاريخ التسجيل</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          {data?.data?.map(user => (
            <tr key={user.id}>
              <td>{user.name}</td>
              <td dir="ltr">{user.phone}</td>
              <td>{user.email}</td>
              <td><StatusBadge status={user.status} /></td>
              <td><StatusBadge status={user.kyc_status} type="kyc" /></td>
              <td>
                {user.is_admin && 'مشرف'}
                {user.is_merchant && 'تاجر'}
                {user.is_agent && 'وكيل'}
                {!user.is_admin && !user.is_merchant && !user.is_agent && 'مستخدم'}
              </td>
              <td>{new Date(user.created_at).toLocaleDateString('ar')}</td>
              <td className="actions">
                <button onClick={() => setSelectedUser(user)}>عرض</button>
                {user.status === 'active' && (
                  <button onClick={() => suspendMutation.mutate(user.id)} className="btn-warning">
                    تعليق
                  </button>
                )}
                {user.status === 'suspended' && (
                  <button onClick={() => activateMutation.mutate(user.id)} className="btn-success">
                    تفعيل
                  </button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>

      {data?.meta && (
        <Pagination
          current={data.meta.current_page}
          total={data.meta.last_page}
          onChange={setPage}
        />
      )}

      {selectedUser && (
        <UserDetailModal
          user={selectedUser}
          onClose={() => setSelectedUser(null)}
        />
      )}
    </div>
  );
}
```

## UserDetail Modal

```jsx
// src/components/admin/UserDetailModal.jsx
export default function UserDetailModal({ user, onClose }) {
  const { data: detail } = useQuery({
    queryKey: ['admin-user', user.id],
    queryFn: () => adminApi.getUser(user.id),
    enabled: !!user.id,
  });

  if (!detail) return null;
  const userDetail = detail.data;

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal" onClick={e => e.stopPropagation()}>
        <h2>{userDetail.name}</h2>
        <div className="detail-grid">
          <div>
            <label>رقم الهاتف</label>
            <span>{userDetail.phone}</span>
          </div>
          <div>
            <label>البريد</label>
            <span>{userDetail.email}</span>
          </div>
          <div>
            <label>الحالة</label>
            <StatusBadge status={userDetail.status} />
          </div>
          <div>
            <label>KYC</label>
            <StatusBadge status={userDetail.kyc_status} type="kyc" />
          </div>
        </div>

        <h3>المحافظ</h3>
        {userDetail.wallets?.map(wallet => (
          <div key={wallet.id} className="wallet-row">
            <span>{wallet.currency}</span>
            <span>{wallet.balance}</span>
            <span>{wallet.is_active ? 'نشطة' : 'موقوفة'}</span>
          </div>
        ))}

        <div className="modal-actions">
          <button onClick={onClose}>إغلاق</button>
        </div>
      </div>
    </div>
  );
}
```
