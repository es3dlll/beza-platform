import { apiPost, apiGet } from "./client";
import { ENDPOINTS } from "./endpoints";

export interface AdminUser {
  id: number;
  name: string;
  email: string;
  role: string;
}

export interface LoginResponse {
  user: AdminUser;
  permissions: string[];
}

export async function loginAdmin(
  email: string,
  password: string
): Promise<LoginResponse> {
  const res = await apiPost<LoginResponse>(ENDPOINTS.ADMIN_LOGIN, { email, password });
  if (!res.success) throw new Error(res.error?.message || "فشل تسجيل الدخول");
  return res.data;
}

export async function getMe(): Promise<LoginResponse> {
  const res = await apiGet<LoginResponse>(ENDPOINTS.ADMIN_ME);
  if (!res.success) throw new Error(res.error?.message || "غير مصادق");
  return res.data;
}

export async function logoutAdmin(): Promise<void> {
  await apiPost(ENDPOINTS.ADMIN_LOGOUT);
}
