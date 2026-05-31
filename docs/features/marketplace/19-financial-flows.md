# Financial Flows - Marketplace

## Flow 1: Mobile Top-Up (Syriatel / MTN)

### Scenario
User purchases 10,000 SYP mobile credit for their Syriatel line.

### Step-by-Step Financial Flow

**Step 1**: User submits top-up request for 10,000 SYP to Syriatel number 0933-456-789

**Step 2**: Beza checks wallet balance (45,300 SYP >= 10,000 SYP) - Sufficient

**Step 3**: Beza deducts 10,000 SYP from user wallet (Hold placed). Wallet: 45,300 -> 35,300 SYP

**Step 4**: Beza calls Syriatel API:
- POST /syriatel/topup
- Body: {"phone": "0933456789", "amount": 10000, "currency": "SYP", "requestId": "txn_abc"}

**Step 5**: Syriatel returns 200 OK with transaction reference

**Step 6**: User receives success notification. Wallet hold confirmed.

**Step 7**: Beza records transaction for weekly settlement.
Beza earns 0 SYP direct commission from user; profit comes from wholesale spread with Syriatel.

### Settlement with Telecom Operators

**Weekly Settlement Process**:

1. Every Monday 00:00, Beza generates settlement report for previous week (Mon-Sun)
2. Report includes: total top-ups volume, transaction count, agreed commission rate, net settlement
3. Beza sends report to Syriatel finance department
4. Syriatel issues invoice for net amount
5. Beza transfers net settlement to Syriatel designated account

Example weekly settlement:
- Total top-ups: 15,000,000 SYP
- Transactions: 2,500
- Beza commission (agreed spread): 2%
- Beza retains: 300,000 SYP
- Net to Syriatel: 14,700,000 SYP

### P&L per Top-Up Transaction (10,000 SYP Example)

| Line Item | Amount (SYP) | Notes |
|---|---|---|
| User pays | 10,000 | Wallet deduction |
| Beza pays Syriatel | 9,800 | Wholesale rate (2% margin) |
| Beza gross profit | 200 | 2% spread |
| Payment processing fee | 0 | Wallet-to-wallet, no processor |
| Telecom API cost | ~5 | Per-transaction API fee |
| Net profit | ~195 | Per transaction |

---

## Flow 2: Digital Goods Purchase

### Scenario
User buys a PUBG Mobile 600 UC game code for 28,000 SYP from vendor PC Market.

### Step-by-Step Financial Flow

**Step 1**: User places order for 600 UC (28,000 SYP) via Beza Marketplace

**Step 2**: Beza checks vendor stock (500 units available) - OK

**Step 3**: Beza places hold on user wallet for 28,000 SYP
- User wallet: 45,300 -> 17,300 SYP (available)
- 28,000 SYP held (not yet released to vendor)

**Step 4**: Order confirmed with status PROCESSING

**Step 5**: Beza calls vendor API to deliver game code
- Vendor returns activation code: PUBG-X7K9-M2N4
- Code displayed to user and sent via SMS

**Step 6**: Delivery confirmed. Wallet hold status: RELEASE_PENDING

**Step 7**: Commission calculation:
- Digital goods commission rate: 12%
- Commission amount: 28,000 x 0.12 = 3,360 SYP
- Vendor earns: 28,000 - 3,360 = 24,640 SYP

**Step 8**: Wallet reconciliation:
- Hold is released from user (already deducted)
- 24,640 SYP credited to vendor's Beza wallet (as available balance)
- 3,360 SYP credited to Beza platform revenue account

**Step 9**: Commission recorded in marketplace_commissions table with status 'accrued'

### Commission Flow Diagram

User Wallet -> Beza Platform Wallet -> Vendor Wallet
- User pays 28,000 (hold placed)
- Code delivered -> hold confirmed
- Beza splits: 3,360 (Beza commission) + 24,640 (Vendor earnings)
- 24,640 SYP available in vendor wallet for payout

### P&L per Digital Goods Transaction (28,000 SYP Example)

| Line Item | Amount (SYP) | Notes |
|---|---|---|
| User pays | 28,000 | Wallet deduction |
| Vendor earnings | 24,640 | 88% of sale price |
| Beza commission | 3,360 | 12% commission rate |
| Payment processing | 0 | Wallet-to-wallet |
| Refund reserve | ~168 | 0.5% set aside |
| Net Beza revenue | ~3,192 | Per transaction |

---

## Flow 3: Gift Card Purchase

### Scenario
User buys a 50,000 SYP gift card for Electronics Store and sends to a friend.

### Step-by-Step Financial Flow

**Step 1**: User purchases gift card for 50,000 SYP from merchant Electronics Store

**Step 2**: Wallet hold placed: 50,000 SYP

**Step 3**: Gift card generated with unique 16-digit code and QR

**Step 4**: Card delivered to recipient via WhatsApp

**Step 5**: Commission recorded:
- Gift card commission rate: 8%
- Commission amount: 50,000 x 0.08 = 4,000 SYP
- Merchant earns: 50,000 - 4,000 = 46,000 SYP (when redeemed)

**Step 6**: Wallet hold confirmed: 50,000 SYP deducted from user

**Step 7**: Funds held in Beza platform until merchant redeems gift card

### Gift Card Redemption Flow

When recipient redeems at merchant:
1. Recipient presents code/QR at Electronics Store
2. Merchant validates code via Beza API (POST /marketplace/gift-cards/redeem)
3. Beza confirms card validity and remaining balance
4. Merchant receives 46,000 SYP (minus any prior commission)
5. Card marked as REDEEMED

### P&L per Gift Card (50,000 SYP Example)

| Line Item | Amount (SYP) | Notes |
|---|---|---|
| User pays | 50,000 | Wallet deduction |
| Merchant commission | 4,000 | 8% (Beza revenue) |
| Net to merchant | 46,000 | Upon redemption |
| Unredeemed liability | 50,000 | If not redeemed within expiry |
| Breakage revenue | 50,000 | If card expires unredeemed |

---

## Flow 4: Physical Goods (Future)

### Flow
1. User purchases physical product -> wallet hold placed
2. Vendor notified -> prepares shipment
3. Vendor ships with tracking -> tracking number entered in system
4. User receives -> confirms delivery
5. Hold released -> vendor paid
6. Commission deducted at settlement

### Settlement Schedule

| Product Type | Hold Release | Commission Deduction |
|---|---|---|
| Mobile Top-Up | Immediate on success | N/A (spread model) |
| Digital Goods | On code delivery | At delivery |
| Gift Cards | On card generation | At generation |
| Physical Goods | On delivery confirmation | On settlement (7-day) |