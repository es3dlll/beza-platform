# 17 - تطبيق React (React Implementation) - الموافقة على التجار والوكلاء (Approval)

## MerchantApplications Page

```jsx
// src/pages/admin/MerchantApplications.jsx
import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { adminApi } from '../../services/api';
import ApplicationCard from '../../components/admin/ApplicationCard';
import RejectModal from '../../components/admin/RejectModal';

export default function MerchantApplications() {
  const [rejectTarget, setRejectTarget] = useState(null);

  const { data: applications, isLoading, refetch } = useQuery({
    queryKey: ['merchant-applications'],
    queryFn: adminApi.getMerchantApplications,
    refetchInterval: 30000,
  });

  const approveMutation = useMutation({
    mutationFn: (id) => adminApi.approveMerchant(id),
    onSuccess: () => refetch(),
  });

  const rejectMutation = useMutation({
    mutationFn: ({ id, reason }) => adminApi.rejectMerchant(id, reason),
    onSuccess: () => { setRejectTarget(null); refetch(); },
  });

  if (isLoading) return <div>جاري التحميل...</div>;

  return (
    <div className="applications-page">
      <h1>طلبات التجار</h1>
      <p className="subtitle">مراجعة طلبات تسجيل التجار الجدد</p>

      <div className="applications-grid">
        {applications?.data?.length === 0 && (
          <div className="empty-state">لا توجد طلبات معلقة</div>
        )}

        {applications?.data?.map(app => (
          <ApplicationCard
            key={app.id}
            application={app}
            onApprove={() => approveMutation.mutate(app.id)}
            onReject={() => setRejectTarget(app)}
            isProcessing={approveMutation.isPending}
          />
        ))}
      </div>

      {rejectTarget && (
        <RejectModal
          name={rejectTarget.business_name}
          onConfirm={(reason) => rejectMutation.mutate({
            id: rejectTarget.id, reason
          })}
          onClose={() => setRejectTarget(null)}
          isLoading={rejectMutation.isPending}
        />
      )}
    </div>
  );
}
```

## ApplicationCard Component

```jsx
// src/components/admin/ApplicationCard.jsx
export default function ApplicationCard({ application, onApprove, onReject, isProcessing }) {
  return (
    <div className="application-card">
      <div className="card-header">
        <h3>{application.business_name}</h3>
        <span className={`status-badge ${application.status}`}>
          {application.status === 'pending' ? 'قيد المراجعة' : application.status}
        </span>
      </div>

      <div className="card-body">
        <div className="info-row">
          <span>صاحب الطلب:</span>
          <strong>{application.user?.name}</strong>
        </div>
        <div className="info-row">
          <span>الهاتف:</span>
          <strong dir="ltr">{application.user?.phone}</strong>
        </div>
        <div className="info-row">
          <span>النشاط:</span>
          <strong>{application.business_type || '—'}</strong>
        </div>
        <div className="info-row">
          <span>السجل التجاري:</span>
          <strong>{application.commercial_reg_no || '—'}</strong>
        </div>
        <div className="info-row">
          <span>حالة KYC:</span>
          <strong>{application.user?.kyc_status === 'verified' ? '✅ موثق' : '⏳ غير موثق'}</strong>
        </div>
      </div>

      {application.documents?.length > 0 && (
        <div className="documents">
          <h4>المستندات:</h4>
          <div className="document-list">
            {application.documents.map(doc => (
              <a key={doc.id} href={doc.file_path} target="_blank" rel="noreferrer">
                📄 {doc.type === 'commercial_reg' ? 'سجل تجاري'
                  : doc.type === 'tax_card' ? 'بطاقة ضريبية'
                  : doc.type === 'id_photo' ? 'صورة هوية'
                  : doc.type === 'license' ? 'رخصة'
                  : doc.type === 'contract' ? 'عقد'
                  : doc.original_name}
              </a>
            ))}
          </div>
        </div>
      )}

      <div className="card-actions">
        <button
          onClick={onApprove}
          disabled={isProcessing}
          className="btn-approve"
        >
          ✅ موافقة
        </button>
        <button
          onClick={onReject}
          disabled={isProcessing}
          className="btn-reject"
        >
          ❌ رفض
        </button>
      </div>
    </div>
  );
}
```
