class ApiEndpoints {
  static const String _v = '';

  // Auth
  static const String authRegister = '$_v/auth/register';
  static const String authOtpRequest = '$_v/auth/otp/request';
  static const String authOtpVerify = '$_v/auth/otp/verify';
  static const String authPinCreate = '$_v/auth/pin/create';
  static const String authPinChange = '$_v/auth/pin/change';
  static const String authPinVerify = '$_v/auth/pin/verify';
  static const String authLogin = '$_v/auth/login';
  static const String authLogout = '$_v/auth/logout';
  static const String authRefresh = '$_v/auth/refresh';

  // Identity
  static const String identityProfile = '$_v/identity/profile';
  static const String identityCheckPhone = '$_v/identity/check-phone';

  // Wallet
  static const String wallets = '$_v/wallets';
  static String wallet(String id) => '$_v/wallets/$id';
  static String walletDeposit(String id) => '$_v/wallets/$id/deposit';
  static String walletWithdraw(String id) => '$_v/wallets/$id/withdraw';
  static String walletTransfer(String id) => '$_v/wallets/$id/transfer';
  static String walletTransactions(String id) => '$_v/wallets/$id/transactions';

  // Cards
  static const String cards = '$_v/cards';
  static String card(String id) => '$_v/cards/$id';
  static String cardActivate(String id) => '$_v/cards/$id/activate';
  static String cardSuspend(String id) => '$_v/cards/$id/suspend';
  static String cardCancel(String id) => '$_v/cards/$id/cancel';
  static String cardLimits(String id) => '$_v/cards/$id/limits';
  static String cardSettings(String id) => '$_v/cards/$id/settings';
  static String cardAuthorize(String id) => '$_v/cards/$id/authorize';
  static String cardTransactions(String id) => '$_v/cards/$id/transactions';
  static String cardMerchantBlocks(String id) => '$_v/cards/$id/merchant-blocks';

  // Bills
  static const String billsProviders = '$_v/bills/providers';
  static const String billsInquiry = '$_v/bills/inquiry';
  static const String billsPay = '$_v/bills/pay';
  static const String billsHistory = '$_v/bills/history';
  static String bill(String id) => '$_v/bills/$id';
  static String billRefund(String id) => '$_v/bills/$id/refund';

  // FX
  static const String fxRates = '$_v/fx/rates';
  static String fxRateHistory(String base, String quote) => '$_v/fx/rates/$base/$quote/history';
  static const String fxQuotes = '$_v/fx/quotes';
  static const String fxConversions = '$_v/fx/conversions';
  static String fxConversion(String id) => '$_v/fx/conversions/show/$id';

  // Savings
  static const String savingsGoals = '$_v/savings/goals';
  static String savingsGoal(String id) => '$_v/savings/goals/$id';
  static String savingsContribute(String id) => '$_v/savings/goals/$id/contribute';
  static String savingsWithdraw(String id) => '$_v/savings/goals/$id/withdraw';
  static String savingsTransactions(String id) => '$_v/savings/goals/$id/transactions';

  // Remittance
  static const String remittanceCorridors = '$_v/remittance/corridors';
  static const String remittanceBeneficiaries = '$_v/remittance/beneficiaries';
  static const String remittanceOrders = '$_v/remittance/orders';
  static String remittanceOrder(String id) => '$_v/remittance/orders/$id';
  static String remittanceScreen(String id) => '$_v/remittance/orders/$id/screen';
  static String remittanceQuote(String id) => '$_v/remittance/orders/$id/quote';
  static String remittancePaidIn(String id) => '$_v/remittance/orders/$id/paid-in';
  static String remittanceComplete(String id) => '$_v/remittance/orders/$id/complete';
  static String remittanceFail(String id) => '$_v/remittance/orders/$id/fail';
  static String remittanceRefund(String id) => '$_v/remittance/orders/$id/refund';

  // Payroll
  static const String payrollRegister = '$_v/payroll/register';
  static const String payrollMy = '$_v/payroll/my';
  static String payrollApprove(String id) => '$_v/payroll/$id/approve';
  static String payrollSuspend(String id) => '$_v/payroll/$id/suspend';
  static const String payrollBatches = '$_v/payroll/batches';
  static const String payrollBatchesCsv = '$_v/payroll/batches/csv';
  static String payrollBatch(String id) => '$_v/payroll/batches/$id';
  static String payrollBatchApprove(String id) => '$_v/payroll/batches/$id/approve';
  static String payrollBatchProcess(String id) => '$_v/payroll/batches/$id/process';
  static String payrollEmployees(String employerId) => '$_v/payroll/$employerId/employees';

  // Merchant
  static const String merchantRegister = '$_v/merchant/register';
  static const String merchantMy = '$_v/merchant/my';
  static String merchantApprove(String id) => '$_v/merchant/$id/approve';
  static String merchantSuspend(String id) => '$_v/merchant/$id/suspend';
  static const String merchantStores = '$_v/merchant/stores';
  static String merchantStoresList(String id) => '$_v/merchant/$id/stores';
  static const String merchantQrGenerate = '$_v/merchant/qr/generate';
  static const String merchantPay = '$_v/merchant/pay';
  static String merchantRefund(String id) => '$_v/merchant/$id/refund';
  static const String merchantPaymentsMy = '$_v/merchant/payments/my';
  static String merchantPayments(String id) => '$_v/merchant/$id/payments';
  static String merchantPayment(String id) => '$_v/merchant/payment/$id';

  // Loyalty
  static const String loyaltyPoints = '$_v/loyalty/points';
  static const String loyaltyPointsAward = '$_v/loyalty/points/award';
  static const String loyaltyPointsRedeem = '$_v/loyalty/points/redeem';
  static const String loyaltyPointsHistory = '$_v/loyalty/points/history';
  static const String loyaltyCashbackCalculate = '$_v/loyalty/cashback/calculate';
  static const String loyaltyRewards = '$_v/loyalty/rewards';
  static const String loyaltyTiers = '$_v/loyalty/tiers';

  // Humanitarian
  static const String humanitarianOrganizations = '$_v/humanitarian/organizations';
  static const String humanitarianPrograms = '$_v/humanitarian/programs';
  static const String humanitarianDisburse = '$_v/humanitarian/disburse';
  static const String humanitarianHistory = '$_v/humanitarian/history';

  // Financing
  static const String financingProducts = '$_v/financing/products';
  static const String financingApply = '$_v/financing/apply';
  static String financingApprove(String id) => '$_v/financing/$id/approve';
  static String financingDisburse(String id) => '$_v/financing/$id/disburse';
  static String financingRepay(String id) => '$_v/financing/$id/repay';
  static const String financingMyLoans = '$_v/financing/my-loans';

  // Education
  static const String educationInstitutions = '$_v/education/institutions';
  static const String educationRegisterStudent = '$_v/education/register-student';
  static const String educationCreateFee = '$_v/education/create-fee';
  static String educationPayFee(String id) => '$_v/education/$id/pay-fee';
  static String educationStudentFees(String id) => '$_v/education/student/$id/fees';

  // Government Collections
  static const String govCollectionsProviders = '$_v/gov-collections/providers';
  static const String govCollectionsInquire = '$_v/gov-collections/inquire';
  static String govCollectionsPay(String id) => '$_v/gov-collections/$id/pay';
  static const String govCollectionsHistory = '$_v/gov-collections/history';

  // Open Finance
  static const String openFinanceRegisterApp = '$_v/open-finance/register-app';
  static const String openFinanceApps = '$_v/open-finance/apps';
  static const String openFinanceConsents = '$_v/open-finance/consents';
  static const String openFinanceCreateConsent = '$_v/open-finance/create-consent';
  static const String openFinanceGenerateToken = '$_v/open-finance/generate-token';
  static String openFinanceRevokeConsent(String id) => '$_v/open-finance/$id/revoke-consent';

  // Agent
  static const String agentRegister = '$_v/agents/register';
  static const String agentMy = '$_v/agents/my';
  static String agentApprove(String id) => '$_v/agents/$id/approve';
  static String agentSuspend(String id) => '$_v/agents/$id/suspend';
  static const String agentTransactions = '$_v/agents/transactions';
  static String agentCommission(String id) => '$_v/agents/$id/commission';

  // Notifications
  static const String notifications = '$_v/notifications';
  static String notificationRead(String id) => '$_v/notifications/$id/read';
  static const String notificationsMarkAllRead = '$_v/notifications/mark-all-read';
}
