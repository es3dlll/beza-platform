import { useState } from 'react';
import { apiClient } from '../core/api-client';
import { ApiRoutes, AppConstants } from '../core/constants';

interface RecipientInfo {
  id: string;
  name: string;
  email: string;
  wallet_id: string;
}

export function TransferPage() {
  const [email, setEmail] = useState('');
  const [recipient, setRecipient] = useState<RecipientInfo | null>(null);
  const [amountFils, setAmountFils] = useState('');
  const [description, setDescription] = useState('');
  const [loading, setLoading] = useState(false);
  const [lookupLoading, setLookupLoading] = useState(false);
  const [error, setError] = useState<string | null>(null);
  const [success, setSuccess] = useState<{ entryId: string; amount: string } | null>(null);
  const [lookupError, setLookupError] = useState<string | null>(null);

  const handleLookup = async () => {
    if (!email.trim()) return;
    setLookupLoading(true);
    setLookupError(null);
    setRecipient(null);
    setError(null);
    setSuccess(null);
    try {
      const res = await apiClient.get<RecipientInfo>(`${ApiRoutes.userLookup}/${encodeURIComponent(email)}`);
      if (res.success && res.data) {
        setRecipient(res.data);
      } else {
        setLookupError(res.message || 'لم يتم العثور على المستخدم');
      }
    } catch {
      setLookupError('حدث خطأ في الاتصال بالخادم');
    } finally {
      setLookupLoading(false);
    }
  };

  const handleTransfer = async (e: React.FormEvent) => {
    e.preventDefault();
    if (!recipient || !amountFils) return;

    const fils = parseInt(amountFils, 10);
    if (isNaN(fils) || fils < 1000) {
      setError('الحد الأدنى للتحويل هو 1,000 فلس (1 ل.س)');
      return;
    }
    if (fils > 100000000000) {
      setError('الحد الأقصى للتحويل هو 100,000,000,000 فلس');
      return;
    }

    setLoading(true);
    setError(null);
    setSuccess(null);
    try {
      const res = await apiClient.post<{ entry_id: string; amount_fils: number; currency: string }>(
        ApiRoutes.walletTransfer,
        { to_wallet_id: recipient.wallet_id, amount_fils: fils, currency: 'SYP' },
      );
      if (res.success && res.data) {
        setSuccess({
          entryId: res.data.entry_id,
          amount: `${(res.data.amount_fils / AppConstants.filsPerSYP).toLocaleString('ar-SA')} ${AppConstants.currencySymbol}`,
        });
        setAmountFils('');
        setDescription('');
        setRecipient(null);
        setEmail('');
      } else {
        setError(res.message || 'فشل التحويل');
      }
    } catch {
      setError('حدث خطأ في الاتصال بالخادم');
    } finally {
      setLoading(false);
    }
  };

  const resetForm = () => {
    setEmail('');
    setRecipient(null);
    setAmountFils('');
    setDescription('');
    setError(null);
    setSuccess(null);
    setLookupError(null);
  };

  return (
    <div dir="rtl" style={{ padding: 24, maxWidth: 600, margin: '0 auto' }}>
      <h2>تحويل مالي</h2>

      {success && (
        <div style={{ padding: 16, background: '#d4edda', borderRadius: 8, marginBottom: 16 }}>
          <p style={{ fontWeight: 'bold', margin: 0 }}>✅ تم التحويل بنجاح</p>
          <p style={{ margin: '8px 0 0' }}>المبلغ: {success.amount}</p>
          <p style={{ margin: '4px 0 0', fontSize: 13, color: '#555' }}>
            معرف المعاملة: {success.entryId}
            <button
              onClick={() => navigator.clipboard.writeText(success.entryId)}
              style={{ marginRight: 8, padding: '2px 8px', fontSize: 12, cursor: 'pointer' }}
            >
              نسخ
            </button>
          </p>
          <button onClick={resetForm} style={{ marginTop: 12, padding: '8px 16px', cursor: 'pointer' }}>
            تحويل جديد
          </button>
        </div>
      )}

      {!success && (
        <form onSubmit={handleTransfer}>
          {/* Step 1: Recipient lookup */}
          <div style={{ marginBottom: 20 }}>
            <label style={{ display: 'block', marginBottom: 4, fontWeight: 'bold' }}>البريد الإلكتروني للمستلم</label>
            <div style={{ display: 'flex', gap: 8 }}>
              <input
                type="email"
                value={email}
                onChange={(e) => setEmail(e.target.value)}
                placeholder="user@example.com"
                required
                style={{ flex: 1, padding: 8, borderRadius: 4, border: '1px solid #ccc' }}
                dir="rtl"
              />
              <button
                type="button"
                onClick={handleLookup}
                disabled={lookupLoading || !email.trim()}
                style={{ padding: '8px 16px', borderRadius: 4, border: '1px solid #ccc', cursor: 'pointer', background: lookupLoading ? '#eee' : '#1A6B4E', color: 'white' }}
              >
                {lookupLoading ? '...' : 'بحث'}
              </button>
            </div>
            {lookupError && <p style={{ color: 'red', fontSize: 13, marginTop: 4 }}>{lookupError}</p>}
            {recipient && (
              <div style={{ marginTop: 8, padding: 12, background: '#e8f5e9', borderRadius: 6 }}>
                <p style={{ margin: 0 }}><strong>المستلم:</strong> {recipient.name}</p>
                <p style={{ margin: '4px 0 0', fontSize: 13, color: '#555' }}>{recipient.email}</p>
              </div>
            )}
          </div>

          {/* Step 2: Amount */}
          <div style={{ marginBottom: 20 }}>
            <label style={{ display: 'block', marginBottom: 4, fontWeight: 'bold' }}>المبلغ (فلس)</label>
            <input
              type="number"
              value={amountFils}
              onChange={(e) => setAmountFils(e.target.value)}
              placeholder="1000"
              min="1000"
              required
              disabled={!recipient}
              style={{ width: '100%', padding: 8, borderRadius: 4, border: '1px solid #ccc' }}
              dir="rtl"
            />
            {amountFils && (
              <p style={{ fontSize: 13, color: '#666', marginTop: 4 }}>
                {(parseInt(amountFils) / 1000).toLocaleString('ar-SA')} ل.س
              </p>
            )}
          </div>

          {/* Step 3: Description */}
          <div style={{ marginBottom: 20 }}>
            <label style={{ display: 'block', marginBottom: 4, fontWeight: 'bold' }}>الوصف (اختياري)</label>
            <input
              type="text"
              value={description}
              onChange={(e) => setDescription(e.target.value)}
              placeholder="سبب التحويل"
              disabled={!recipient}
              style={{ width: '100%', padding: 8, borderRadius: 4, border: '1px solid #ccc' }}
              dir="rtl"
            />
          </div>

          {error && (
            <div style={{ padding: 12, background: '#fdd', borderRadius: 8, marginBottom: 16, color: 'red' }}>
              {error}
            </div>
          )}

          <button
            type="submit"
            disabled={loading || !recipient || !amountFils}
            style={{
              width: '100%', padding: 12, borderRadius: 4, border: 'none',
              background: loading || !recipient ? '#999' : '#1A6B4E', color: 'white',
              fontSize: 16, cursor: loading || !recipient ? 'not-allowed' : 'pointer',
            }}
          >
            {loading ? 'جاري التنفيذ...' : 'تنفيذ التحويل'}
          </button>
        </form>
      )}
    </div>
  );
}
