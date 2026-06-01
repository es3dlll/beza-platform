export const ENDPOINTS = {
  AUTH_LOGIN: "/api/v1/wap/auth/login",
  AUTH_LOGOUT: "/api/v1/wap/auth/logout",
  AUTH_REFRESH: "/api/v1/wap/auth/refresh",
  AUTH_ME: "/api/v1/wap/auth/me",
  WALLET_BALANCE: "/api/v1/wap/wallet/balance",
  WALLET_TRANSFER: "/api/v1/wap/wallet/transfer",
  MERCHANT_SUMMARY: "/api/v1/wap/merchant/summary",
  MERCHANT_QR: "/api/v1/wap/merchant/qr",
  MERCHANT_SETTLEMENTS: "/api/v1/wap/merchant/settlements",
  AGENT_LIMITS: "/api/v1/wap/agent/limits",
  AGENT_COMMISSIONS: "/api/v1/wap/agent/commissions",
  AGENT_PENDING: "/api/v1/wap/agent/pending",
} as const;
