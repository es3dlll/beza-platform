# 17 - تطبيق React (React Implementation)

**الرمز التشغيلي:** SY2-health  
**النوع:** كود React (React Component)

---

## نظرة عامة (Overview)

لوحة التحكم الصحية للمشرفين مبنية باستخدام React مع TypeScript. تعرض جميع الخدمات بحالة فورية مع تفاصيل إضافية (استخدام الذاكرة، المساحة التخزينية، زمن الاستجابة).

---

## Typescript Types

```typescript
// types/health.ts

/** نتيجة فحص خدمة واحدة */
export interface ServiceHealth {
  name: 'database' | 'redis' | 'cache' | 'queue' | 'storage' | 'php_requirements';
  status: 'up' | 'down' | 'degraded';
  latency_ms: number;
  details?: Record<string, unknown>;
  error?: string;
}

/** التقرير الصحي الكامل */
export interface HealthReport {
  status: 'ok' | 'degraded' | 'down';
  services: ServiceHealth[];
  timestamp: string;
  cached: boolean;
}

/** معلومات النظام (تظهر فقط للمشرفين) */
export interface SystemInfo {
  php_version: string;
  laravel_version: string;
  os: string;
  memory_usage: {
    current_usage_mb: number;
    peak_usage_mb: number;
    memory_limit: string;
  };
  uptime: string;
}

/** التقرير المفصل للمشرف */
export interface AdminHealthReport extends HealthReport {
  system?: SystemInfo;
}
```

---

## خدمة API (API Service)

```typescript
// services/healthApi.ts

import axios, { AxiosInstance } from 'axios';
import { HealthReport, AdminHealthReport } from '../types/health';

const API_BASE = process.env.REACT_APP_API_URL || 'https://api.beza.com';

class HealthApiService {
  private client: AxiosInstance;

  constructor(token?: string) {
    this.client = axios.create({
      baseURL: API_BASE,
      headers: {
        Accept: 'application/json',
        ...(token ? { Authorization: `Bearer ${token}` } : {}),
      },
    });
  }

  /** جلب التقرير الصحي العام */
  async getGeneralHealth(): Promise<HealthReport> {
    try {
      const response = await this.client.get<HealthReport>('/system/health');
      return response.data;
    } catch (error) {
      if (axios.isAxiosError(error)) {
        throw new Error(
          `فشل جلب التقرير الصحي: ${error.response?.status || 'اتصال'}`
        );
      }
      throw new Error('خطأ غير متوقع في الاتصال');
    }
  }

  /** جلب التقرير المفصل للمشرف */
  async getAdminHealth(token: string): Promise<AdminHealthReport> {
    try {
      const response = await this.client.get<AdminHealthReport>(
        '/admin/system/health',
        { headers: { Authorization: `Bearer ${token}` } }
      );
      return response.data;
    } catch (error) {
      if (axios.isAxiosError(error)) {
        const status = error.response?.status;
        if (status === 401) throw new Error('التوكن غير صالح');
        if (status === 403) throw new Error('الصلاحية غير كافية');
        throw new Error(`فشل جلب التقرير: ${status}`);
      }
      throw new Error('خطأ في الاتصال بالخادم');
    }
  }
}

export const healthApi = new HealthApiService();
export default HealthApiService;
```

---

## مكون عرض الخدمة (Service Status Component)

```tsx
// components/HealthServiceCard.tsx

import React from 'react';
import { ServiceHealth } from '../types/health';

interface Props {
  service: ServiceHealth;
}

/** بطاقة عرض حالة خدمة واحدة */
const HealthServiceCard: React.FC<Props> = ({ service }) => {
  const statusColors: Record<string, string> = {
    up: '#28a745',
    degraded: '#ffc107',
    down: '#dc3545',
  };

  const statusLabels: Record<string, string> = {
    up: 'يعمل',
    degraded: 'متدهور',
    down: 'معطل',
  };

  const arabicNames: Record<string, string> = {
    database: 'قاعدة البيانات',
    redis: 'Redis',
    cache: 'الذاكرة المؤقتة',
    queue: 'قائمة الانتظار',
    storage: 'التخزين',
    php_requirements: 'متطلبات PHP',
  };

  const color = statusColors[service.status] || '#6c757d';

  return (
    <div
      className="health-service-card"
      style={{
        borderLeft: `4px solid ${color}`,
        padding: '12px 16px',
        margin: '8px 0',
        backgroundColor: '#fff',
        borderRadius: '8px',
        boxShadow: '0 1px 3px rgba(0,0,0,0.1)',
        direction: 'rtl',
      }}
    >
      <div style={{ display: 'flex', alignItems: 'center', gap: '12px' }}>
        <div
          style={{
            width: '40px',
            height: '40px',
            borderRadius: '50%',
            backgroundColor: `${color}20`,
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
          }}
        >
          <span style={{ fontSize: '20px' }}>
            {service.status === 'up' ? '✓' : service.status === 'degraded' ? '⚠' : '✕'}
          </span>
        </div>
        <div style={{ flex: 1 }}>
          <h4 style={{ margin: 0, fontSize: '16px', fontWeight: 600 }}>
            {arabicNames[service.name] || service.name}
          </h4>
          <p style={{ margin: '4px 0', color, fontWeight: 500 }}>
            {statusLabels[service.status]}
          </p>
          {service.latency_ms > 0 && (
            <small style={{ color: '#666' }}>
              زمن الاستجابة: {service.latency_ms.toFixed(2)} مللي ثانية
            </small>
          )}
          {service.error && (
            <p style={{ color: '#dc3545', fontSize: '13px', margin: '4px 0' }}>
              {service.error}
            </p>
          )}
        </div>
        <div
          style={{
            width: '12px',
            height: '12px',
            borderRadius: '50%',
            backgroundColor: color,
          }}
        />
      </div>
    </div>
  );
};

export default HealthServiceCard;
```

---

## مكون لوحة التحكم الرئيسية (Main Dashboard Component)

```tsx
// components/HealthDashboard.tsx

import React, { useState, useEffect, useCallback } from 'react';
import HealthServiceCard from './HealthServiceCard';
import { healthApi } from '../services/healthApi';
import { AdminHealthReport } from '../types/health';

interface Props {
  adminToken?: string;
  refreshInterval?: number; // milliseconds
}

/** لوحة التحكم الصحية الرئيسية */
const HealthDashboard: React.FC<Props> = ({
  adminToken,
  refreshInterval = 30000,
}) => {
  const [report, setReport] = useState<AdminHealthReport | null>(null);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [lastRefresh, setLastRefresh] = useState<Date>(new Date());

  const fetchHealth = useCallback(async () => {
    try {
      setLoading(true);
      setError(null);

      const data = adminToken
        ? await healthApi.getAdminHealth(adminToken)
        : await healthApi.getGeneralHealth();

      setReport(data);
      setLastRefresh(new Date());
    } catch (err) {
      setError(err instanceof Error ? err.message : 'حدث خطأ غير متوقع');
    } finally {
      setLoading(false);
    }
  }, [adminToken]);

  useEffect(() => {
    fetchHealth();
    const interval = setInterval(fetchHealth, refreshInterval);
    return () => clearInterval(interval);
  }, [fetchHealth, refreshInterval]);

  const statusColors: Record<string, string> = {
    ok: '#28a745',
    degraded: '#ffc107',
    down: '#dc3545',
  };

  const statusLabels: Record<string, string> = {
    ok: 'النظام يعمل بشكل طبيعي',
    degraded: 'النظام بحاجة إلى انتباه',
    down: 'النظام معطل',
  };

  if (loading && !report) {
    return (
      <div className="health-dashboard" style={{ textAlign: 'center', padding: '40px' }}>
        <div className="spinner" />
        <p>جاري فحص النظام...</p>
      </div>
    );
  }

  if (error && !report) {
    return (
      <div
        className="health-dashboard error"
        style={{
          textAlign: 'center',
          padding: '40px',
          color: '#dc3545',
        }}
      >
        <h3>فشل الاتصال</h3>
        <p>{error}</p>
        <button onClick={fetchHealth} style={buttonStyle}>
          إعادة المحاولة
        </button>
      </div>
    );
  }

  const overallColor = statusColors[report?.status || 'down'];

  return (
    <div className="health-dashboard" style={{ direction: 'rtl', padding: '20px' }}>
      {/* ترجمة: بطاقة الحالة العامة */}
      <div
        style={{
          backgroundColor: `${overallColor}10`,
          border: `1px solid ${overallColor}40`,
          borderRadius: '12px',
          padding: '24px',
          textAlign: 'center',
          marginBottom: '20px',
        }}
      >
        <div
          style={{
            width: '80px',
            height: '80px',
            borderRadius: '50%',
            backgroundColor: overallColor,
            color: '#fff',
            display: 'flex',
            alignItems: 'center',
            justifyContent: 'center',
            margin: '0 auto 12px',
            fontSize: '36px',
          }}
        >
          {report?.status === 'ok' ? '✓' : report?.status === 'degraded' ? '⚠' : '✕'}
        </div>
        <h2 style={{ color: overallColor, margin: '8px 0' }}>
          {statusLabels[report?.status || 'down']}
        </h2>
        <p style={{ color: '#666' }}>
          آخر فحص: {lastRefresh.toLocaleTimeString('ar-SA')}
          {report?.cached && ' (مخزن مؤقتاً)'}
        </p>
        <button onClick={fetchHealth} style={buttonStyle}>
          تحديث الآن
        </button>
      </div>

      {/* ترجمة: قائمة الخدمات */}
      <h3 style={{ marginBottom: '12px' }}>حالة الخدمات</h3>
      {report?.services.map((service) => (
        <HealthServiceCard key={service.name} service={service} />
      ))}

      {/* ترجمة: معلومات النظام للمشرف */}
      {report?.system && (
        <div
          style={{
            marginTop: '24px',
            padding: '16px',
            backgroundColor: '#f8f9fa',
            borderRadius: '8px',
          }}
        >
          <h4>معلومات النظام</h4>
          <table style={{ width: '100%', borderCollapse: 'collapse' }}>
            <tbody>
              <tr>
                <td style={tdStyle}>PHP</td>
                <td style={tdStyle}>{report.system.php_version}</td>
              </tr>
              <tr>
                <td style={tdStyle}>Laravel</td>
                <td style={tdStyle}>{report.system.laravel_version}</td>
              </tr>
              <tr>
                <td style={tdStyle}>نظام التشغيل</td>
                <td style={tdStyle}>{report.system.os}</td>
              </tr>
              <tr>
                <td style={tdStyle}>الذاكرة المستخدمة</td>
                <td style={tdStyle}>
                  {report.system.memory_usage.current_usage_mb} MB
                  (الذروة: {report.system.memory_usage.peak_usage_mb} MB)
                </td>
              </tr>
              <tr>
                <td style={tdStyle}>مدة التشغيل</td>
                <td style={tdStyle}>{report.system.uptime}</td>
              </tr>
            </tbody>
          </table>
        </div>
      )}
    </div>
  );
};

const buttonStyle: React.CSSProperties = {
  padding: '8px 24px',
  backgroundColor: '#007bff',
  color: '#fff',
  border: 'none',
  borderRadius: '6px',
  cursor: 'pointer',
  fontSize: '14px',
  marginTop: '8px',
};

const tdStyle: React.CSSProperties = {
  padding: '8px 12px',
  borderBottom: '1px solid #dee2e6',
};

export default HealthDashboard;
```

---

## مثال استخدام (Usage Example)

```tsx
// pages/AdminHealthPage.tsx

import React from 'react';
import HealthDashboard from '../components/HealthDashboard';
import { useAuth } from '../hooks/useAuth';

/** صفحة التحقق الصحي للمشرفين */
const AdminHealthPage: React.FC = () => {
  const { token } = useAuth();

  return (
    <div className="admin-page" style={{ maxWidth: '800px', margin: '0 auto' }}>
      <h1 style={{ textAlign: 'center', marginBottom: '24px' }}>
        لوحة التحكم الصحية
      </h1>
      <HealthDashboard
        adminToken={token}
        refreshInterval={30000}
      />
    </div>
  );
};

export default AdminHealthPage;
```

---

## ملفات CSS (Styling)

```css
/* styles/health-dashboard.css */

.health-dashboard {
  font-family: 'Segoe UI', Tahoma, sans-serif;
}

.health-service-card {
  transition: all 0.2s ease;
}

.health-service-card:hover {
  box-shadow: 0 2px 8px rgba(0, 0, 0, 0.15);
}

.spinner {
  width: 40px;
  height: 40px;
  border: 4px solid #e9ecef;
  border-top-color: #007bff;
  border-radius: 50%;
  animation: spin 0.8s linear infinite;
  margin: 0 auto 12px;
}

@keyframes spin {
  to {
    transform: rotate(360deg);
  }
}

@media (max-width: 600px) {
  .health-dashboard {
    padding: 12px;
  }
}
```

---

## ملخص المكونات (Components Summary)

| المكون (Component) | الوظيفة (Function) |
|-------------------|-------------------|
| `HealthServiceCard` | عرض حالة خدمة واحدة مع لون وأيقونة |
| `HealthDashboard` | لوحة التحكم الرئيسية مع تحديث تلقائي |
| `AdminHealthPage` | صفحة كاملة للمشرفين |
| `HealthApiService` | خدمة API لجلب البيانات |
