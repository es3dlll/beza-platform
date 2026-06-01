# 17 - تطبيق React (React Implementation) — تسجيل الخروج (Logout)

## هوك مخصص (Custom Hook)

```javascript
// hooks/useLogout.js
import { useState, useCallback } from 'react';
import api from '../services/api';
import { tokenService } from '../services/tokenService';

export function useLogout() {
  const [loading, setLoading] = useState(false);

  const logout = useCallback(async () => {
    setLoading(true);
    try {
      await api.post('/auth/logout');
    } finally {
      tokenService.clearToken();
      setLoading(false);
      window.location.href = '/login';
    }
  }, []);

  const logoutAll = useCallback(async () => {
    setLoading(true);
    try {
      const response = await api.post('/auth/logout-all');
      return response.data.data.devices_count;
    } finally {
      tokenService.clearToken();
      setLoading(false);
      window.location.href = '/login';
    }
  }, []);

  return { loading, logout, logoutAll };
}
```

## Logout Button Component

```jsx
// components/LogoutButton.jsx
import React from 'react';
import { useLogout } from '../hooks/useLogout';

export default function LogoutButton({ variant = 'single' }) {
  const { loading, logout, logoutAll } = useLogout();

  const handleClick = () => {
    if (window.confirm('هل أنت متأكد من تسجيل الخروج؟')) {
      variant === 'all' ? logoutAll() : logout();
    }
  };

  return (
    <button onClick={handleClick} disabled={loading} className="logout-btn">
      {loading ? 'جاري...' : variant === 'all' ? 'خروج من كل الأجهزة' : 'تسجيل خروج'}
    </button>
  );
}
```

## Usage in Header

```jsx
// components/Header.jsx
import LogoutButton from './LogoutButton';

export default function Header() {
  return (
    <header>
      <h1>Beza</h1>
      <nav>
        <LogoutButton />
      </nav>
    </header>
  );
}
```
