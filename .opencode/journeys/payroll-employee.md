# Journey 6: Payroll Disbursement (Employee)

## Goal
Employer uploads payroll file for 50 employees. Each employee receives salary notification in their Beza wallet. Employee can cash out or transfer funds.

## Actor
- Role: Employee (public sector teacher) receiving salary
- Device: Mobile
- Language: Arabic (default)
- Tier: Tier 1+ (sufficient for salary ≤ 500,000 SYP)
- Connectivity: Online (can check offline later)

## Preconditions
- Employer is registered as a Beza Business client
- Employee has active Beza wallet (Tier 1 minimum)
- Payroll file is prepared in CSV format (employee phone, amount, reference)
- Employer has sufficient balance in Business Wallet to cover total payroll (e.g., 50 × 250,000 SYP = 12,500,000 SYP)
- End-of-month (Syrian calendar): last Thursday of the month

## Success Flow
| Step | Actor | Action | System | Event Emitted | State Change |
|------|-------|--------|--------|---------------|--------------|
| 1 | Employer Finance | Logs into Beza Business dashboard, navigates to "الرواتب" (Payroll) | Shows upload screen with template download link | — | — |
| 2 | Employer | Downloads CSV template, fills 50 rows with employee names, phone numbers, amounts (each 250,000 SYP), reference "رواتب أيار 2026" | — | — | — |
| 3 | Employer | Uploads CSV file | Validates: all numbers are valid Syrian numbers, total 12,500,000 SYP ≤ business balance | — | — |
| 4 | System | — | Shows preview of all 50 employees with amounts. Total: 12,500,000 SYP. Fees: 0.5% = 62,500 SYP. Grand total: 12,562,500 SYP. | — | — |
| 5 | Employer | Confirms payroll batch, enters PIN | Processes batch payment | `PAYROLL_SUBMITTED` | Business balance: reduced by 12,562,500 SYP |
| 6 | System | — | For each employee: creates salary transaction, credits wallet, sends notification | `SALARY_DISBURSED` (×50) | Each employee wallet: credited |
| 7 | Employee (User) | Receives SMS: "تم إيداع راتبك 250,000 ل.س في محفظة Beza. الرصيد: 375,000 ل.س. وزارة التربية - أيار 2026" | — | — | — |
| 8 | Employee | Opens app, sees home screen with banner: "استلمت راتبك! 250,000 ل.س من وزارة التربية" | — | — | — |
| 9 | Employee | Taps banner to view salary details | Shows: "راتب شهر أيار 2026 / المبلغ: 250,000 ل.س / جهة الإيداع: وزارة التربية / التاريخ: 28 أيار 2026" | — | — |
| 10 | Employee | Decides to cash out 100,000 SYP at agent | Navigates to cash-out flow | — | — |
| 11 | Employee | Goes to nearest agent on map, completes cash-out of 100,000 SYP | — | `CASHOUT_COMPLETED` | Balance: 375,000 → 275,000 SYP |
| 12 | Employee | Receives SMS: "تم سحب 100,000 ل.س. الرصيد: 275,000 ل.س." | — | — | — |

## Alternative Flows
### A1: Employee not registered on Beza
System sends SMS invite: "وظفك {company} يدفع راتبك عبر Beza. سجّل الآن: bez.app/dwnld. الرمز: {code}". Funds held in pending until registration.

### A2: Private sector payroll (different day)
Private companies pay on varying dates (e.g., 15th of month). Processing is same but SMS sender name is company name instead of "وزارة".

### A3: Partial payment
If employer has insufficient balance for full batch, system suggests processing partial batch. Employer can select which employees to pay.

### A4: Employee wants to transfer full salary
Employee can transfer salary to family member immediately. No hold period.

## Failure Flows
### F1: CSV format error
System rejects with specific error: "العمود الثالث يجب أن يكون المبلغ / الصف 7: رقم هاتف غير صالح". Employer corrects and re-uploads.

### F2: Duplicate employee entry
If same phone appears twice in same batch, deduplication logic keeps most recent entry and warns employer.

### F3: Employee wallet at max limit
If wallet balance would exceed Tier 2 max (2,000,000 SYP), payment fails for that employee. Notify employer: "الموظف {name} تجاوز الحد الأقصى للمحفظة."

### F4: Business account frozen
If employer's business account is flagged (e.g., suspicious activity), payroll batch is queued for compliance review before processing.

## Notifications
- SMS (employee): "تم إيداع راتبك {amount} ل.س من {employer}. الرصيد: {balance} ل.س. Beza"
- SMS (unregistered): "وظفك {employer} يدفع راتبك عبر Beza. سجّل الآن: bez.app/dwnld. الرمز: {code}"
- Push (employee): "راتب {month} من {employer} متوفر الآن في محفظتك!"
- SMS (employer confirmation): "تم دفع رواتب {count} موظف بنجاح. المبلغ الإجمالي: {total} ل.س."
- SMS (employer failure): "فشل دفع راتب {count} موظف. راجع التفاصيل في لوحة التحكم."

## Ledger Impact
| Account | Debit | Credit | Currency |
|---------|-------|--------|----------|
| Business Wallet (وزارة التربية) | 12,562,500 SYP | — | SYP |
| Employee 1 Wallet | — | 250,000 SYP | SYP |
| Employee 2 Wallet | — | 250,000 SYP | SYP |
| ... (×50) | — | 250,000 SYP each | SYP |
| Beza Payroll Fee Income | — | 62,500 SYP | SYP |

## State Changes
- Business wallet balance: decreased by 12,562,500 SYP
- Each employee wallet: increased by 250,000 SYP
- Payroll batch: draft → submitted → completed
- Transaction records: 50 new transactions (completed)

## UI Screens
**Employer side:**
1. Business Dashboard → 2. Payroll → 3. Upload CSV → 4. Preview & Confirm → 5. PIN Entry → 6. Processing → 7. Batch Results

**Employee side:**
1. Home (banner) → 2. Salary Detail → 3. Balance Update → 4. Optional: Cash-Out or Transfer
