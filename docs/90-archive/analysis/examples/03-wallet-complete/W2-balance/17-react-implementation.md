# 17 - تطبيق React (React Implementation) - عرض الرصيد (W2 Balance)

## هيكل الملفات

```
user-frontend/src/
├── pages/
│   └── DashboardPage.jsx
├── components/
│   └── Wallet/
│       ├── BalanceCard.jsx
│       └── WalletSummary.jsx
├── hooks/
│   └── useBalance.js
└── services/
    └── api.js
```

## API Service

```javascript
// services/tokenService.js
import axios from 'axios';

const TOKEN_KEY = 'access_token';
const EXPIRES_AT_KEY = 'expires_at';
const REFRESH_TOKEN_KEY = 'refresh_token';

export const tokenService = {
  saveTokens({ accessToken, expiresIn = 3600, refreshToken }) {
    const expiresAt = Date.now() + expiresIn * 1000;
    localStorage.setItem(TOKEN_KEY, accessToken);
    localStorage.setItem(EXPIRES_AT_KEY, expiresAt.toString());
    if (refreshToken) {
      localStorage.setItem(REFRESH_TOKEN_KEY, refreshToken);
    }
  },

  getValidToken() {
    const token = localStorage.getItem(TOKEN_KEY);
    if (!token) return null;

    const expiresAt = parseInt(localStorage.getItem(EXPIRES_AT_KEY) || '0');
    if (Date.now() >= expiresAt) {
      return this._refreshToken();
    }
    return token;
  },

  async _refreshToken() {
    const refreshToken = localStorage.getItem(REFRESH_TOKEN_KEY);
    if (!refreshToken) {
      this.clearToken();
      return null;
    }

    try {
      const response = await axios.post('/api/v1/auth/refresh', {}, {
        headers: { Authorization: `Bearer ${refreshToken}` },
      });
      const { token, expires_in } = response.data.data;
      this.saveTokens({ accessToken: token, expiresIn: expires_in });
      return token;
    } catch {
      this.clearToken();
      window.location.href = '/login';
      return null;
    }
  },

  clearToken() {
    localStorage.removeItem(TOKEN_KEY);
    localStorage.removeItem(EXPIRES_AT_KEY);
    localStorage.removeItem(REFRESH_TOKEN_KEY);
    localStorage.removeItem('user');
  },
};
```

```javascript
// services/api.js
import axios from 'axios';
import { tokenService } from './tokenService';

const api = axios.create({
  baseURL: 'http://localhost:8000/api/v1',
  headers: { Accept: 'application/json' },
});

api.interceptors.request.use(async (config) => {
  const token = tokenService.getValidToken();
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  async (error) => {
    if (error.response?.status === 401) {
      const refreshed = await tokenService._refreshToken?.();
      if (refreshed) {
        error.config.headers.Authorization = `Bearer ${refreshed}`;
        return axios(error.config);
      }
    }
    return Promise.reject(error);
  }
);

export const walletApi = {
  getBalance: () => api.get('/wallet/balance'),
};

export default api;
```

## هوك مخصص (Custom Hook)

```javascript
// hooks/useBalance.js
import { useState, useEffect, useCallback } from 'react';
import { walletApi } from '../services/api';

export function useBalance() {
  const [state, setState] = useState({
    loading: true,
    error: null,
    syp: null,
    usd: null,
  });

  const fetchBalance = useCallback(async () => {
    try {
      const response = await walletApi.getBalance();
      const { syp, usd } = response.data.data;

      setState({
        loading: false,
        error: null,
        syp,
        usd,
      });
    } catch (err) {
      const message =
        err.response?.data?.message ||
        err.response?.data?.errors?.[Object.keys(err.response?.data?.errors || {})[0]]?.[0] ||
        'حدث خطأ في جلب الرصيد';

      setState(prev => ({
        ...prev,
        loading: false,
        error: message,
      }));
    }
  }, []);

  useEffect(() => {
    fetchBalance();
  }, [fetchBalance]);

  return { ...state, refresh: fetchBalance };
}
```

## DashboardPage

```jsx
// pages/DashboardPage.jsx
import React, { useEffect } from 'react';
import BalanceCard from '../components/Wallet/BalanceCard';
import { useBalance } from '../hooks/useBalance';

export default function DashboardPage() {
  const { loading, error, syp, usd, refresh } = useBalance();

  useEffect(() => {
    // Auto-refresh every 30 seconds
    const interval = setInterval(refresh, 30000);
    return () => clearInterval(interval);
  }, [refresh]);

  if (loading) {
    return <div className="loading">جاري تحميل الرصيد...</div>;
  }

  if (error) {
    return (
      <div className="error-container">
        <p className="error-message">{error}</p>
        <button onClick={refresh} className="btn-primary">إعادة المحاولة</button>
      </div>
    );
  }

  return (
    <div className="dashboard">
      <div className="dashboard-header">
        <h1>محفظتي</h1>
        <button onClick={refresh} className="btn-refresh" title="تحديث">
          ↻
        </button>
      </div>

      <div className="wallets-grid">
        <BalanceCard
          currency="SYP"
          balance={syp.balance}
          frozen={syp.frozen}
          available={syp.available}
          walletNumber={syp.wallet_number}
        />
        <BalanceCard
          currency="USD"
          balance={usd.balance}
          frozen={usd.frozen}
          available={usd.available}
          walletNumber={usd.wallet_number}
        />
      </div>

      <div className="total-balance">
        <h3>إجمالي الرصيد المتاح</h3>
        <p>{syp.available.toLocaleString()} SYP + {usd.available.toFixed(2)} USD</p>
      </div>
    </div>
  );
}
```

## BalanceCard Component

```jsx
// components/Wallet/BalanceCard.jsx
import React from 'react';

export default function BalanceCard({ currency, balance, frozen, available, walletNumber }) {
  return (
    <div className={`balance-card ${currency.toLowerCase()}`}>
      <div className="card-header">
        <span className="currency-badge">{currency}</span>
        <span className="wallet-number" dir="ltr">{walletNumber}</span>
      </div>

      <div className="card-balance">
        <span className="amount">{balance.toLocaleString(undefined, { minimumFractionDigits: 2 })}</span>
        <span className="currency-label">{currency}</span>
      </div>

      <div className="card-details">
        <div className="detail">
          <span>رصيد مجمد</span>
          <span>{frozen.toFixed(2)}</span>
        </div>
        <div className="detail available">
          <span>المتاح</span>
          <span>{available.toFixed(2)} {currency}</span>
        </div>
      </div>
    </div>
  );
}
```

## CSS

```css
.dashboard { max-width: 600px; margin: 0 auto; padding: 24px; }
.dashboard-header { display: flex; justify-content: space-between; align-items: center; margin-bottom: 24px; }
.btn-refresh { background: none; border: 1px solid #d1d5db; border-radius: 50%; width: 40px; height: 40px; font-size: 20px; cursor: pointer; }
.wallets-grid { display: flex; flex-direction: column; gap: 16px; }
.balance-card { background: white; border-radius: 12px; padding: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.1); }
.balance-card.syp { border-right: 4px solid #2563eb; }
.balance-card.usd { border-right: 4px solid #059669; }
.card-header { display: flex; justify-content: space-between; margin-bottom: 16px; }
.currency-badge { background: #f3f4f6; padding: 4px 12px; border-radius: 20px; font-size: 14px; font-weight: 600; }
.wallet-number { color: #9ca3af; font-size: 12px; font-family: monospace; }
.card-balance { margin-bottom: 16px; }
.amount { font-size: 32px; font-weight: 700; }
.currency-label { font-size: 18px; color: #6b7280; margin-right: 8px; }
.card-details { border-top: 1px solid #e5e7eb; padding-top: 12px; }
.detail { display: flex; justify-content: space-between; padding: 4px 0; color: #6b7280; font-size: 14px; }
.detail.available { color: #059669; font-weight: 600; }
.total-balance { margin-top: 24px; text-align: center; padding: 20px; background: #f9fafb; border-radius: 12px; }
.total-balance h3 { color: #6b7280; font-size: 14px; }
.total-balance p { font-size: 20px; font-weight: 700; color: #111827; }
.loading { text-align: center; padding: 48px; color: #6b7280; }
.error-container { text-align: center; padding: 48px; }
.error-message { color: #dc2626; margin-bottom: 16px; }
```
