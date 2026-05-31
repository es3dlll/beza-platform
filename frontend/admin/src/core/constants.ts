export const API_BASE_URL = import.meta.env.VITE_API_BASE_URL ?? '/api';

export const ApiRoutes = {
  health: '/v1/core/health',
  register: '/v1/auth/register',
  login: '/v1/auth/login',
  walletBalance: '/v1/wallet/balance',
  walletTransfer: '/v1/wallet/transfer',
} as const;

export const AppConstants = {
  appName: 'بيزا',
  appNameEn: 'Beza',
  currencySymbol: 'ل.س',
  filsPerSYP: 1000,
} as const;
