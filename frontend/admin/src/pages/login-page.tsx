import { useState } from 'react';
import { useNavigate, useLocation } from 'react-router-dom';
import { useAuthStore } from '../store/auth-store';
import { apiClient } from '../core/api-client';

export function LoginPage() {
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [error, setError] = useState<string | null>(null);
  const [loading, setLoading] = useState(false);
  const login = useAuthStore((s) => s.login);
  const navigate = useNavigate();
  const location = useLocation();
  const from = (location.state as { from?: { pathname: string } })?.from?.pathname || '/';

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError(null);
    setLoading(true);

    try {
      const res = await apiClient.post('/v1/auth/login', { email, password });
      if (res.success && res.data) {
        login(res.data.token as string, res.data.user as { id: string; name: string; email: string });
        navigate(from, { replace: true });
      } else {
        setError(res.message || 'فشل تسجيل الدخول');
      }
    } catch (err) {
      setError('حدث خطأ في الاتصال بالخادم');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div dir="rtl" style={{ maxWidth: 400, margin: '100px auto', padding: 24 }}>
      <h1 style={{ textAlign: 'center', marginBottom: 32 }}>بيزا</h1>
      <h2 style={{ textAlign: 'center', marginBottom: 24 }}>تسجيل الدخول</h2>

      {error && (
        <div style={{ color: 'red', marginBottom: 16, padding: 12, background: '#fdd', borderRadius: 8 }}>
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit}>
        <div style={{ marginBottom: 16 }}>
          <label style={{ display: 'block', marginBottom: 4 }}>البريد الإلكتروني</label>
          <input
            type="email"
            value={email}
            onChange={(e) => setEmail(e.target.value)}
            required
            style={{ width: '100%', padding: 8, borderRadius: 4, border: '1px solid #ccc' }}
            dir="rtl"
          />
        </div>

        <div style={{ marginBottom: 24 }}>
          <label style={{ display: 'block', marginBottom: 4 }}>كلمة المرور</label>
          <input
            type="password"
            value={password}
            onChange={(e) => setPassword(e.target.value)}
            required
            style={{ width: '100%', padding: 8, borderRadius: 4, border: '1px solid #ccc' }}
            dir="rtl"
          />
        </div>

        <button
          type="submit"
          disabled={loading}
          style={{
            width: '100%', padding: 12, borderRadius: 4, border: 'none',
            background: loading ? '#999' : '#1A6B4E', color: 'white', fontSize: 16, cursor: 'pointer',
          }}
        >
          {loading ? 'جاري التحميل...' : 'دخول'}
        </button>
      </form>
    </div>
  );
}
