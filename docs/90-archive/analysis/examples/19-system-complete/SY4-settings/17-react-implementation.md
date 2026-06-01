# 17 - تنفيذ React: صفحة إعدادات المسؤول مع تبويبات المجموعات (React Implementation)

## نظرة عامة (Overview)

صفحة إعدادات النظام في لوحة تحكم React (Admin Panel). تستخدم تبويبات (Tabs) لتنظيم المجموعات، مع نموذج منفصل لكل مجموعة. تدعم التحديث المباشر مع إبطال الكاش.

```php
// // React هو المستخدم الأساسي لواجهة API
// // المسؤول يستخدم هذه الصفحة لإدارة كل الإعدادات
// // الصفحة مقسمة إلى تبويبات حسب المجموعة
```

## خدمة API (API Service)

```typescript
// // ملف: src/services/systemSettings.service.ts
// // خدمة API لإعدادات النظام

import api from './api'; // // axios instance مع JWT interceptor
import { AxiosResponse } from 'axios';

export interface SystemSettings {
  general: GeneralSettings;
  features: FeatureFlags;
  fees: FeeSettings;
  limits: LimitSettings;
  exchange: ExchangeSettings;
  security: SecuritySettings;
  notifications: NotificationSettings;
  mail: MailSettings;
  maintenance: MaintenanceSettings;
}

export interface GeneralSettings {
  app_name: string;
  app_description: string;
  app_logo?: string;
  app_favicon?: string;
  timezone: string;
  locale: 'ar' | 'en';
}

export interface FeatureFlags {
  gold: boolean;
  deals: boolean;
  cards: boolean;
  agents: boolean;
  loans: boolean;
}

export interface FeeSettings {
  p2p: number;
  exchange: number;
  card_deposit: number;
  withdrawal: number;
}

export interface LimitSettings {
  daily_transfer: number;
  max_wallet: number;
  min_withdrawal: number;
}

export interface ExchangeSettings {
  margin: number;
  update_interval: number;
}

export interface SecuritySettings {
  max_attempts: number;
  lockout_minutes: number;
  password_policy: object;
}

export interface NotificationSettings {
  default_channels: string[];
}

export interface MailSettings {
  smtp: {
    host: string;
    port: number;
    encryption: 'tls' | 'ssl' | null;
    username: string;
    password: string;
    from_address: string;
    from_name: string;
  };
}

export interface MaintenanceSettings {
  mode: boolean;
  message: string;
  allowed_ips: string[];
}

// // فئة الخدمة
class SystemSettingsService {
  private readonly basePath = '/admin/system/settings';

  /** جلب جميع الإعدادات */
  async getAll(): Promise<SystemSettings> {
    const response: AxiosResponse = await api.get(this.basePath);
    return response.data.data;
  }

  /** جلب إعدادات مجموعة محددة */
  async getByGroup(group: string): Promise<Record<string, unknown>> {
    const response: AxiosResponse = await api.get(`${this.basePath}/${group}`);
    return response.data.data;
  }

  /** تحديث مجموعة إعدادات */
  async updateGroup(
    group: string,
    data: Record<string, unknown>
  ): Promise<Record<string, unknown>> {
    const response: AxiosResponse = await api.put(
      `${this.basePath}/${group}`,
      data
    );
    return response.data.data;
  }

  /** اختبار اتصال SMTP */
  async testSmtp(smtpConfig: MailSettings['smtp']): Promise<boolean> {
    try {
      await api.post(`${this.basePath}/mail/test`, { smtp: smtpConfig });
      return true;
    } catch {
      return false;
    }
  }
}

export const systemSettingsService = new SystemSettingsService();
```

## مزود الحالة (State Provider - Context API)

```typescript
// // ملف: src/contexts/SystemSettingsContext.tsx
// // مزود حالة إعدادات النظام مع التخزين المؤقت المحلي

import React, {
  createContext,
  useContext,
  useState,
  useEffect,
  useCallback,
  ReactNode,
} from 'react';
import {
  SystemSettings,
  systemSettingsService,
} from '../services/systemSettings.service';

interface SystemSettingsContextType {
  settings: SystemSettings | null;
  isLoading: boolean;
  error: string | null;
  isStale: boolean;
  refresh: () => Promise<void>;
  updateGroup: (group: string, data: Record<string, unknown>) => Promise<void>;
  testSmtp: (config: Record<string, unknown>) => Promise<boolean>;
}

const SystemSettingsContext = createContext<SystemSettingsContextType | null>(
  null
);

// // مفتاح التخزين المحلي
const STORAGE_KEY = 'beza_system_settings';

export function SystemSettingsProvider({ children }: { children: ReactNode }) {
  const [settings, setSettings] = useState<SystemSettings | null>(null);
  const [isLoading, setIsLoading] = useState(true);
  const [error, setError] = useState<string | null>(null);
  const [isStale, setIsStale] = useState(false);

  /** تحميل الإعدادات من API */
  const loadSettings = useCallback(async () => {
    setIsLoading(true);
    setError(null);

    try {
      // // محاولة قراءة من التخزين المحلي أولاً
      const cached = localStorage.getItem(STORAGE_KEY);
      if (cached) {
        setSettings(JSON.parse(cached));
        setIsStale(true); // // الكاش قديم، نحتاج تحديث
      }

      // // جلب من API
      const fresh = await systemSettingsService.getAll();
      setSettings(fresh);
      localStorage.setItem(STORAGE_KEY, JSON.stringify(fresh));
      setIsStale(false);
    } catch (err) {
      if (!settings) {
        setError('فشل تحميل إعدادات النظام. تحقق من اتصالك.');
      }
    } finally {
      setIsLoading(false);
    }
  }, [settings]);

  /** تحديث مجموعة إعدادات */
  const updateGroup = useCallback(
    async (group: string, data: Record<string, unknown>) => {
      try {
        const updated = await systemSettingsService.updateGroup(group, data);

        // // تحديث الحالة المحلية
        setSettings((prev) => {
          if (!prev) return prev;
          return { ...prev, [group]: updated };
        });

        // // تحديث التخزين المحلي
        const current = localStorage.getItem(STORAGE_KEY);
        if (current) {
          const parsed = JSON.parse(current);
          parsed[group] = updated;
          localStorage.setItem(STORAGE_KEY, JSON.stringify(parsed));
        }
      } catch (err: unknown) {
        const message =
          err instanceof Error ? err.message : 'فشل تحديث الإعدادات';
        throw new Error(message);
      }
    },
    []
  );

  /** اختبار SMTP */
  const testSmtp = useCallback(
    async (config: Record<string, unknown>): Promise<boolean> => {
      return systemSettingsService.testSmtp(config as never);
    },
    []
  );

  useEffect(() => {
    loadSettings();
  }, [loadSettings]);

  return (
    <SystemSettingsContext.Provider
      value={{
        settings,
        isLoading,
        error,
        isStale,
        refresh: loadSettings,
        updateGroup,
        testSmtp,
      }}
    >
      {children}
    </SystemSettingsContext.Provider>
  );
}

export function useSystemSettings() {
  const context = useContext(SystemSettingsContext);
  if (!context) {
    throw new Error(
      'useSystemSettings must be used within SystemSettingsProvider'
    );
  }
  return context;
}
```

## صفحة الإعدادات (Settings Page)

```tsx
// // ملف: src/pages/admin/SystemSettingsPage.tsx
// // صفحة إعدادات النظام مع تبويبات لكل مجموعة

import React, { useState } from 'react';
import {
  Box,
  Tabs,
  Tab,
  Typography,
  Alert,
  Snackbar,
  CircularProgress,
} from '@mui/material';
import { useSystemSettings } from '../../contexts/SystemSettingsContext';
import GeneralSettingsTab from './tabs/GeneralSettingsTab';
import FeaturesTab from './tabs/FeaturesTab';
import FeesTab from './tabs/FeesTab';
import LimitsTab from './tabs/LimitsTab';
import ExchangeTab from './tabs/ExchangeTab';
import SecurityTab from './tabs/SecurityTab';
import NotificationsTab from './tabs/NotificationsTab';
import MailTab from './tabs/MailTab';
import MaintenanceTab from './tabs/MaintenanceTab';

interface TabPanelProps {
  children?: React.ReactNode;
  index: number;
  value: number;
}

function TabPanel({ children, value, index }: TabPanelProps) {
  return value === index ? <Box sx={{ p: 3 }}>{children}</Box> : null;
}

// // ترتيب التبويبات (نفس ترتيب API)
const TABS = [
  { label: 'عام', key: 'general' },
  { label: 'الميزات', key: 'features' },
  { label: 'الرسوم', key: 'fees' },
  { label: 'الحدود', key: 'limits' },
  { label: 'صرف العملات', key: 'exchange' },
  { label: 'الأمان', key: 'security' },
  { label: 'الإشعارات', key: 'notifications' },
  { label: 'البريد', key: 'mail' },
  { label: 'الصيانة', key: 'maintenance' },
];

export default function SystemSettingsPage() {
  const [activeTab, setActiveTab] = useState(0);
  const [snackbar, setSnackbar] = useState<{
    open: boolean;
    message: string;
    severity: 'success' | 'error';
  }>({ open: false, message: '', severity: 'success' });

  const { settings, isLoading, error, isStale, refresh } = useSystemSettings();

  if (isLoading && !settings) {
    return (
      <Box display="flex" justifyContent="center" p={4}>
        <CircularProgress />
      </Box>
    );
  }

  if (error && !settings) {
    return <Alert severity="error">{error}</Alert>;
  }

  const handleSnackbarClose = () => {
    setSnackbar((prev) => ({ ...prev, open: false }));
  };

  const showMessage = (message: string, severity: 'success' | 'error') => {
    setSnackbar({ open: true, message, severity });
  };

  return (
    <Box>
      <Typography variant="h4" gutterBottom>
        إعدادات النظام
      </Typography>

      {/* إشعار الكاش القديم */}
      {isStale && (
        <Alert severity="info" sx={{ mb: 2 }} action={
          <button onClick={refresh}>تحديث</button>
        }>
          توجد بيانات مخزنة محلياً. قم بتحديث الصفحة لجلب آخر التغييرات.
        </Alert>
      )}

      {/* التبويبات */}
      <Box sx={{ borderBottom: 1, borderColor: 'divider' }}>
        <Tabs
          value={activeTab}
          onChange={(_, newValue) => setActiveTab(newValue)}
          variant="scrollable"
          scrollButtons="auto"
        >
          {TABS.map((tab) => (
            <Tab key={tab.key} label={tab.label} />
          ))}
        </Tabs>
      </Box>

      {/* محتوى كل تبويب */}
      <TabPanel value={activeTab} index={0}>
        <GeneralSettingsTab
          data={settings?.general}
          onSave={(data) => updateGroup('general', data, showMessage)}
        />
      </TabPanel>
      <TabPanel value={activeTab} index={1}>
        <FeaturesTab
          data={settings?.features}
          onSave={(data) => updateGroup('features', data, showMessage)}
        />
      </TabPanel>
      <TabPanel value={activeTab} index={2}>
        <FeesTab
          data={settings?.fees}
          onSave={(data) => updateGroup('fees', data, showMessage)}
        />
      </TabPanel>
      <TabPanel value={activeTab} index={3}>
        <LimitsTab
          data={settings?.limits}
          onSave={(data) => updateGroup('limits', data, showMessage)}
        />
      </TabPanel>
      <TabPanel value={activeTab} index={4}>
        <ExchangeTab
          data={settings?.exchange}
          onSave={(data) => updateGroup('exchange', data, showMessage)}
        />
      </TabPanel>
      <TabPanel value={activeTab} index={5}>
        <SecurityTab
          data={settings?.security}
          onSave={(data) => updateGroup('security', data, showMessage)}
        />
      </TabPanel>
      <TabPanel value={activeTab} index={6}>
        <NotificationsTab
          data={settings?.notifications}
          onSave={(data) => updateGroup('notifications', data, showMessage)}
        />
      </TabPanel>
      <TabPanel value={activeTab} index={7}>
        <MailTab
          data={settings?.mail}
          onSave={(data) => updateGroup('mail', data, showMessage)}
          onTestSmtp={(config) => testSmtp(config)}
        />
      </TabPanel>
      <TabPanel value={activeTab} index={8}>
        <MaintenanceTab
          data={settings?.maintenance}
          onSave={(data) => updateGroup('maintenance', data, showMessage)}
        />
      </TabPanel>

      {/* Snackbar للإشعارات */}
      <Snackbar
        open={snackbar.open}
        autoHideDuration={3000}
        onClose={handleSnackbarClose}
        anchorOrigin={{ vertical: 'bottom', horizontal: 'center' }}
      >
        <Alert
          onClose={handleSnackbarClose}
          severity={snackbar.severity}
          sx={{ width: '100%' }}
        >
          {snackbar.message}
        </Alert>
      </Snackbar>
    </Box>
  );
}

// // دالة مساعدة لتحديث المجموعة مع عرض رسالة
async function updateGroup(
  group: string,
  data: Record<string, unknown>,
  showMessage: (msg: string, severity: 'success' | 'error') => void
) {
  try {
    const { updateGroup } = useSystemSettings(); // سياق
    await updateGroup(group, data);
    showMessage(`تم تحديث إعدادات ${group} بنجاح`, 'success');
  } catch {
    showMessage(`فشل تحديث إعدادات ${group}`, 'error');
  }
}
```

## مكون تبويب إعدادات الرسوم (مثال على أحد التبويبات)

```tsx
// // ملف: src/pages/admin/tabs/FeesTab.tsx
// // تبويب إعدادات الرسوم

import React, { useState, useEffect } from 'react';
import {
  Box,
  TextField,
  Button,
  Grid,
  Card,
  CardContent,
  Typography,
} from '@mui/material';
import { FeeSettings } from '../../../services/systemSettings.service';

interface Props {
  data?: FeeSettings;
  onSave: (data: FeeSettings) => Promise<void>;
}

export default function FeesTab({ data, onSave }: Props) {
  const [form, setForm] = useState<FeeSettings>({
    p2p: 0,
    exchange: 1.5,
    card_deposit: 2.5,
    withdrawal: 1.0,
  });
  const [saving, setSaving] = useState(false);

  useEffect(() => {
    if (data) setForm(data);
  }, [data]);

  const handleChange = (field: keyof FeeSettings) => (
    e: React.ChangeEvent<HTMLInputElement>
  ) => {
    setForm((prev) => ({ ...prev, [field]: parseFloat(e.target.value) || 0 }));
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSaving(true);
    try {
      await onSave(form);
    } finally {
      setSaving(false);
    }
  };

  return (
    <Card>
      <CardContent>
        <Typography variant="h6" gutterBottom>
          نسب الرسوم والعمولات
        </Typography>
        <Box component="form" onSubmit={handleSubmit}>
          <Grid container spacing={2}>
            <Grid item xs={12} sm={6}>
              <TextField
                fullWidth
                label="تحويل P2P (%)"
                type="number"
                inputProps={{ step: 0.1, min: 0, max: 100 }}
                value={form.p2p}
                onChange={handleChange('p2p')}
                helperText="نسبة رسوم التحويل من شخص لشخص"
              />
            </Grid>
            <Grid item xs={12} sm={6}>
              <TextField
                fullWidth
                label="صرف العملات (%)"
                type="number"
                inputProps={{ step: 0.1, min: 0, max: 100 }}
                value={form.exchange}
                onChange={handleChange('exchange')}
              />
            </Grid>
            <Grid item xs={12} sm={6}>
              <TextField
                fullWidth
                label="إيداع بطاقة (%)"
                type="number"
                inputProps={{ step: 0.1, min: 0, max: 100 }}
                value={form.card_deposit}
                onChange={handleChange('card_deposit')}
              />
            </Grid>
            <Grid item xs={12} sm={6}>
              <TextField
                fullWidth
                label="سحب (%)"
                type="number"
                inputProps={{ step: 0.1, min: 0, max: 100 }}
                value={form.withdrawal}
                onChange={handleChange('withdrawal')}
              />
            </Grid>
          </Grid>
          <Box sx={{ mt: 2, textAlign: 'left' }}>
            <Button
              type="submit"
              variant="contained"
              disabled={saving}
            >
              {saving ? 'جاري الحفظ...' : 'حفظ التغييرات'}
            </Button>
          </Box>
        </Box>
      </CardContent>
    </Card>
  );
}
```
