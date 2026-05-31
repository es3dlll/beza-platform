# 17 - تطبيق React (React Implementation) — مثبت Beza

## InstallerWizard — 7 خطوات

```javascript
// services/installApi.js
import axios from 'axios';

const api = axios.create({
  baseURL: '/',
  headers: { Accept: 'application/json' },
});

export const installApi = {
  welcome:        () => api.get('/install'),
  checkRequirements: () => api.post('/install/requirements'),
  setupDatabase:  (data) => api.post('/install/database', data),
  configureEnv:   (data) => api.post('/install/env', data),
  runMigrations:  () => api.post('/install/migrate'),
  createAdmin:    (data) => api.post('/install/admin', data),
  complete:       () => api.post('/install/complete'),
};
```

## هوك مخصص (Custom Hook)

```javascript
// hooks/useInstaller.js
import { useState, useCallback } from 'react';
import { installApi } from '../services/installApi';

const STEPS = ['welcome', 'requirements', 'database', 'environment', 'migration', 'admin', 'complete'];

export function useInstaller() {
  const [currentStep, setCurrentStep] = useState(0);
  const [loading, setLoading] = useState(false);
  const [error, setError] = useState(null);
  const [data, setData] = useState({});
  const [summary, setSummary] = useState(null);

  const goToNextStep = useCallback(() => {
    setCurrentStep(prev => Math.min(prev + 1, STEPS.length - 1));
    setError(null);
  }, []);

  const goToStep = useCallback((step) => {
    setCurrentStep(step);
    setError(null);
  }, []);

  const checkRequirements = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await installApi.checkRequirements();
      setData(prev => ({ ...prev, requirements: res.data.data }));
      if (res.data.data.all_pass) {
        goToNextStep();
      }
      return res.data.data;
    } catch (err) {
      setError(err.response?.data?.message || 'فشل فحص المتطلبات');
      throw err;
    } finally {
      setLoading(false);
    }
  }, [goToNextStep]);

  const setupDatabase = useCallback(async (formData) => {
    setLoading(true);
    setError(null);
    try {
      const res = await installApi.setupDatabase(formData);
      setData(prev => ({ ...prev, db: formData }));
      goToNextStep();
      return res.data;
    } catch (err) {
      setError(err.response?.data?.message || 'فشل الاتصال بقاعدة البيانات');
      throw err;
    } finally {
      setLoading(false);
    }
  }, [goToNextStep]);

  const configureEnv = useCallback(async (formData) => {
    setLoading(true);
    setError(null);
    try {
      const res = await installApi.configureEnv(formData);
      setData(prev => ({ ...prev, env: formData }));
      goToNextStep();
      return res.data;
    } catch (err) {
      setError(err.response?.data?.message || 'فشل إعداد البيئة');
      throw err;
    } finally {
      setLoading(false);
    }
  }, [goToNextStep]);

  const runMigrations = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await installApi.runMigrations();
      setData(prev => ({ ...prev, migrations: res.data.data }));
      goToNextStep();
      return res.data;
    } catch (err) {
      setError(err.response?.data?.message || 'فشل تشغيل الترحيلات');
      throw err;
    } finally {
      setLoading(false);
    }
  }, [goToNextStep]);

  const createAdmin = useCallback(async (formData) => {
    setLoading(true);
    setError(null);
    try {
      const res = await installApi.createAdmin(formData);
      setData(prev => ({ ...prev, admin: res.data.data }));
      goToNextStep();
      return res.data;
    } catch (err) {
      setError(err.response?.data?.message || 'فشل إنشاء المشرف');
      throw err;
    } finally {
      setLoading(false);
    }
  }, [goToNextStep]);

  const complete = useCallback(async () => {
    setLoading(true);
    setError(null);
    try {
      const res = await installApi.complete();
      setSummary(res.data.data.summary);
      goToNextStep();
      return res.data;
    } catch (err) {
      setError(err.response?.data?.message || 'فشل إكمال التنصيب');
      throw err;
    } finally {
      setLoading(false);
    }
  }, [goToNextStep]);

  return {
    currentStep,
    loading,
    error,
    data,
    summary,
    totalSteps: STEPS.length,
    stepName: STEPS[currentStep],
    isFirstStep: currentStep === 0,
    isLastStep: currentStep === STEPS.length - 1,
    actions: {
      checkRequirements,
      setupDatabase,
      configureEnv,
      runMigrations,
      createAdmin,
      complete,
      goToStep,
    },
  };
}
```

## InstallerPage — الواجهة الرئيسية

```jsx
// pages/InstallerPage.jsx
import React, { useEffect, useState } from 'react';
import { installApi } from '../services/installApi';
import { useInstaller } from '../hooks/useInstaller';

export default function InstallerPage() {
  const { currentStep, loading, error, data, summary, stepName, isLastStep, actions } = useInstaller();
  const [appName, setAppName] = useState('Beza Platform');

  useEffect(() => {
    installApi.welcome().then(res => {
      setAppName(res.data.data.app_name);
    }).catch(() => {});
  }, []);

  if (summary) {
    return <InstallationSummary summary={summary} />;
  }

  return (
    <div className="installer-container">
      <header>
        <h1>{appName}</h1>
        <p>مثبت النظام — الخطوة {currentStep + 1} من 7</p>
      </header>

      <ProgressBar currentStep={currentStep} totalSteps={7} />

      {error && <div className="alert alert-error">{error}</div>}

      {stepName === 'welcome' && <WelcomeStep onNext={() => actions.goToStep(1)} />}
      {stepName === 'requirements' && <RequirementsStep data={data.requirements} onCheck={actions.checkRequirements} loading={loading} />}
      {stepName === 'database' && <DatabaseStep onSubmit={actions.setupDatabase} loading={loading} />}
      {stepName === 'environment' && <EnvironmentStep onSubmit={actions.configureEnv} loading={loading} />}
      {stepName === 'migration' && <MigrationStep onRun={actions.runMigrations} loading={loading} />}
      {stepName === 'admin' && <AdminStep onSubmit={actions.createAdmin} loading={loading} />}
      {stepName === 'complete' && <CompleteStep onFinish={actions.complete} loading={loading} />}
    </div>
  );
}

// شريط التقدم
function ProgressBar({ currentStep, totalSteps }) {
  const steps = ['ترحيب', 'متطلبات', 'قاعدة بيانات', 'بيئة', 'ترحيلات', 'مشرف', 'إكمال'];
  return (
    <div className="progress-bar">
      {steps.map((label, i) => (
        <div key={i} className={`step ${i <= currentStep ? 'active' : ''} ${i < currentStep ? 'completed' : ''}`}>
          <div className="step-number">{i < currentStep ? '✓' : i + 1}</div>
          <div className="step-label">{label}</div>
        </div>
      ))}
    </div>
  );
}

// الخطوة 0: الترحيب
function WelcomeStep({ onNext }) {
  return (
    <div className="step-content">
      <h2>مرحباً بك في مثبت Beza</h2>
      <p>سيقوم هذا المثبت بإعداد منصة Beza على خادمك. يتكون من 7 خطوات بسيطة.</p>
      <ul>
        <li>فحص متطلبات PHP والخادم</li>
        <li>إعداد قاعدة البيانات MySQL</li>
        <li>تهيئة ملف البيئة (.env)</li>
        <li>تشغيل ترحيلات قاعدة البيانات</li>
        <li>إنشاء المشرف الأول</li>
      </ul>
      <p className="warning">تأكد من توفر معلومات اتصال MySQL و Redis قبل البدء.</p>
      <button onClick={onNext} className="btn-primary">ابدأ</button>
    </div>
  );
}

// الخطوة 1: فحص المتطلبات
function RequirementsStep({ data, onCheck, loading }) {
  const [result, setResult] = useState(data);

  const handleCheck = async () => {
    const res = await onCheck();
    setResult(res);
  };

  return (
    <div className="step-content">
      <h2>فحص متطلبات النظام</h2>
      <p>سيتم فحص PHP والإضافات والأوامر المطلوبة.</p>

      {!result && (
        <button onClick={handleCheck} disabled={loading} className="btn-primary">
          {loading ? 'جاري الفحص...' : 'فحص المتطلبات'}
        </button>
      )}

      {result && (
        <div className="requirements-list">
          {Object.entries(result.items).map(([key, item]) => (
            <div key={key} className={`requirement-item ${item.pass ? 'pass' : 'fail'}`}>
              <span className="status-icon">{item.pass ? '✓' : '✗'}</span>
              <span className="requirement-message">{item.message}</span>
            </div>
          ))}

          <div className={`requirement-summary ${result.all_pass ? 'pass' : 'fail'}`}>
            {result.all_pass ? '✓ جميع المتطلبات مستوفاة' : '✗ يوجد متطلبات غير مستوفاة'}
          </div>

          {result.all_pass && (
            <button onClick={() => {}} className="btn-primary">التالي</button>
          )}
        </div>
      )}
    </div>
  );
}

// الخطوة 2: إعداد قاعدة البيانات
function DatabaseStep({ onSubmit, loading }) {
  const [form, setForm] = useState({ db_host: '127.0.0.1', db_port: 3306, db_database: '', db_username: '', db_password: '' });

  const handleSubmit = (e) => {
    e.preventDefault();
    onSubmit(form);
  };

  return (
    <div className="step-content">
      <h2>إعداد قاعدة البيانات</h2>
      <p>أدخل معلومات اتصال MySQL.</p>
      <form onSubmit={handleSubmit}>
        <input placeholder="المضيف" value={form.db_host} onChange={e => setForm({...form, db_host: e.target.value})} />
        <input type="number" placeholder="المنفذ" value={form.db_port} onChange={e => setForm({...form, db_port: +e.target.value})} />
        <input placeholder="اسم قاعدة البيانات" value={form.db_database} onChange={e => setForm({...form, db_database: e.target.value})} />
        <input placeholder="اسم المستخدم" value={form.db_username} onChange={e => setForm({...form, db_username: e.target.value})} />
        <input type="password" placeholder="كلمة المرور" value={form.db_password} onChange={e => setForm({...form, db_password: e.target.value})} />
        <button type="submit" disabled={loading} className="btn-primary">
          {loading ? 'جاري الاتصال...' : 'اختبار الاتصال'}
        </button>
      </form>
    </div>
  );
}

// الخطوة 3: إعداد البيئة
function EnvironmentStep({ onSubmit, loading }) {
  const [form, setForm] = useState({
    app_name: 'Beza', app_url: '', app_env: 'production',
    redis_host: '127.0.0.1', redis_port: 6379, redis_password: '',
    mail_host: '', mail_port: 587, mail_username: '', mail_password: '', mail_encryption: 'tls',
    queue_connection: 'redis',
  });

  const handleSubmit = (e) => {
    e.preventDefault();
    onSubmit(form);
  };

  return (
    <div className="step-content">
      <h2>إعداد البيئة</h2>
      <p>أدخل إعدادات التطبيق و Redis والبريد الإلكتروني.</p>
      <form onSubmit={handleSubmit}>
        <h3>التطبيق</h3>
        <input placeholder="اسم التطبيق" value={form.app_name} onChange={e => setForm({...form, app_name: e.target.value})} />
        <input placeholder="رابط التطبيق" value={form.app_url} onChange={e => setForm({...form, app_url: e.target.value})} />
        <select value={form.app_env} onChange={e => setForm({...form, app_env: e.target.value})}>
          <option value="local">تطويري</option>
          <option value="staging">اختباري</option>
          <option value="production">إنتاجي</option>
        </select>

        <h3>Redis</h3>
        <input placeholder="مضيف Redis" value={form.redis_host} onChange={e => setForm({...form, redis_host: e.target.value})} />
        <input type="number" placeholder="منفذ Redis" value={form.redis_port} onChange={e => setForm({...form, redis_port: +e.target.value})} />
        <input placeholder="كلمة مرور Redis (اختياري)" value={form.redis_password} onChange={e => setForm({...form, redis_password: e.target.value})} />

        <h3>البريد الإلكتروني (اختياري)</h3>
        <input placeholder="خادم SMTP" value={form.mail_host} onChange={e => setForm({...form, mail_host: e.target.value})} />
        <input type="number" placeholder="منفذ SMTP" value={form.mail_port} onChange={e => setForm({...form, mail_port: +e.target.value})} />
        <input placeholder="اسم المستخدم SMTP" value={form.mail_username} onChange={e => setForm({...form, mail_username: e.target.value})} />
        <input type="password" placeholder="كلمة مرور SMTP" value={form.mail_password} onChange={e => setForm({...form, mail_password: e.target.value})} />
        <select value={form.mail_encryption} onChange={e => setForm({...form, mail_encryption: e.target.value})}>
          <option value="tls">TLS</option>
          <option value="ssl">SSL</option>
          <option value="null">بدون تشفير</option>
        </select>

        <h3>نظام الطوابير</h3>
        <select value={form.queue_connection} onChange={e => setForm({...form, queue_connection: e.target.value})}>
          <option value="sync">مباشر</option>
          <option value="database">قاعدة بيانات</option>
          <option value="redis">Redis</option>
        </select>

        <button type="submit" disabled={loading} className="btn-primary">
          {loading ? 'جاري الحفظ...' : 'حفظ الإعدادات'}
        </button>
      </form>
    </div>
  );
}

// الخطوة 4: تشغيل الترحيلات
function MigrationStep({ onRun, loading }) {
  const [output, setOutput] = useState(null);

  const handleRun = async () => {
    const res = await onRun();
    setOutput(res);
  };

  return (
    <div className="step-content">
      <h2>تشغيل الترحيلات</h2>
      <p>سيتم إنشاء جميع جداول قاعدة البيانات وتعبئتها بالبيانات الأولية.</p>

      {!output && (
        <button onClick={handleRun} disabled={loading} className="btn-primary">
          {loading ? 'جاري تشغيل الترحيلات...' : 'تشغيل الترحيلات'}
        </button>
      )}

      {output && (
        <div>
          <div className="success-message">✓ تم إنشاء الجداول بنجاح</div>
          <button onClick={() => {}} className="btn-primary">التالي</button>
        </div>
      )}
    </div>
  );
}

// الخطوة 5: إنشاء المشرف
function AdminStep({ onSubmit, loading }) {
  const [form, setForm] = useState({ name: '', email: '', phone: '', password: '', password_confirmation: '' });

  const handleSubmit = (e) => {
    e.preventDefault();
    onSubmit(form);
  };

  return (
    <div className="step-content">
      <h2>إنشاء المشرف الأول</h2>
      <p>أدخل بيانات مدير النظام.</p>
      <form onSubmit={handleSubmit}>
        <input placeholder="الاسم" value={form.name} onChange={e => setForm({...form, name: e.target.value})} />
        <input type="email" placeholder="البريد الإلكتروني" value={form.email} onChange={e => setForm({...form, email: e.target.value})} />
        <input placeholder="رقم الهاتف (09XXXXXXXX)" value={form.phone} onChange={e => setForm({...form, phone: e.target.value})} maxLength={10} />
        <input type="password" placeholder="كلمة المرور" value={form.password} onChange={e => setForm({...form, password: e.target.value})} />
        <input type="password" placeholder="تأكيد كلمة المرور" value={form.password_confirmation} onChange={e => setForm({...form, password_confirmation: e.target.value})} />
        <button type="submit" disabled={loading} className="btn-primary">
          {loading ? 'جاري الإنشاء...' : 'إنشاء المشرف'}
        </button>
      </form>
    </div>
  );
}

// الخطوة 6: الإكمال
function CompleteStep({ onFinish, loading }) {
  return (
    <div className="step-content">
      <h2>إنهاء التنصيب</h2>
      <p>سيتم تعطيل المثبت وعرض ملخص التنصيب.</p>
      <button onClick={onFinish} disabled={loading} className="btn-primary">
        {loading ? 'جاري...' : 'إنهاء التنصيب'}
      </button>
    </div>
  );
}

// شاشة الملخص النهائي
function InstallationSummary({ summary }) {
  return (
    <div className="installer-container">
      <div className="summary-card">
        <h1>✓ تم إكمال التنصيب بنجاح</h1>
        <div className="summary-details">
          <h3>معلومات التطبيق</h3>
          <p>الاسم: {summary.app_name}</p>
          <p>الرابط: <a href={summary.app_url}>{summary.app_url}</a></p>

          <h3>بيانات المشرف</h3>
          <p>الاسم: {summary.admin_name}</p>
          <p>البريد: {summary.admin_email}</p>
          <p>الهاتف: {summary.admin_phone}</p>

          <h3>قاعدة البيانات</h3>
          <p>المضيف: {summary.db_host}</p>
          <p>اسم DB: {summary.db_name}</p>

          <h3>النظام</h3>
          <p>PHP: {summary.php_version}</p>
          <p>تم في: {summary.completed_at}</p>
        </div>
        <div className="warning">
          ⚠ تم تعطيل المثبت. احتفظ ببيانات الدخول في مكان آمن.
        </div>
        <a href={summary.app_url + '/admin'} className="btn-primary">
          الذهاب إلى لوحة التحكم
        </a>
      </div>
    </div>
  );
}
```

## Styles

```css
/* styles/installer.css */
.installer-container {
  max-width: 800px;
  margin: 40px auto;
  padding: 20px;
  direction: rtl;
  font-family: 'Tajawal', sans-serif;
}

.progress-bar {
  display: flex;
  justify-content: space-between;
  margin: 30px 0;
}

.step {
  text-align: center;
  flex: 1;
}

.step-number {
  width: 36px;
  height: 36px;
  border-radius: 50%;
  background: #e0e0e0;
  display: flex;
  align-items: center;
  justify-content: center;
  margin: 0 auto;
  font-weight: bold;
}

.step.active .step-number { background: #4CAF50; color: white; }
.step.completed .step-number { background: #2196F3; color: white; }

.step-label { font-size: 12px; margin-top: 4px; }

.step-content { background: white; padding: 30px; border-radius: 8px; box-shadow: 0 2px 10px rgba(0,0,0,0.1); }

.step-content input,
.step-content select {
  width: 100%;
  padding: 12px;
  margin: 8px 0;
  border: 1px solid #ddd;
  border-radius: 4px;
  font-size: 14px;
}

.btn-primary {
  background: #4CAF50;
  color: white;
  padding: 14px 28px;
  border: none;
  border-radius: 4px;
  font-size: 16px;
  cursor: pointer;
  margin: 16px 0;
}

.btn-primary:disabled { background: #9e9e9e; cursor: not-allowed; }

.alert-error {
  background: #ffebee;
  color: #c62828;
  padding: 12px;
  border-radius: 4px;
  margin: 16px 0;
}

.success-message {
  background: #e8f5e9;
  color: #2e7d32;
  padding: 16px;
  border-radius: 4px;
  font-size: 18px;
  text-align: center;
}

.warning {
  background: #fff3e0;
  color: #e65100;
  padding: 12px;
  border-radius: 4px;
  margin: 16px 0;
}

.summary-card {
  background: white;
  padding: 40px;
  border-radius: 8px;
  box-shadow: 0 4px 20px rgba(0,0,0,0.1);
  text-align: center;
}

.summary-details {
  text-align: right;
  margin: 20px auto;
  max-width: 400px;
}
```
