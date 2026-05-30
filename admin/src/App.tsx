import React from 'react';
import { BrowserRouter, Routes, Route, Navigate, Outlet } from 'react-router-dom';
import UserList from './pages/users/UserList';
import UserDetail from './pages/users/UserDetail';
import RoleList from './pages/roles/RoleList';
import RoleForm from './pages/roles/RoleForm';
import PermissionList from './pages/permissions/PermissionList';

const LoginPage: React.FC = () => (
  <div
    style={{
      direction: 'rtl',
      display: 'flex',
      alignItems: 'center',
      justifyContent: 'center',
      minHeight: '100vh',
      backgroundColor: '#F5F5F5',
      fontFamily: "'Noto Naskh Arabic', 'Tajawal', sans-serif",
    }}
  >
    <div
      style={{
        backgroundColor: '#fff',
        borderRadius: '16px',
        padding: '40px',
        width: '400px',
        boxShadow: '0 4px 24px rgba(0,0,0,0.08)',
      }}
    >
      <h1
        style={{
          textAlign: 'center',
          color: '#1B5E20',
          fontSize: '32px',
          fontWeight: 900,
          marginBottom: '8px',
        }}
      >
        بزة
      </h1>
      <p
        style={{
          textAlign: 'center',
          color: '#757575',
          marginBottom: '32px',
          fontSize: '14px',
        }}
      >
        لوحة التحكم الإدارية
      </p>
      <p
        style={{
          textAlign: 'center',
          color: '#9E9E9E',
          fontSize: '13px',
        }}
      >
        يرجى تسجيل الدخول للمتابعة
      </p>
    </div>
  </div>
);

const Dashboard: React.FC = () => (
  <div
    style={{
      direction: 'rtl',
      padding: '24px',
      fontFamily: "'Noto Naskh Arabic', 'Tajawal', sans-serif",
    }}
  >
    <h1 style={{ fontSize: '24px', fontWeight: 700, color: '#1B5E20' }}>
      لوحة التحكم
    </h1>
    <p style={{ color: '#757575' }}>مرحباً بك في لوحة تحكم بزة</p>
  </div>
);

const AdminLayout: React.FC = () => {
  const [sidebarOpen, setSidebarOpen] = React.useState(true);

  const navItems = [
    { path: '/admin', label: 'لوحة التحكم', icon: '📊' },
    { path: '/admin/users', label: 'المستخدمين', icon: '👥' },
    { path: '/admin/roles', label: 'الأدوار', icon: '🔐' },
    { path: '/admin/permissions', label: 'الصلاحيات', icon: '🛡️' },
  ];

  return (
    <div
      style={{
        display: 'flex',
        minHeight: '100vh',
        backgroundColor: '#F5F5F5',
        fontFamily: "'Noto Naskh Arabic', 'Tajawal', sans-serif",
      }}
    >
      <aside
        style={{
          width: sidebarOpen ? '260px' : '0px',
          overflow: 'hidden',
          backgroundColor: '#1B5E20',
          color: '#fff',
          transition: 'width 0.3s ease',
          display: 'flex',
          flexDirection: 'column',
        }}
      >
        <div
          style={{
            padding: '24px 20px',
            borderBottom: '1px solid rgba(255,255,255,0.1)',
            textAlign: 'center',
          }}
        >
          <h2
            style={{
              margin: 0,
              fontSize: '28px',
              fontWeight: 900,
              letterSpacing: '2px',
            }}
          >
            بزة
          </h2>
          <p style={{ margin: '4px 0 0', fontSize: '11px', opacity: 0.7 }}>
            لوحة التحكم
          </p>
        </div>
        <nav style={{ flex: 1, padding: '12px 0' }}>
          {navItems.map((item) => (
            <a
              key={item.path}
              href={item.path}
              style={{
                display: 'flex',
                alignItems: 'center',
                gap: '12px',
                padding: '14px 20px',
                color: 'rgba(255,255,255,0.8)',
                textDecoration: 'none',
                fontSize: '15px',
                fontWeight: 500,
                transition: 'all 0.2s',
                borderRight: '3px solid transparent',
              }}
              onMouseEnter={(e) => {
                e.currentTarget.style.backgroundColor = 'rgba(255,255,255,0.1)';
                e.currentTarget.style.color = '#fff';
              }}
              onMouseLeave={(e) => {
                e.currentTarget.style.backgroundColor = 'transparent';
                e.currentTarget.style.color = 'rgba(255,255,255,0.8)';
              }}
            >
              <span>{item.icon}</span>
              <span>{item.label}</span>
            </a>
          ))}
        </nav>
        <div style={{ padding: '16px 20px', borderTop: '1px solid rgba(255,255,255,0.1)' }}>
          <button
            onClick={() => {
              localStorage.removeItem('admin_token');
              window.location.href = '/admin/login';
            }}
            style={{
              width: '100%',
              padding: '10px',
              borderRadius: '8px',
              border: '1px solid rgba(255,255,255,0.2)',
              backgroundColor: 'transparent',
              color: '#fff',
              fontSize: '14px',
              cursor: 'pointer',
              fontFamily: 'inherit',
            }}
          >
            تسجيل الخروج
          </button>
        </div>
      </aside>

      <div style={{ flex: 1, display: 'flex', flexDirection: 'column' }}>
        <header
          style={{
            backgroundColor: '#fff',
            padding: '12px 24px',
            borderBottom: '1px solid #E0E0E0',
            display: 'flex',
            alignItems: 'center',
            gap: '16px',
          }}
        >
          <button
            onClick={() => setSidebarOpen(!sidebarOpen)}
            style={{
              background: 'none',
              border: 'none',
              fontSize: '20px',
              cursor: 'pointer',
              color: '#616161',
              padding: '4px',
            }}
          >
            {sidebarOpen ? '☰' : '☰'}
          </button>
          <div style={{ flex: 1 }} />
        </header>
        <main style={{ flex: 1, padding: '0' }}>
          <Outlet />
        </main>
      </div>
    </div>
  );
};

const App: React.FC = () => {
  return (
    <BrowserRouter>
      <Routes>
        <Route path="/admin/login" element={<LoginPage />} />
        <Route path="/admin" element={<AdminLayout />}>
          <Route index element={<Dashboard />} />
          <Route path="users" element={<UserList />} />
          <Route path="users/:id" element={<UserDetail />} />
          <Route path="roles" element={<RoleList />} />
          <Route path="roles/new" element={<RoleForm />} />
          <Route path="roles/:id/edit" element={<RoleForm />} />
          <Route path="permissions" element={<PermissionList />} />
        </Route>
        <Route path="*" element={<Navigate to="/admin" replace />} />
      </Routes>
    </BrowserRouter>
  );
};

export default App;
