# 17 - تطبيق React (React Implementation) - تقديم ومراجعة وثائق KYC

## KycPage

```jsx
import React, { useState, useEffect } from 'react';
import api from '../../services/api';

export default function KycPage() {
  const [kycStatus, setKycStatus] = useState(null);
  const [files, setFiles] = useState({});
  const [docType, setDocType] = useState('ID');
  const [loading, setLoading] = useState(false);
  const [message, setMessage] = useState(null);

  useEffect(() => {
    api.get('/kyc/status').then(res => setKycStatus(res.data.data.status));
  }, []);

  const handleFileChange = (field) => (e) => {
    setFiles(prev => ({ ...prev, [field]: e.target.files[0] }));
  };

  const handleSubmit = async () => {
    setLoading(true);
    setMessage(null);
    const formData = new FormData();
    formData.append('front_id', files.front_id);
    formData.append('back_id', files.back_id);
    formData.append('selfie', files.selfie);
    formData.append('address_proof', files.address_proof);
    formData.append('doc_type', docType);

    try {
      const res = await api.post('/kyc/submit', formData);
      setMessage(res.data.message);
      setKycStatus(res.data.data.status);
    } catch (err) {
      setMessage(err.response?.data?.message || 'حدث خطأ');
    } finally {
      setLoading(false);
    }
  };

  if (!kycStatus) return <div>جاري التحميل...</div>;

  if (kycStatus.kyc_status === 'verified') {
    return (
      <div className="kyc-verified">
        <div className="success-icon">✓</div>
        <h1>تم التحقق من هويتك</h1>
      </div>
    );
  }

  if (kycStatus.kyc_status === 'pending') {
    return (
      <div className="kyc-pending">
        <h1>المستندات قيد المراجعة</h1>
        <p>سنقوم بإشعارك عند الانتهاء من المراجعة</p>
      </div>
    );
  }

  return (
    <div className="kyc-page">
      <h1>التحقق من الهوية (KYC)</h1>
      {message && <div className="alert">{message}</div>}

      <div className="form-group">
        <label>نوع الوثيقة</label>
        <select value={docType} onChange={e => setDocType(e.target.value)}>
          <option value="ID">بطاقة هوية</option>
          <option value="Passport">جواز سفر</option>
          <option value="Driver_License">رخصة قيادة</option>
        </select>
      </div>

      <FileInput label="صورة الهوية الأمامية" onChange={handleFileChange('front_id')} />
      <FileInput label="صورة الهوية الخلفية" onChange={handleFileChange('back_id')} />
      <FileInput label="صورة شخصية (selfie)" onChange={handleFileChange('selfie')} />
      <FileInput label="إثبات العنوان" onChange={handleFileChange('address_proof')} accept="image/*,.pdf" />

      <button onClick={handleSubmit} disabled={loading}>
        {loading ? 'جاري الرفع...' : 'تقديم الطلب'}
      </button>
    </div>
  );
}

function FileInput({ label, onChange, accept = 'image/*' }) {
  return (
    <div className="form-group">
      <label>{label}</label>
      <input type="file" onChange={onChange} accept={accept} />
    </div>
  );
}
```
