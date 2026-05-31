# 17 - تطبيق React (React Implementation) - دعوة صديق + برنامج ولاء

## ReferralPage

```jsx
import React, { useState, useEffect } from 'react';
import api from '../../services/api';

export default function ReferralPage() {
  const [referralCode, setReferralCode] = useState(null);
  const [rewards, setRewards] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    loadReferralData();
  }, []);

  const loadReferralData = async () => {
    try {
      const [codeRes, rewardsRes] = await Promise.all([
        api.post('/referral/code'),
        api.get('/referral/rewards'),
      ]);
      setReferralCode(codeRes.data.data.code);
      setRewards(rewardsRes.data.data);
    } catch (err) {
      console.error(err);
    } finally {
      setLoading(false);
    }
  };

  const handleShare = () => {
    if (navigator.share) {
      navigator.share({
        title: 'انضم إلى Beza',
        text: `انضم إلى Beza باستخدام كود الإحالة الخاص بي: ${referralCode.code}`,
      });
    } else {
      navigator.clipboard.writeText(referralCode.code);
      alert('تم نسخ الكود!');
    }
  };

  if (loading) return <div>جاري التحميل...</div>;

  const totalRewards = rewards.reduce((sum, r) =>
    r.status === 'paid' ? sum + Number(r.referrer_amount) : sum, 0
  );

  return (
    <div className="referral-page">
      <h1>دعوة صديق</h1>
      <div className="referral-code-box">
        <p>كود الإحالة الخاص بك</p>
        <h2 dir="ltr">{referralCode?.code}</h2>
        <button onClick={handleShare}>مشاركة</button>
      </div>

      <div className="rewards-summary">
        <h3>إجمالي المكافآت: ${totalRewards.toFixed(2)}</h3>
      </div>

      <div className="rewards-list">
        <h3>المكافآت</h3>
        {rewards.map(reward => (
          <div key={reward.id} className="reward-item">
            <span>{reward.referred?.name}</span>
            <span>${reward.referrer_amount}</span>
            <span>{reward.status === 'paid' ? 'مدفوعة' : 'معلقة'}</span>
          </div>
        ))}
      </div>
    </div>
  );
}
```
