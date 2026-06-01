# 17 - تطبيق React (React Implementation) - إدارة النزاعات (Disputes)

## DisputeList Page

```jsx
// src/pages/admin/DisputeList.jsx
import React, { useState } from 'react';
import { useQuery, useMutation } from '@tanstack/react-query';
import { adminApi } from '../../services/api';
import DisputeCard from '../../components/admin/disputes/DisputeCard';
import ResolveModal from '../../components/admin/disputes/ResolveModal';

export default function DisputeList() {
  const [selectedDispute, setSelectedDispute] = useState(null);

  const { data, isLoading, refetch } = useQuery({
    queryKey: ['admin-disputes'],
    queryFn: adminApi.getDisputes,
    refetchInterval: 30000,
  });

  const resolveMutation = useMutation({
    mutationFn: ({ id, resolution, partialAmount, notes }) =>
      adminApi.resolveDispute(id, resolution, partialAmount, notes),
    onSuccess: () => { setSelectedDispute(null); refetch(); },
  });

  if (isLoading) return <div>جاري التحميل...</div>;

  return (
    <div className="disputes-page">
      <h1>إدارة النزاعات</h1>

      <div className="disputes-list">
        {data?.data?.map(dispute => (
          <DisputeCard
            key={dispute.id}
            dispute={dispute}
            onResolve={() => setSelectedDispute(dispute)}
          />
        ))}

        {data?.data?.length === 0 && (
          <div className="empty-state">لا توجد نزاعات مفتوحة</div>
        )}
      </div>

      {selectedDispute && (
        <ResolveModal
          dispute={selectedDispute}
          onResolve={(resolution, amount, notes) =>
            resolveMutation.mutate({
              id: selectedDispute.id,
              resolution,
              partialAmount: amount,
              notes,
            })
          }
          onClose={() => setSelectedDispute(null)}
          isLoading={resolveMutation.isPending}
        />
      )}
    </div>
  );
}
```

## ResolveModal

```jsx
// src/components/admin/disputes/ResolveModal.jsx
import React, { useState } from 'react';

export default function ResolveModal({ dispute, onResolve, onClose, isLoading }) {
  const [resolution, setResolution] = useState('refund');
  const [partialAmount, setPartialAmount] = useState('');
  const [notes, setNotes] = useState('');

  const handleSubmit = (e) => {
    e.preventDefault();
    onResolve(resolution, resolution === 'partial_refund' ? partialAmount : null, notes);
  };

  return (
    <div className="modal-overlay" onClick={onClose}>
      <div className="modal" onClick={e => e.stopPropagation()}>
        <h2>حل النزاع</h2>

        <div className="dispute-summary">
          <p>المعاملة: {dispute.transaction?.reference_number}</p>
          <p>المبلغ: {dispute.transaction?.amount}</p>
          <p>السبب: {dispute.reason}</p>
        </div>

        <form onSubmit={handleSubmit}>
          <div className="resolution-options">
            <label>
              <input type="radio" value="refund" checked={resolution === 'refund'}
                onChange={e => setResolution(e.target.value)} />
              استرجاع كامل
            </label>
            <label>
              <input type="radio" value="partial_refund" checked={resolution === 'partial_refund'}
                onChange={e => setResolution(e.target.value)} />
              استرجاع جزئي
            </label>
            <label>
              <input type="radio" value="reject" checked={resolution === 'reject'}
                onChange={e => setResolution(e.target.value)} />
              رفض النزاع
            </label>
          </div>

          {resolution === 'partial_refund' && (
            <input type="number" step="0.01" placeholder="المبلغ الجزئي"
              value={partialAmount} onChange={e => setPartialAmount(e.target.value)} required />
          )}

          <textarea placeholder="ملاحظات المشرف (اختياري)"
            value={notes} onChange={e => setNotes(e.target.value)} rows={3} />

          <div className="modal-actions">
            <button type="button" onClick={onClose}>إلغاء</button>
            <button type="submit" disabled={isLoading}>
              {isLoading ? 'جاري...' : 'تأكيد القرار'}
            </button>
          </div>
        </form>
      </div>
    </div>
  );
}
```
