# Journey 4: Inbound Remittance (Receive from Diaspora)

## Goal
Syrian diaspora member sends money from Germany via a partner MTO (Money Transfer Operator). Recipient in Syria receives SMS with reference, opens Beza app, confirms receipt, and funds are credited.

## Actor
- Role: Recipient (Tier 2 user in Syria)
- Device: Mobile
- Language: Arabic (default)
- Tier: Tier 2 (required for remittance receive ≥ 500 USD equivalent)
- Connectivity: Online

## Preconditions
- Recipient has completed KYC Tier 2
- Sender is in Germany (or Lebanon) using a partner MTO (Western Union, Ria, or MoneyGram)
- Sender has recipient's full name (as registered in Beza) and phone number
- Remittance corridor is active: Germany (1-2 business days) or Lebanon (same-day)
- Recipient has SYP wallet (default) and may have USD wallet

## Success Flow
| Step | Actor | Action | System | Event Emitted | State Change |
|------|-------|--------|--------|---------------|--------------|
| 1 | Sender | Goes to MTO agent in Berlin, fills form with recipient name: "أحمد محمد الخالد", phone: "00963933456789", amount: €200 | MTO processes transfer to Beza settlement account | — | — |
| 2 | MTO System | — | Sends remittance instruction to Beza via API: reference "MTO-DE-20260529-78432", amount €200, recipient 0933456789 | `REMITTANCE_INBOUND` | Remittance: pending |
| 3 | Beza System | — | Converts EUR to SYP at daily rate: €200 × 12,500 = 2,500,000 SYP. Calculates fee: 50,000 SYP (2%). Net: 2,450,000 SYP | — | — |
| 4 | Recipient | Receives SMS: "وصلتك حوالة من ألمانيا بقيمة 2,500,000 ل.س. الرقم المرجعي: MTO-78432. افتح التطبيق لاستلامها." | — | — | — |
| 5 | Recipient | Opens Beza app | Home screen shows banner: "حوالة واردة بقيمة 2,500,000 ل.س. اضغط لاستلامها" | — | — |
| 6 | User | Taps on banner or goes to "الحوالات" (Remittances) section | Shows list of pending remittances with reference, amount, origin country | — | — |
| 7 | User | Selects remittance "MTO-78432 - 2,500,000 ل.س - ألمانيا" | Shows details: المرسل: unknown (MTO), المبلغ: 2,500,000 ل.س, الرسوم: 50,000 ل.س, الصافي: 2,450,000 ل.س, سعر الصرف: 1 EUR = 12,500 SYP | — | — |
| 8 | User | Taps "استلام" (Receive) | Prompts: "اختر المحفظة: 1. محفظة SYP (رصيد: 125,000 ل.س) 2. محفظة USD (رصيد: $0)" | — | — |
| 9 | User | Selects "محفظة SYP" | Shows PIN prompt | — | — |
| 10 | User | Enters PIN | Validates PIN | — | — |
| 11 | System | — | Credits SYP wallet with 2,450,000 SYP. Sends confirmation | `REMITTANCE_CLAIMED` | Balance: 125,000 → 2,575,000 SYP, Remittance: claimed |
| 12 | System | — | Shows success screen: "تم استلام الحوالة! 2,450,000 ل.س مضافة إلى محفظتك" | — | — |
| 13 | Sender | Receives SMS notification from MTO: "Ihre Überweisung 2.450.000 SYP an Ahmad Al-Khaled wurde zugestellt." | — | — | — |
| 14 | User | Can view transaction in history under "الحوالات الواردة" | — | — | — |

## Alternative Flows
### A1: Lebanon corridor (same-day)
Transfer from Lebanon (€100 or $100) arrives within 2 hours. Lower fee: 1.5%. SMS: "وصلتك حوالة من لبنان. تستلم فوراً."

### A2: Receive in USD wallet (not SYP)
If user has USD wallet and selects it, remittance is held in USD at 1:1 (minus fee in USD). No FX conversion.

### A3: Recipient doesn't claim within 7 days
Remittance auto-expires, funds returned to MTO. SMS reminder sent on day 3 and day 6.

### A4: Partial claim not allowed
Remittance must be claimed in full. User cannot split into partial amounts.

## Failure Flows
### F1: MTO reference number invalid
If reference not found in Beza system: "رقم الحوالة غير صحيح. يرجى التأكد من الرقم مع المرسل أو الاتصال بخدمة العملاء 1234."

### F2: Exchange rate expired
FX rate locked for 24 hours. If claim after 24h, new rate applied. User must accept new rate before proceeding.

### F3: Tier 2 not completed
If recipient is Tier 1: "لا يمكن استلام الحوالات التي تزيد عن 500 USD. يرجى ترقية حسابك من الإعدادات."

## Notifications
- SMS (pending): "وصلتك حوالة من {origin} بقيمة {amount} ل.س. المرجع: {ref}. افتح التطبيق للاستلام."
- SMS (success): "تم استلام حوالة {amount} ل.س من {origin}. الرصيد: {balance} ل.س."
- Push (pending): "حوالة واردة جديدة من {origin}"
- Push (reminder day 3): "لديك حوالة غير مستلمة. تنتهي بعد 4 أيام."
- SMS (expired): "انتهت صلاحية الحوالة {ref}. تم إرجاع المبلغ إلى المرسل."

## Ledger Impact
| Account | Debit | Credit | Currency |
|---------|-------|--------|----------|
| MTO Settlement Account | 2,500,000 SYP | — | SYP |
| User SYP Wallet | — | 2,450,000 SYP | SYP |
| Beza Remittance Fee Income | — | 50,000 SYP | SYP |
| FX Income (if applicable) | — | variance | SYP |

## State Changes
- Remittance: pending → claimed
- Wallet balance (SYP): 125,000 → 2,575,000 SYP
- Daily limit consumed: +2,450,000 SYP
- MTO settlement: reduced by 2,500,000 SYP

## UI Screens
1. Home (with banner) → 2. Remittance List → 3. Remittance Detail → 4. Wallet Selection → 5. PIN Entry → 6. Processing → 7. Success (amount + new balance) → 8. Transaction History
