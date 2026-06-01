import { apiPost, apiGet } from "./client";
import { ENDPOINTS } from "./endpoints";

export interface User {
  id: number;
  name: string;
  email: string;
  phone: string;
  role: "user" | "merchant" | "agent" | "admin";
}

export interface Wallet {
  id: number;
  currency: string;
  balance: number;
  status: string;
}

export interface LoginResponse {
  user: User;
  wallets: Wallet[];
}

export async function login(
  email: string,
  password: string
): Promise<LoginResponse> {
  const res = await apiPost<LoginResponse>(ENDPOINTS.AUTH_LOGIN, {
    email,
    password,
  });
  if (!res.success) throw new Error(res.error?.message || "فشل تسجيل الدخول");
  return res.data;
}

export async function logout(): Promise<void> {
  await apiPost(ENDPOINTS.AUTH_LOGOUT, {});
}

export async function getMe(): Promise<LoginResponse> {
  const res = await apiGet<LoginResponse>(ENDPOINTS.AUTH_ME);
  if (!res.success) throw new Error(res.error?.message || "غير مصادق");
  return res.data;
}
