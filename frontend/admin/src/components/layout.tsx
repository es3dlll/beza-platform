import { useState } from 'react';
import { Outlet, useNavigate, useLocation } from 'react-router-dom';
import { useAuthStore } from '../store/auth-store';

export function DashboardLayout() {
  const [sidebarOpen, setSidebarOpen] = useState(true);
  const { user, logout } = useAuthStore();
  const navigate = useNavigate();
  const location = useLocation();

  const handleLogout = () => {
    logout();
    navigate('/login');
  };

  return (
    <div dir="rtl" style={{ display: 'flex', minHeight: '100vh' }}>
      <aside
        style={{
          width: sidebarOpen ? 250 : 60,
          background: '#1a1a2e',
          color: 'white',
          transition: 'width 0.3s',
          padding: sidebarOpen ? 16 : 8,
          display: 'flex',
          flexDirection: 'column',
        }}
      >
        <button
          onClick={() => setSidebarOpen(!sidebarOpen)}
          style={{
            background: 'none', border: 'none', color: 'white', fontSize: 20,
            cursor: 'pointer', marginBottom: 24, textAlign: sidebarOpen ? 'left' : 'center',
          }}
        >
          {sidebarOpen ? '✕' : '☰'}
        </button>

        {sidebarOpen && (
          <>
            <h2 style={{ margin: '0 0 24 0', fontSize: 22 }}>بيزا</h2>

            <nav style={{ flex: 1 }}>
              <div
                onClick={() => navigate('/')}
                style={{
                  marginBottom: 8, padding: '8px 12px', borderRadius: 6, cursor: 'pointer',
                  background: location.pathname === '/' ? '#16213e' : 'transparent',
                }}
              >
                لوحة التحكم
              </div>
              <div
                onClick={() => navigate('/transfer')}
                style={{
                  marginBottom: 8, padding: '8px 12px', borderRadius: 6, cursor: 'pointer',
                  background: location.pathname === '/transfer' ? '#16213e' : 'transparent',
                }}
              >
                تحويل
              </div>
              <div
                onClick={() => navigate('/audit-log')}
                style={{
                  marginBottom: 8, padding: '8px 12px', borderRadius: 6, cursor: 'pointer',
                  background: location.pathname === '/audit-log' ? '#16213e' : 'transparent',
                }}
              >
                سجل التدقيق
              </div>
            </nav>
          </>
        )}
      </aside>

      <div style={{ flex: 1, display: 'flex', flexDirection: 'column' }}>
        <header
          style={{
            padding: '12px 24px',
            background: 'white',
            borderBottom: '1px solid #ddd',
            display: 'flex',
            justifyContent: 'space-between',
            alignItems: 'center',
          }}
        >
          <span>{user?.name || 'مستخدم'}</span>
          <button
            onClick={handleLogout}
            style={{
              padding: '8px 16px', borderRadius: 4, border: '1px solid #ccc',
              cursor: 'pointer', background: 'white',
            }}
          >
            تسجيل خروج
          </button>
        </header>

        <main style={{ flex: 1, background: '#f9f9f9' }}>
          <Outlet />
        </main>
      </div>
    </div>
  );
}
