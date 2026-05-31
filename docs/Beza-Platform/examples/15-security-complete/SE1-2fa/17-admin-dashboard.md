# 17 - لوحة تحكم 2FA (Admin Dashboard)

## إدارة 2FA للمستخدمين

```jsx
// admin-dashboard/src/pages/TwoFactorManagement.jsx
import React, { useState, useEffect } from 'react';
import api from '../services/api';

export default function TwoFactorManagement() {
  const [users, setUsers] = useState([]);

  useEffect(() => {
    loadUsers();
  }, []);

  const loadUsers = async () => {
    const res = await api.get('/admin/users?with_2fa=true');
    setUsers(res.data.data);
  };

  const disableTwoFactor = async (userId) => {
    if (!confirm('هل أنت متأكد من إلغاء المصادقة الثنائية لهذا المستخدم؟')) return;
    await api.post(`/admin/users/${userId}/disable-2fa`);
    loadUsers();
  };

  return (
    <div>
      <h1>إدارة المصادقة الثنائية</h1>
      <table>
        <thead>
          <tr>
            <th>المستخدم</th>
            <th>2FA مفعلة</th>
            <th>تاريخ التفعيل</th>
            <th>إجراءات</th>
          </tr>
        </thead>
        <tbody>
          {users.map(user => (
            <tr key={user.id}>
              <td>{user.name} ({user.phone})</td>
              <td>{user.two_factor_enabled ? 'نعم' : 'لا'}</td>
              <td>{user.two_factor_confirmed_at || '-'}</td>
              <td>
                {user.two_factor_enabled && (
                  <button onClick={() => disableTwoFactor(user.id)}>
                    إلغاء 2FA
                  </button>
                )}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```

## إحصائيات 2FA

```php
public function twoFactorStats(): JsonResponse
{
    $total = User::count();
    $enabled = User::whereNotNull('two_factor_confirmed_at')->count();
    $disabled = $total - $enabled;

    return response()->json([
        'success' => true,
        'data' => [
            'total_users' => $total,
            'two_factor_enabled' => $enabled,
            'two_factor_disabled' => $disabled,
            'enabled_percentage' => round(($enabled / $total) * 100, 2),
        ],
    ]);
}
```
