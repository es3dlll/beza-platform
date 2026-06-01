# 17 - تطبيق React (React Implementation) - فهرس - الذهب والفضة مدخرات (Gold & Silver Savings)

## هيكل المكونات

```
src/features/gold/
├── api/
│   └── goldApi.js
├── hooks/
│   └── useGold.js
├── components/
│   ├── GoldPage.jsx
│   ├── PriceCard.jsx
│   ├── BuyForm.jsx
│   ├── SellForm.jsx
│   ├── PortfolioTable.jsx
│   └── TransactionHistory.jsx
```

## API Layer

```jsx
// api/goldApi.js
import apiClient from '../../services/apiClient';

export const goldApi = {
  getPrices: async () => {
    const { data } = await apiClient.get('/commodity/prices');
    return data;
  },

  buy: async ({ commodity, amountSpent, currency }) => {
    const { data } = await apiClient.post('/commodity/buy', {
      commodity, amount_spent: amountSpent, currency,
    });
    return data;
  },

  sell: async ({ commodity, grams, currency }) => {
    const { data } = await apiClient.post('/commodity/sell', {
      commodity, grams, currency,
    });
    return data;
  },

  getHoldings: async () => {
    const { data } = await apiClient.get('/commodity/holdings');
    return data;
  },

  getHistory: async (page = 1) => {
    const { data } = await apiClient.get('/commodity/history', {
      params: { page, per_page: 20 },
    });
    return data;
  },
};
```

## Custom Hook

```jsx
// hooks/useGold.js
import { useState, useEffect, useCallback } from 'react';
import { goldApi } from '../api/goldApi';

export function useGold() {
  const [prices, setPrices] = useState({ gold: null, silver: null });
  const [marketOpen, setMarketOpen] = useState(true);
  const [holdings, setHoldings] = useState([]);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [operationResult, setOperationResult] = useState(null);

  const fetchPrices = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const response = await goldApi.getPrices();
      setPrices(response.data);
      setMarketOpen(response.market_open);
    } catch (err) {
      setError(err.response?.data?.message || 'فشل جلب الأسعار');
    } finally {
      setLoading(false);
    }
  }, []);

  const fetchHoldings = useCallback(async () => {
    setLoading(true);
    try {
      const response = await goldApi.getHoldings();
      setHoldings(response.data);
    } catch (err) {
      setError(err.response?.data?.message || 'فشل جلب المحفظة');
    } finally {
      setLoading(false);
    }
  }, []);

  const executeBuy = useCallback(async ({ commodity, amountSpent, currency }) => {
    setLoading(true);
    setError(null);
    setOperationResult(null);
    try {
      const response = await goldApi.buy({ commodity, amountSpent, currency });
      setOperationResult({ type: 'buy', data: response.data, message: response.message });
      await fetchHoldings();
      return response.data;
    } catch (err) {
      const msg = err.response?.data?.message || 'فشلت عملية الشراء';
      setError(msg);
      throw new Error(msg);
    } finally {
      setLoading(false);
    }
  }, [fetchHoldings]);

  const executeSell = useCallback(async ({ commodity, grams, currency }) => {
    setLoading(true);
    setError(null);
    setOperationResult(null);
    try {
      const response = await goldApi.sell({ commodity, grams, currency });
      setOperationResult({ type: 'sell', data: response.data, message: response.message });
      await fetchHoldings();
      return response.data;
    } catch (err) {
      const msg = err.response?.data?.message || 'فشلت عملية البيع';
      setError(msg);
      throw new Error(msg);
    } finally {
      setLoading(false);
    }
  }, [fetchHoldings]);

  useEffect(() => {
    fetchPrices();
    fetchHoldings();
  }, [fetchPrices, fetchHoldings]);

  const clearOperation = useCallback(() => {
    setOperationResult(null);
    setError(null);
  }, []);

  return {
    prices, marketOpen, holdings, loading, error,
    operationResult, fetchPrices, fetchHoldings,
    executeBuy, executeSell, clearOperation,
  };
}
```

## صفحة الذهب الرئيسية

```jsx
// components/GoldPage.jsx
import React from 'react';
import { useGold } from '../hooks/useGold';
import PriceCard from './PriceCard';
import BuyForm from './BuyForm';
import SellForm from './SellForm';
import PortfolioTable from './PortfolioTable';

export default function GoldPage() {
  const {
    prices, marketOpen, holdings, loading, error,
    operationResult, executeBuy, executeSell,
    fetchPrices, clearOperation,
  } = useGold();

  return (
    <div className="container mx-auto p-4" dir="rtl">
      <h1 className="text-2xl font-bold mb-6">الذهب والفضة</h1>

      {error && (
        <div className="bg-red-100 border border-red-400 text-red-700 px-4 py-3 rounded mb-4">
          {error}
          <button onClick={clearOperation} className="mr-2">✕</button>
        </div>
      )}

      {operationResult && (
        <div className="bg-green-100 border border-green-400 text-green-700 px-4 py-3 rounded mb-4">
          <p className="font-bold">{operationResult.message}</p>
          {operationResult.type === 'buy' && (
            <p>الجرامات: {operationResult.data.grams} | المرجع: {operationResult.data.reference}</p>
          )}
          {operationResult.type === 'sell' && (
            <p>المستلم: ${operationResult.data.net_received} | المرجع: {operationResult.data.reference}</p>
          )}
        </div>
      )}

      {!marketOpen && (
        <div className="bg-yellow-100 border border-yellow-400 text-yellow-800 px-4 py-3 rounded mb-4">
          ⚠ السوق مغلق حالياً (عطلة نهاية الأسبوع). يمكنك عرض محفظتك فقط.
        </div>
      )}

      <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
        <PriceCard
          title="الذهب (XAU/USD)"
          price={prices.gold}
          onRefresh={fetchPrices}
          loading={loading}
        />
        <PriceCard
          title="الفضة (XAG/USD)"
          price={prices.silver}
          onRefresh={fetchPrices}
          loading={loading}
        />
      </div>

      {marketOpen && (
        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 mb-6">
          <div className="bg-white shadow rounded-lg p-4">
            <h2 className="text-xl font-semibold mb-4">شراء</h2>
            <BuyForm onSubmit={executeBuy} loading={loading} />
          </div>
          <div className="bg-white shadow rounded-lg p-4">
            <h2 className="text-xl font-semibold mb-4">بيع</h2>
            <SellForm onSubmit={executeSell} loading={loading} holdings={holdings} />
          </div>
        </div>
      )}

      <PortfolioTable holdings={holdings} />
    </div>
  );
}
```

## PriceCard Component

```jsx
// components/PriceCard.jsx
import React from 'react';

export default function PriceCard({ title, price, onRefresh, loading }) {
  if (!price) {
    return (
      <div className="bg-white shadow rounded-lg p-4 text-center">
        <p className="text-gray-500">جاري تحميل السعر...</p>
      </div>
    );
  }

  const changeColor = price.change24h >= 0 ? 'text-green-600' : 'text-red-600';
  const changeIcon = price.change24h >= 0 ? '▲' : '▼';

  return (
    <div className="bg-white shadow rounded-lg p-4">
      <div className="flex justify-between items-center mb-2">
        <h3 className="text-lg font-semibold">{title}</h3>
        <button
          onClick={onRefresh}
          disabled={loading}
          className="text-blue-500 hover:text-blue-700"
        >
          {loading ? '...' : 'تحديث'}
        </button>
      </div>
      <div className="text-3xl font-bold mb-2" dir="ltr">
        ${price.ask.toFixed(2)}
      </div>
      <div className="flex justify-between text-sm text-gray-600">
        <span>شراء: <strong>${price.bid.toFixed(2)}</strong></span>
        <span>بيع: <strong>${price.ask.toFixed(2)}</strong></span>
      </div>
      <div className={`mt-2 ${changeColor}`}>
        {changeIcon} {Math.abs(price.change24h).toFixed(2)}% (24h)
      </div>
      <div className="text-xs text-gray-400 mt-1">
        آخر تحديث: {new Date(price.timestamp).toLocaleString('ar-SA')}
      </div>
    </div>
  );
}
```

## BuyForm Component

```jsx
// components/BuyForm.jsx
import React, { useState } from 'react';

export default function BuyForm({ onSubmit, loading }) {
  const [commodity, setCommodity] = useState('gold');
  const [amountSpent, setAmountSpent] = useState('');
  const [currency, setCurrency] = useState('USD');

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!amountSpent || parseFloat(amountSpent) < 1) return;
    try {
      await onSubmit({ commodity, amountSpent: parseFloat(amountSpent), currency });
      setAmountSpent('');
    } catch {
      // الخطأ يُعرض في GoldPage
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <div className="mb-3">
        <label className="block text-sm font-medium mb-1">السلعة</label>
        <select
          value={commodity}
          onChange={(e) => setCommodity(e.target.value)}
          className="w-full border rounded p-2"
        >
          <option value="gold">ذهب</option>
          <option value="silver">فضة</option>
        </select>
      </div>
      <div className="mb-3">
        <label className="block text-sm font-medium mb-1">المبلغ المراد إنفاقه</label>
        <div className="flex">
          <input
            type="number"
            value={amountSpent}
            onChange={(e) => setAmountSpent(e.target.value)}
            min="1"
            step="0.01"
            required
            className="flex-1 border rounded p-2 ml-2"
            placeholder="مثال: 500"
          />
          <select
            value={currency}
            onChange={(e) => setCurrency(e.target.value)}
            className="border rounded p-2 w-20"
          >
            <option value="USD">USD</option>
            <option value="SYP">SYP</option>
          </select>
        </div>
      </div>
      <button
        type="submit"
        disabled={loading}
        className="w-full bg-amber-600 text-white py-2 rounded hover:bg-amber-700 disabled:bg-gray-400"
      >
        {loading ? 'جاري التنفيذ...' : 'شراء'}
      </button>
    </form>
  );
}
```

## SellForm Component

```jsx
// components/SellForm.jsx
import React, { useState } from 'react';

export default function SellForm({ onSubmit, loading, holdings }) {
  const [commodity, setCommodity] = useState('gold');
  const [grams, setGrams] = useState('');
  const [currency, setCurrency] = useState('USD');

  const selectedHolding = holdings.find(h => h.commodity === commodity);
  const maxGrams = selectedHolding?.grams || 0;

  const handleSubmit = async (e) => {
    e.preventDefault();
    if (!grams || parseFloat(grams) < 0.1 || parseFloat(grams) > maxGrams) return;
    try {
      await onSubmit({ commodity, grams: parseFloat(grams), currency });
      setGrams('');
    } catch {
      // الخطأ يُعرض في GoldPage
    }
  };

  return (
    <form onSubmit={handleSubmit}>
      <div className="mb-3">
        <label className="block text-sm font-medium mb-1">السلعة</label>
        <select
          value={commodity}
          onChange={(e) => setCommodity(e.target.value)}
          className="w-full border rounded p-2"
        >
          <option value="gold">ذهب</option>
          <option value="silver">فضة</option>
        </select>
      </div>
      {selectedHolding && (
        <div className="text-sm text-gray-600 mb-2">
          المتاح: {maxGrams.toFixed(4)} جرام
        </div>
      )}
      <div className="mb-3">
        <label className="block text-sm font-medium mb-1">عدد الجرامات</label>
        <input
          type="number"
          value={grams}
          onChange={(e) => setGrams(e.target.value)}
          min="0.1"
          max={maxGrams || 0.1}
          step="0.0001"
          required
          className="w-full border rounded p-2"
          placeholder={`مثال: 1.5 (الحد الأقصى: ${maxGrams.toFixed(4)})`}
        />
      </div>
      <div className="mb-3">
        <label className="block text-sm font-medium mb-1">عملة الاستلام</label>
        <select
          value={currency}
          onChange={(e) => setCurrency(e.target.value)}
          className="w-full border rounded p-2"
        >
          <option value="USD">USD</option>
          <option value="SYP">SYP</option>
        </select>
      </div>
      <button
        type="submit"
        disabled={loading || maxGrams <= 0}
        className="w-full bg-emerald-600 text-white py-2 rounded hover:bg-emerald-700 disabled:bg-gray-400"
      >
        {loading ? 'جاري التنفيذ...' : 'بيع'}
      </button>
    </form>
  );
}
```

## PortfolioTable Component

```jsx
// components/PortfolioTable.jsx
import React from 'react';

export default function PortfolioTable({ holdings }) {
  if (!holdings.length) {
    return (
      <div className="bg-white shadow rounded-lg p-6 text-center text-gray-500">
        لا توجد حيازات. اشترِ ذهباً أو فضة للبدء.
      </div>
    );
  }

  return (
    <div className="bg-white shadow rounded-lg overflow-hidden">
      <h2 className="text-xl font-semibold p-4 border-b">محفظتي</h2>
      <table className="w-full text-right">
        <thead className="bg-gray-50">
          <tr>
            <th className="p-3">السلعة</th>
            <th className="p-3">الجرامات</th>
            <th className="p-3">متوسط السعر</th>
            <th className="p-3">المستثمر</th>
            <th className="p-3">القيمة الحالية</th>
            <th className="p-3">الربح/الخسارة</th>
          </tr>
        </thead>
        <tbody>
          {holdings.map((h) => (
            <tr key={h.id} className="border-t hover:bg-gray-50">
              <td className="p-3 font-medium">
                {h.commodity === 'gold' ? '🥇 ذهب' : '🥈 فضة'}
              </td>
              <td className="p-3" dir="ltr">{h.grams.toFixed(4)}</td>
              <td className="p-3" dir="ltr">${h.avg_price_usd.toFixed(2)}</td>
              <td className="p-3" dir="ltr">${h.total_invested_usd.toFixed(2)}</td>
              <td className="p-3 font-semibold" dir="ltr">${h.current_value_usd.toFixed(2)}</td>
              <td className={`p-3 ${h.profit_loss >= 0 ? 'text-green-600' : 'text-red-600'}`} dir="ltr">
                {h.profit_loss >= 0 ? '+' : ''}{h.profit_loss.toFixed(2)} ({h.profit_loss_percent.toFixed(2)}%)
              </td>
            </tr>
          ))}
        </tbody>
      </table>
    </div>
  );
}
```
