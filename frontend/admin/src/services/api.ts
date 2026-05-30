import axios, { AxiosError } from 'axios';

const api = axios.create({
  baseURL: process.env.REACT_APP_API_URL || '/api/v1/admin',
  headers: { 'Content-Type': 'application/json' },
});

api.interceptors.request.use((config) => {
  const token = localStorage.getItem('admin_token');
  if (token) {
    config.headers.Authorization = `Bearer ${token}`;
  }
  return config;
});

api.interceptors.response.use(
  (response) => response,
  (error: AxiosError) => {
    if (error.response?.status === 401) {
      localStorage.removeItem('admin_token');
      window.location.href = '/admin/login';
    }
    return Promise.reject(error);
  }
);

export interface User {
  id: string;
  phone: string;
  full_name: string | null;
  email: string | null;
  kyc_tier: 'none' | 'basic' | 'verified' | 'premium';
  status: 'active' | 'pending' | 'blocked' | 'closed';
  created_at: string;
  last_login_at: string | null;
  national_id: string | null;
  date_of_birth: string | null;
  address: string | null;
  city: string | null;
  metadata: Record<string, unknown>;
}

export interface Role {
  id: string;
  name: string;
  description: string | null;
  permissions_count: number;
  users_count: number;
  is_system: boolean;
  created_at: string;
  permissions?: Permission[];
}

export interface Permission {
  id: string;
  name: string;
  module: string;
  description: string | null;
  created_at: string;
}

export interface Device {
  id: string;
  device_id: string;
  device_name: string;
  os_version: string;
  is_trusted: boolean;
  last_login_at: string;
  created_at: string;
}

export interface Session {
  id: string;
  ip_address: string;
  user_agent: string;
  is_active: boolean;
  last_activity_at: string;
  created_at: string;
}

export interface AuditLog {
  id: string;
  action: string;
  target_type: string;
  target_id: string;
  metadata: Record<string, unknown>;
  admin_id: string;
  admin_name: string;
  created_at: string;
}

export interface PaginatedResponse<T> {
  data: T[];
  meta: {
    current_page: number;
    last_page: number;
    per_page: number;
    total: number;
  };
}

export interface ApiResponse<T> {
  data: T;
  message?: string;
  message_ar?: string;
}

export const userApi = {
  list: (params?: Record<string, unknown>) =>
    api.get<PaginatedResponse<User>>('/users', { params }).then((r) => r.data),

  show: (id: string) =>
    api.get<ApiResponse<User>>(`/users/${id}`).then((r) => r.data.data),

  create: (data: Partial<User>) =>
    api.post<ApiResponse<User>>('/users', data).then((r) => r.data.data),

  update: (id: string, data: Partial<User>) =>
    api.put<ApiResponse<User>>(`/users/${id}`, data).then((r) => r.data.data),

  suspend: (id: string, reason?: string) =>
    api.post<ApiResponse<User>>(`/users/${id}/suspend`, { reason }).then((r) => r.data.data),

  activate: (id: string) =>
    api.post<ApiResponse<User>>(`/users/${id}/activate`).then((r) => r.data.data),

  getDevices: (id: string) =>
    api.get<ApiResponse<Device[]>>(`/users/${id}/devices`).then((r) => r.data.data),

  toggleDeviceTrust: (userId: string, deviceId: string, trusted: boolean) =>
    api.post<ApiResponse<Device>>(`/users/${userId}/devices/${deviceId}/trust`, { trusted }).then((r) => r.data.data),

  getSessions: (id: string) =>
    api.get<ApiResponse<Session[]>>(`/users/${id}/sessions`).then((r) => r.data.data),

  getRoles: (id: string) =>
    api.get<ApiResponse<Role[]>>(`/users/${id}/roles`).then((r) => r.data.data),

  assignRole: (userId: string, roleId: string) =>
    api.post(`/users/${userId}/roles`, { role_id: roleId }).then((r) => r.data),

  revokeRole: (userId: string, roleId: string) =>
    api.delete(`/users/${userId}/roles/${roleId}`).then((r) => r.data),

  getActivityLog: (id: string, params?: Record<string, unknown>) =>
    api.get<PaginatedResponse<AuditLog>>(`/users/${id}/activity-log`, { params }).then((r) => r.data),

  approveKyc: (id: string, tier: string) =>
    api.post<ApiResponse<User>>(`/users/${id}/kyc/approve`, { tier }).then((r) => r.data.data),

  rejectKyc: (id: string, reason?: string) =>
    api.post<ApiResponse<User>>(`/users/${id}/kyc/reject`, { reason }).then((r) => r.data.data),
};

export const roleApi = {
  list: (params?: Record<string, unknown>) =>
    api.get<PaginatedResponse<Role>>('/roles', { params }).then((r) => r.data),

  show: (id: string) =>
    api.get<ApiResponse<Role>>(`/roles/${id}`).then((r) => r.data.data),

  create: (data: Partial<Role>) =>
    api.post<ApiResponse<Role>>('/roles', data).then((r) => r.data.data),

  update: (id: string, data: Partial<Role>) =>
    api.put<ApiResponse<Role>>(`/roles/${id}`, data).then((r) => r.data.data),

  remove: (id: string) =>
    api.delete(`/roles/${id}`).then((r) => r.data),

  assignPermissions: (id: string, permissionIds: string[]) =>
    api.post(`/roles/${id}/permissions`, { permission_ids: permissionIds }).then((r) => r.data),
};

export const permissionApi = {
  list: (params?: Record<string, unknown>) =>
    api.get<PaginatedResponse<Permission>>('/permissions', { params }).then((r) => r.data),

  show: (id: string) =>
    api.get<ApiResponse<Permission>>(`/permissions/${id}`).then((r) => r.data.data),

  create: (data: Partial<Permission>) =>
    api.post<ApiResponse<Permission>>('/permissions', data).then((r) => r.data.data),

  remove: (id: string) =>
    api.delete(`/permissions/${id}`).then((r) => r.data),
};

export interface Agent {
  id: string;
  full_name: string;
  shop_name: string;
  phone: string;
  governorate: string;
  city: string;
  status: 'pending' | 'approved' | 'suspended' | 'rejected';
  float_balance: number;
  commission_balance: number;
  daily_limit: number;
  latitude: number | null;
  longitude: number | null;
  kyc_status: 'pending' | 'submitted' | 'approved' | 'rejected';
  created_at: string;
}

export interface Transaction {
  id: string;
  type: string;
  amount: number;
  currency: string;
  fee: number;
  status: 'pending' | 'completed' | 'failed' | 'reversed';
  sender_phone: string;
  receiver_phone: string | null;
  reference_type: string;
  reference_id: string;
  description: string | null;
  created_at: string;
}

export interface FraudCase {
  id: string;
  user_id: string;
  user_phone: string;
  transaction_id: string | null;
  risk_score: number;
  rule_name: string;
  status: 'open' | 'investigating' | 'resolved' | 'dismissed';
  decision: 'allow' | 'block' | 'review' | null;
  reviewed_by: string | null;
  notes: string | null;
  created_at: string;
}

export interface ExchangeRate {
  id: string;
  base_currency: string;
  quote_currency: string;
  rate: number;
  source: 'cbs' | 'market' | 'manual';
  is_active: boolean;
  valid_until: string | null;
  created_at: string;
}

export const agentApi = {
  list: (params?: Record<string, unknown>) =>
    api.get<PaginatedResponse<Agent>>('/agents', { params }).then((r) => r.data),
  show: (id: string) =>
    api.get<ApiResponse<Agent>>(`/agents/${id}`).then((r) => r.data.data),
  approve: (id: string) =>
    api.post<ApiResponse<Agent>>(`/agents/${id}/approve`).then((r) => r.data.data),
  suspend: (id: string, reason?: string) =>
    api.post<ApiResponse<Agent>>(`/agents/${id}/suspend`, { reason }).then((r) => r.data.data),
};

export const transactionApi = {
  list: (params?: Record<string, unknown>) =>
    api.get<PaginatedResponse<Transaction>>('/transactions', { params }).then((r) => r.data),
  show: (id: string) =>
    api.get<ApiResponse<Transaction>>(`/transactions/${id}`).then((r) => r.data.data),
  reverse: (id: string, reason: string) =>
    api.post<ApiResponse<Transaction>>(`/transactions/${id}/reverse`, { reason }).then((r) => r.data.data),
};

export const fraudApi = {
  listCases: (params?: Record<string, unknown>) =>
    api.get<PaginatedResponse<FraudCase>>('/fraud/cases', { params }).then((r) => r.data),
  showCase: (id: string) =>
    api.get<ApiResponse<FraudCase>>(`/fraud/cases/${id}`).then((r) => r.data.data),
  reviewCase: (id: string, data: { decision: string; notes?: string }) =>
    api.post(`/fraud/cases/${id}/review`, data).then((r) => r.data),
};

export const fxApi = {
  listRates: (params?: Record<string, unknown>) =>
    api.get<PaginatedResponse<ExchangeRate>>('/fx/rates', { params }).then((r) => r.data),
  createRate: (data: Partial<ExchangeRate>) =>
    api.post<ApiResponse<ExchangeRate>>('/fx/rates', data).then((r) => r.data.data),
};

export default api;
