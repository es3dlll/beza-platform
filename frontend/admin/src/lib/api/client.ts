const API_URL = process.env.NEXT_PUBLIC_API_URL || "http://localhost:8000";

export interface ApiResponse<T = unknown> {
  success: boolean;
  data: T;
  error?: { code: string; message: string };
}

export async function apiGet<T>(endpoint: string): Promise<ApiResponse<T>> {
  const res = await fetch(`${API_URL}/api${endpoint}`, {
    credentials: "include",
    headers: { Accept: "application/json" },
  });
  return res.json();
}

export async function apiPost<T>(
  endpoint: string,
  body?: Record<string, unknown>
): Promise<ApiResponse<T>> {
  const res = await fetch(`${API_URL}/api${endpoint}`, {
    method: "POST",
    credentials: "include",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: body ? JSON.stringify(body) : undefined,
  });
  return res.json();
}

export async function apiPut<T>(
  endpoint: string,
  body: Record<string, unknown>
): Promise<ApiResponse<T>> {
  const res = await fetch(`${API_URL}/api${endpoint}`, {
    method: "PUT",
    credentials: "include",
    headers: { "Content-Type": "application/json", Accept: "application/json" },
    body: JSON.stringify(body),
  });
  return res.json();
}
