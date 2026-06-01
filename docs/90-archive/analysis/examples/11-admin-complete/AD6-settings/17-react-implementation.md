# 17 - تطبيق React (React Implementation) - إعدادات النظام (Admin Settings)

## SettingsPage

```jsx
// src/pages/admin/SettingsPage.jsx
import React from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { adminApi } from '../../services/api';
import GeneralSettings from '../../components/admin/settings/GeneralSettings';
import FeeSettings from '../../components/admin/settings/FeeSettings';
import LimitSettings from '../../components/admin/settings/LimitSettings';
import ExchangeRateSettings from '../../components/admin/settings/ExchangeRateSettings';
import toast from 'react-hot-toast';

export default function SettingsPage() {
  const { data, isLoading, refetch } = useQuery({
    queryKey: ['admin-settings'],
    queryFn: adminApi.getSettings,
  });

  const generalMutation = useMutation({
    mutationFn: adminApi.updateGeneralSettings,
    onSuccess: () => { toast.success('تم تحديث الإعدادات العامة'); refetch(); },
  });

  const feesMutation = useMutation({
    mutationFn: adminApi.updateFeeSettings,
    onSuccess: () => { toast.success('تم تحديث الرسوم'); refetch(); },
  });

  const limitsMutation = useMutation({
    mutationFn: adminApi.updateLimitSettings,
    onSuccess: () => { toast.success('تم تحديث الحدود'); refetch(); },
  });

  const exchangeMutation = useMutation({
    mutationFn: adminApi.updateExchangeRate,
    onSuccess: () => { toast.success('تم تحديث سعر الصرف'); refetch(); },
  });

  if (isLoading) return <div>جاري التحميل...</div>;

  const settings = data?.data;

  return (
    <div className="settings-page">
      <h1>إعدادات المنصة</h1>

      <div className="settings-grid">
        <div className="settings-card">
          <h2>الإعدادات العامة</h2>
          <GeneralSettings
            settings={settings.general}
            onSave={(v) => generalMutation.mutate(v)}
            isLoading={generalMutation.isPending}
          />
        </div>

        <div className="settings-card">
          <h2>رسوم المعاملات</h2>
          <FeeSettings
            settings={settings.fees}
            onSave={(v) => feesMutation.mutate(v)}
            isLoading={feesMutation.isPending}
          />
        </div>

        <div className="settings-card">
          <h2>الحدود</h2>
          <LimitSettings
            settings={settings.limits}
            onSave={(v) => limitsMutation.mutate(v)}
            isLoading={limitsMutation.isPending}
          />
        </div>

        <div className="settings-card">
          <h2>سعر الصرف</h2>
          <ExchangeRateSettings
            settings={settings.exchange}
            onSave={(v) => exchangeMutation.mutate(v)}
            isLoading={exchangeMutation.isPending}
          />
        </div>
      </div>
    </div>
  );
}
```

## FeeSettings Component

```jsx
// src/components/admin/settings/FeeSettings.jsx
import React, { useState } from 'react';

export default function FeeSettings({ settings, onSave, isLoading }) {
  const [fees, setFees] = useState(settings);

  const handleChange = (field) => (e) => {
    setFees(prev => ({ ...prev, [field]: parseFloat(e.target.value) || 0 }));
  };

  return (
    <div className="settings-form">
      <div className="field">
        <label>تحويل P2P (%)</label>
        <input type="number" step="0.1" value={fees.transfer}
          onChange={handleChange('transfer')} />
      </div>
      <div className="field">
        <label>صرافة (%)</label>
        <input type="number" step="0.1" value={fees.exchange}
          onChange={handleChange('exchange')} />
      </div>
      <div className="field">
        <label>تحميل بطاقة (%)</label>
        <input type="number" step="0.1" value={fees.card_load}
          onChange={handleChange('card_load')} />
      </div>
      <div className="field">
        <label>عمولة تاجر (%)</label>
        <input type="number" step="0.1" value={fees.merchant?.percent}
          onChange={handleChange('merchant_percent')} />
      </div>
      <button onClick={() => onSave(fees)} disabled={isLoading}>
        {isLoading ? 'جاري الحفظ...' : 'حفظ الرسوم'}
      </button>
    </div>
  );
}
```
