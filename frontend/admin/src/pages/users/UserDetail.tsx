import React, { useState, useEffect, useCallback } from 'react';
import { useParams } from 'react-router-dom';
import {
  userApi,
  roleApi,
  User,
  Device,
  Session,
  Role,
  AuditLog,
} from '../../services/api';

type TabKey = 'profile' | 'kyc' | 'devices' | 'sessions' | 'roles' | 'activity';

interface UserDetailState {
  user: User | null;
  devices: Device[];
  sessions: Session[];
  availableRoles: Role[];
  userRoles: Role[];
  activityLog: AuditLog[];
  loading: boolean;
  error: string | null;
  activeTab: TabKey;
  activityPage: number;
  activityTotal: number;
}

const TABS: { key: TabKey; label: string }[] = [
  { key: 'profile', label: 'الملف الشخصي' },
  { key: 'kyc', label: 'KYC' },
  { key: 'devices', label: 'الأجهزة' },
  { key: 'sessions', label: 'الجلسات' },
  { key: 'roles', label: 'الأدوار' },
  { key: 'activity', label: 'سجل النشاطات' },
];

const UserDetail: React.FC = () => {
  const { id } = useParams<{ id: string }>();
  const [state, setState] = useState<UserDetailState>({
    user: null,
    devices: [],
    sessions: [],
    availableRoles: [],
    userRoles: [],
    activityLog: [],
    loading: true,
    error: null,
    activeTab: 'profile',
    activityPage: 1,
    activityTotal: 0,
  });

  const setPartial = (partial: Partial<UserDetailState>) =>
    setState((prev) => ({ ...prev, ...partial }));

  const fetchUser = useCallback(async () => {
    if (!id) return;
    setPartial({ loading: true, error: null });
    try {
      const user = await userApi.show(id);
      setPartial({ user, loading: false });
    } catch {
      setPartial({ loading: false, error: 'فشل في تحميل بيانات المستخدم' });
    }
  }, [id]);

  const fetchDevices = useCallback(async () => {
    if (!id) return;
    try {
      const devices = await userApi.getDevices(id);
      setPartial({ devices });
    } catch {
      console.error('Failed to load devices');
    }
  }, [id]);

  const fetchSessions = useCallback(async () => {
    if (!id) return;
    try {
      const sessions = await userApi.getSessions(id);
      setPartial({ sessions });
    } catch {
      console.error('Failed to load sessions');
    }
  }, [id]);

  const fetchRoles = useCallback(async () => {
    if (!id) return;
    try {
      const [userRoles, allRoles] = await Promise.all([
        userApi.getRoles(id),
        roleApi.list({ per_page: 100 }).then((r) => r.data),
      ]);
      setPartial({ userRoles, availableRoles: allRoles });
    } catch {
      console.error('Failed to load roles');
    }
  }, [id]);

  const fetchActivityLog = useCallback(async () => {
    if (!id) return;
    try {
      const result = await userApi.getActivityLog(id, {
        page: state.activityPage,
        per_page: 20,
      });
      setPartial({
        activityLog: result.data,
        activityTotal: result.meta.total,
      });
    } catch {
      console.error('Failed to load activity log');
    }
  }, [id, state.activityPage]);

  useEffect(() => {
    fetchUser();
  }, [fetchUser]);

  useEffect(() => {
    switch (state.activeTab) {
      case 'devices':
        fetchDevices();
        break;
      case 'sessions':
        fetchSessions();
        break;
      case 'roles':
        fetchRoles();
        break;
      case 'activity':
        fetchActivityLog();
        break;
    }
  }, [state.activeTab, fetchDevices, fetchSessions, fetchRoles, fetchActivityLog]);

  const handleTabChange = (tab: TabKey) => {
    setPartial({ activeTab: tab, activityPage: 1 });
  };

  const handleToggleDeviceTrust = async (deviceId: string, trusted: boolean) => {
    if (!id) return;
    try {
      await userApi.toggleDeviceTrust(id, deviceId, trusted);
      fetchDevices();
    } catch {
      console.error('Failed to toggle device trust');
    }
  };

  const handleAssignRole = async (roleId: string) => {
    if (!id) return;
    try {
      await userApi.assignRole(id, roleId);
      fetchRoles();
    } catch {
      console.error('Failed to assign role');
    }
  };

  const handleRevokeRole = async (roleId: string) => {
    if (!id) return;
    try {
      await userApi.revokeRole(id, roleId);
      fetchRoles();
    } catch {
      console.error('Failed to revoke role');
    }
  };

  const handleKycApprove = async (tier: string) => {
    if (!id) return;
    try {
      await userApi.approveKyc(id, tier);
      fetchUser();
    } catch {
      console.error('Failed to approve KYC');
    }
  };

  const handleKycReject = async () => {
    if (!id) return;
    try {
      await userApi.rejectKyc(id);
      fetchUser();
    } catch {
      console.error('Failed to reject KYC');
    }
  };

  if (state.loading) {
    return (
      <div style={styles.centerState}>
        <div style={styles.spinner} />
        <p>جاري التحميل...</p>
      </div>
    );
  }

  if (state.error) {
    return (
      <div style={styles.centerState}>
        <p style={{ color: '#C62828' }}>{state.error}</p>
        <button onClick={fetchUser} style={styles.retryBtn}>
          إعادة المحاولة
        </button>
      </div>
    );
  }

  if (!state.user) {
    return (
      <div style={styles.centerState}>
        <p>المستخدم غير موجود</p>
      </div>
    );
  }

  const { user } = state;
  const unassignedRoles = state.availableRoles.filter(
    (r) => !state.userRoles.some((ur) => ur.id === r.id)
  );

  return (
    <div style={styles.container}>
      <div style={styles.header}>
        <div>
          <h1 style={styles.title}>{user.full_name || 'مستخدم'}</h1>
          <p style={styles.subtitle}>{user.phone}</p>
        </div>
        <div style={styles.headerBadges}>
          <span
            style={{
              ...styles.badge,
              color: user.status === 'active' ? '#1B5E20' : '#C62828',
              backgroundColor: user.status === 'active' ? '#E8F5E9' : '#FFEBEE',
            }}
          >
            {user.status === 'active' ? 'نشط' : 'محظور'}
          </span>
          <span
            style={{
              ...styles.badge,
              color: '#1565C0',
              backgroundColor: '#E3F2FD',
            }}
          >
            {user.kyc_tier}
          </span>
        </div>
      </div>

      <div style={styles.tabsRow}>
        {TABS.map((tab) => (
          <button
            key={tab.key}
            onClick={() => handleTabChange(tab.key)}
            style={{
              ...styles.tab,
              color: state.activeTab === tab.key ? '#2E7D32' : '#757575',
              borderBottom:
                state.activeTab === tab.key
                  ? '3px solid #2E7D32'
                  : '3px solid transparent',
              fontWeight: state.activeTab === tab.key ? 700 : 500,
            }}
          >
            {tab.label}
          </button>
        ))}
      </div>

      <div style={styles.tabContent}>
        {state.activeTab === 'profile' && (
          <ProfileTab user={user} />
        )}
        {state.activeTab === 'kyc' && (
          <KycTab
            user={user}
            onApprove={handleKycApprove}
            onReject={handleKycReject}
          />
        )}
        {state.activeTab === 'devices' && (
          <DevicesTab
            devices={state.devices}
            onToggleTrust={handleToggleDeviceTrust}
          />
        )}
        {state.activeTab === 'sessions' && (
          <SessionsTab sessions={state.sessions} />
        )}
        {state.activeTab === 'roles' && (
          <RolesTab
            userRoles={state.userRoles}
            availableRoles={unassignedRoles}
            onAssign={handleAssignRole}
            onRevoke={handleRevokeRole}
          />
        )}
        {state.activeTab === 'activity' && (
          <ActivityLogTab
            logs={state.activityLog}
            page={state.activityPage}
            total={state.activityTotal}
            onPageChange={(p) => setPartial({ activityPage: p })}
          />
        )}
      </div>
    </div>
  );
};

const ProfileTab: React.FC<{ user: User }> = ({ user }) => {
  const fields = [
    { label: 'رقم الهاتف', value: user.phone },
    { label: 'الاسم الكامل', value: user.full_name || '—' },
    { label: 'البريد الإلكتروني', value: user.email || '—' },
    { label: 'رقم الهوية', value: user.national_id || '—' },
    { label: 'تاريخ الميلاد', value: user.date_of_birth || '—' },
    { label: 'العنوان', value: user.address || '—' },
    { label: 'المدينة', value: user.city || '—' },
    { label: 'تاريخ التسجيل', value: new Date(user.created_at).toLocaleDateString('ar-SA') },
    { label: 'آخر دخول', value: user.last_login_at ? new Date(user.last_login_at).toLocaleDateString('ar-SA') : '—' },
  ];

  return (
    <div style={styles.profileGrid}>
      {fields.map((f) => (
        <div key={f.label} style={styles.fieldRow}>
          <span style={styles.fieldLabel}>{f.label}</span>
          <span style={styles.fieldValue}>{f.value}</span>
        </div>
      ))}
    </div>
  );
};

const KycTab: React.FC<{
  user: User;
  onApprove: (tier: string) => void;
  onReject: () => void;
}> = ({ user, onApprove, onReject }) => {
  const [selectedTier, setSelectedTier] = useState('verified');

  return (
    <div>
      <div style={styles.kycStatus}>
        <h3>حالة التوثيق: {user.kyc_tier}</h3>
      </div>
      <div style={styles.kycImages}>
        <div style={styles.kycImageBox}>
          <p>وجه الأمامي للبطاقة</p>
          <div style={styles.kycImagePlaceholder} />
        </div>
        <div style={styles.kycImageBox}>
          <p>الوجه الخلفي للبطاقة</p>
          <div style={styles.kycImagePlaceholder} />
        </div>
        <div style={styles.kycImageBox}>
          <p>صورة شخصية (سيلفي)</p>
          <div style={styles.kycImagePlaceholder} />
        </div>
      </div>
      <div style={styles.kycActions}>
        <select
          value={selectedTier}
          onChange={(e) => setSelectedTier(e.target.value)}
          style={styles.select}
        >
          <option value="basic">أساسي</option>
          <option value="verified">موثق</option>
          <option value="premium">ممتاز</option>
        </select>
        <button
          onClick={() => onApprove(selectedTier)}
          style={{ ...styles.actionBtn, backgroundColor: '#E8F5E9', color: '#2E7D32' }}
        >
          اعتماد
        </button>
        <button
          onClick={onReject}
          style={{ ...styles.actionBtn, backgroundColor: '#FFEBEE', color: '#C62828' }}
        >
          رفض
        </button>
      </div>
    </div>
  );
};

const DevicesTab: React.FC<{
  devices: Device[];
  onToggleTrust: (deviceId: string, trusted: boolean) => void;
}> = ({ devices, onToggleTrust }) => {
  if (devices.length === 0) {
    return <div style={styles.emptyTab}>لا توجد أجهزة مسجلة</div>;
  }
  return (
    <table style={styles.innerTable}>
      <thead>
        <tr>
          <th style={styles.th}>اسم الجهاز</th>
          <th style={styles.th}>نظام التشغيل</th>
          <th style={styles.th}>آخر دخول</th>
          <th style={styles.th}>موثوق</th>
        </tr>
      </thead>
      <tbody>
        {devices.map((device) => (
          <tr key={device.id} style={styles.tr}>
            <td style={styles.td}>{device.device_name}</td>
            <td style={styles.td}>{device.os_version}</td>
            <td style={styles.td}>{new Date(device.last_login_at).toLocaleDateString('ar-SA')}</td>
            <td style={styles.td}>
              <button
                onClick={() => onToggleTrust(device.id, !device.is_trusted)}
                style={{
                  ...styles.toggleBtn,
                  backgroundColor: device.is_trusted ? '#E8F5E9' : '#FFEBEE',
                  color: device.is_trusted ? '#2E7D32' : '#C62828',
                }}
              >
                {device.is_trusted ? 'موثوق' : 'غير موثوق'}
              </button>
            </td>
          </tr>
        ))}
      </tbody>
    </table>
  );
};

const SessionsTab: React.FC<{ sessions: Session[] }> = ({ sessions }) => {
  if (sessions.length === 0) {
    return <div style={styles.emptyTab}>لا توجد جلسات نشطة</div>;
  }
  return (
    <table style={styles.innerTable}>
      <thead>
        <tr>
          <th style={styles.th}>عنوان IP</th>
          <th style={styles.th}>المتصفح</th>
          <th style={styles.th}>الحالة</th>
          <th style={styles.th}>آخر نشاط</th>
          <th style={styles.th}>تاريخ الإنشاء</th>
        </tr>
      </thead>
      <tbody>
        {sessions.map((session) => (
          <tr key={session.id} style={styles.tr}>
            <td style={styles.td}>{session.ip_address}</td>
            <td style={styles.td}>{session.user_agent.substring(0, 50)}...</td>
            <td style={styles.td}>
              <span
                style={{
                  ...styles.badge,
                  color: session.is_active ? '#2E7D32' : '#9E9E9E',
                  backgroundColor: session.is_active ? '#E8F5E9' : '#F5F5F5',
                }}
              >
                {session.is_active ? 'نشطة' : 'منتهية'}
              </span>
            </td>
            <td style={styles.td}>{new Date(session.last_activity_at).toLocaleDateString('ar-SA')}</td>
            <td style={styles.td}>{new Date(session.created_at).toLocaleDateString('ar-SA')}</td>
          </tr>
        ))}
      </tbody>
    </table>
  );
};

const RolesTab: React.FC<{
  userRoles: Role[];
  availableRoles: Role[];
  onAssign: (roleId: string) => void;
  onRevoke: (roleId: string) => void;
}> = ({ userRoles, availableRoles, onAssign, onRevoke }) => {
  const [selectedRole, setSelectedRole] = useState('');

  return (
    <div>
      <div style={styles.assignSection}>
        <select
          value={selectedRole}
          onChange={(e) => setSelectedRole(e.target.value)}
          style={{ ...styles.select, minWidth: '250px' }}
        >
          <option value="">اختر دوراً...</option>
          {availableRoles.map((role) => (
            <option key={role.id} value={role.id}>
              {role.name}
            </option>
          ))}
        </select>
        <button
          disabled={!selectedRole}
          onClick={() => {
            if (selectedRole) {
              onAssign(selectedRole);
              setSelectedRole('');
            }
          }}
          style={{
            ...styles.actionBtn,
            backgroundColor: '#E8F5E9',
            color: '#2E7D32',
            opacity: !selectedRole ? 0.5 : 1,
          }}
        >
          إضافة دور
        </button>
      </div>

      {userRoles.length === 0 ? (
        <div style={styles.emptyTab}>لا توجد أدوار مخصصة</div>
      ) : (
        <table style={styles.innerTable}>
          <thead>
            <tr>
              <th style={styles.th}>اسم الدور</th>
              <th style={styles.th}>الوصف</th>
              <th style={styles.th}>عدد الصلاحيات</th>
              <th style={styles.th}>الإجراءات</th>
            </tr>
          </thead>
          <tbody>
            {userRoles.map((role) => (
              <tr key={role.id} style={styles.tr}>
                <td style={styles.td}>{role.name}</td>
                <td style={styles.td}>{role.description || '—'}</td>
                <td style={styles.td}>{role.permissions_count}</td>
                <td style={styles.td}>
                  <button
                    onClick={() => onRevoke(role.id)}
                    style={{
                      ...styles.actionBtn,
                      backgroundColor: '#FFEBEE',
                      color: '#C62828',
                    }}
                  >
                    إزالة
                  </button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      )}
    </div>
  );
};

const ActivityLogTab: React.FC<{
  logs: AuditLog[];
  page: number;
  total: number;
  onPageChange: (page: number) => void;
}> = ({ logs, page, total }) => {
  if (logs.length === 0) {
    return <div style={styles.emptyTab}>لا توجد نشاطات مسجلة</div>;
  }
  return (
    <div>
      <table style={styles.innerTable}>
        <thead>
          <tr>
            <th style={styles.th}>الإجراء</th>
            <th style={styles.th}>النوع</th>
            <th style={styles.th}>المسؤول</th>
            <th style={styles.th}>التاريخ</th>
            <th style={styles.th}>التفاصيل</th>
          </tr>
        </thead>
        <tbody>
          {logs.map((log) => (
            <tr key={log.id} style={styles.tr}>
              <td style={styles.td}>{log.action}</td>
              <td style={styles.td}>{log.target_type}</td>
              <td style={styles.td}>{log.admin_name}</td>
              <td style={styles.td}>{new Date(log.created_at).toLocaleDateString('ar-SA')}</td>
              <td style={styles.td}>
                {log.metadata ? JSON.stringify(log.metadata).substring(0, 50) : '—'}
              </td>
            </tr>
          ))}
        </tbody>
      </table>
      <div style={styles.pagination}>
        <span style={styles.pageInfo}>
          صفحة {page} من {Math.ceil(total / 20)}
        </span>
      </div>
    </div>
  );
};

const styles: Record<string, React.CSSProperties> = {
  container: {
    direction: 'rtl',
    padding: '24px',
    fontFamily: "'Noto Naskh Arabic', 'Tajawal', sans-serif",
  },
  header: {
    display: 'flex',
    justifyContent: 'space-between',
    alignItems: 'center',
    marginBottom: '20px',
  },
  title: {
    fontSize: '22px',
    fontWeight: 700,
    color: '#212121',
    margin: 0,
  },
  subtitle: {
    fontSize: '14px',
    color: '#757575',
    margin: '4px 0 0 0',
  },
  headerBadges: {
    display: 'flex',
    gap: '8px',
  },
  badge: {
    display: 'inline-block',
    padding: '4px 12px',
    borderRadius: '20px',
    fontSize: '12px',
    fontWeight: 600,
  },
  tabsRow: {
    display: 'flex',
    gap: '4px',
    borderBottom: '1px solid #E0E0E0',
    marginBottom: '24px',
  },
  tab: {
    padding: '12px 20px',
    border: 'none',
    backgroundColor: 'transparent',
    cursor: 'pointer',
    fontSize: '14px',
    fontFamily: "'Noto Naskh Arabic', 'Tajawal', sans-serif",
    transition: 'all 0.2s',
  },
  tabContent: {
    minHeight: '300px',
  },
  centerState: {
    display: 'flex',
    flexDirection: 'column',
    alignItems: 'center',
    justifyContent: 'center',
    padding: '80px 0',
    color: '#757575',
    fontFamily: "'Noto Naskh Arabic', 'Tajawal', sans-serif",
  },
  spinner: {
    width: '40px',
    height: '40px',
    border: '4px solid #E0E0E0',
    borderTop: '4px solid #2E7D32',
    borderRadius: '50%',
    animation: 'spin 1s linear infinite',
    marginBottom: '16px',
  },
  retryBtn: {
    padding: '10px 24px',
    borderRadius: '8px',
    border: 'none',
    backgroundColor: '#2E7D32',
    color: '#fff',
    fontSize: '14px',
    cursor: 'pointer',
  },
  profileGrid: {
    display: 'grid',
    gridTemplateColumns: 'repeat(auto-fill, minmax(300px, 1fr))',
    gap: '16px',
  },
  fieldRow: {
    display: 'flex',
    flexDirection: 'column',
    padding: '16px',
    backgroundColor: '#F9F9F9',
    borderRadius: '8px',
  },
  fieldLabel: {
    fontSize: '12px',
    color: '#9E9E9E',
    marginBottom: '4px',
  },
  fieldValue: {
    fontSize: '16px',
    color: '#212121',
    fontWeight: 500,
  },
  kycStatus: {
    marginBottom: '20px',
  },
  kycImages: {
    display: 'flex',
    gap: '20px',
    marginBottom: '24px',
    flexWrap: 'wrap',
  },
  kycImageBox: {
    flex: 1,
    minWidth: '200px',
    textAlign: 'center',
  },
  kycImagePlaceholder: {
    width: '100%',
    height: '180px',
    backgroundColor: '#F5F5F5',
    borderRadius: '8px',
    border: '2px dashed #E0E0E0',
    marginTop: '8px',
  },
  kycActions: {
    display: 'flex',
    gap: '12px',
    alignItems: 'center',
  },
  select: {
    padding: '10px 16px',
    borderRadius: '8px',
    border: '1px solid #E0E0E0',
    fontSize: '14px',
    backgroundColor: '#fff',
    outline: 'none',
  },
  actionBtn: {
    padding: '10px 20px',
    borderRadius: '8px',
    border: 'none',
    cursor: 'pointer',
    fontSize: '14px',
    fontWeight: 600,
  },
  assignSection: {
    display: 'flex',
    gap: '12px',
    alignItems: 'center',
    marginBottom: '20px',
  },
  innerTable: {
    width: '100%',
    borderCollapse: 'collapse',
    fontSize: '14px',
    backgroundColor: '#fff',
    borderRadius: '8px',
    overflow: 'hidden',
    border: '1px solid #E0E0E0',
  },
  th: {
    padding: '12px 16px',
    textAlign: 'right',
    backgroundColor: '#F5F5F5',
    color: '#424242',
    fontWeight: 600,
    borderBottom: '2px solid #E0E0E0',
  },
  tr: {
    borderBottom: '1px solid #F0F0F0',
  },
  td: {
    padding: '12px 16px',
    textAlign: 'right',
    color: '#212121',
  },
  toggleBtn: {
    padding: '6px 14px',
    borderRadius: '6px',
    border: 'none',
    fontSize: '13px',
    fontWeight: 500,
    cursor: 'pointer',
  },
  emptyTab: {
    textAlign: 'center',
    padding: '60px 0',
    color: '#9E9E9E',
    fontSize: '15px',
  },
  pagination: {
    display: 'flex',
    justifyContent: 'center',
    marginTop: '20px',
  },
  pageInfo: {
    fontSize: '14px',
    color: '#616161',
  },
};

export default UserDetail;
