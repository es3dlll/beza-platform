export const ENDPOINTS = {
  ADMIN_LOGIN: "/admin/login",
  ADMIN_ME: "/admin/me",
  ADMIN_LOGOUT: "/admin/logout",
  ADMIN_WAP_SUMMARY: "/admin/wap/summary",
  ADMIN_WAP_DEVICES: "/admin/wap/devices",
  ADMIN_WAP_QUEUE: "/admin/wap/queue",
  ADMIN_WAP_ROUTES: "/admin/wap/routes",
  ADMIN_WAP_ROUTE_UPDATE: (id: number) => `/admin/wap/routes/${id}`,
} as const;
