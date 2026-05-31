# Gift Card System

## Gift Card Generation

### Code Format
```
Format: GC-XXXX-XXXX-XXXX-XXXX
Example: GC-7K9M-X2N4-P8Q1-A3B2

- 16 alphanumeric characters (excluding GC- prefix and dashes)
- Characters: A-Z (excluding I, O, Q) + 0-9
- Checksum: Luhn mod 10 on alphanumeric value
- Collision probability: < 1 in 10^18
```

### QR Code
- Generated at time of card creation
- Encodes: card code + merchant ID + initial balance
- Format: `beza://gift/{code}?merchant={merchant_id}&amount={balance}`
- Resolution: 300x300px, PNG with transparent background
- Embedded merchant logo in center

## Delivery Channels

| Channel | Method | Open Rate | Cost |
|---|---|---|---|
| WhatsApp | Twilio WhatsApp API | 98% | Free (template messages) |
| SMS | Twilio SMS API | 95% | ~50 SYP per message |
| Email | SendGrid API | 70% | Free |
| In-app notification | Firebase Cloud Messaging | 90% | Free |
| Downloadable PDF | Generated server-side | - | Free |

### WhatsApp Message Template
```
🎁 {merchant_name} بطاقة هدية من

{personal_message}

قيمة البطاقة: {amount} ل.س
رمز الهدية: {code}

اضغط لاستلام الهدية: {link}

شكراً،
{Beza App Name}
```

## Merchant Redemption

### Online Redemption
1. Customer enters gift card code at merchant checkout
2. Merchant calls POST /marketplace/gift-cards/redeem
3. Beza validates code, balance, and expiry
4. Beza marks redemption: reduces remaining balance
5. Merchant notified of successful redemption
6. If full balance: card marked REDEEMED

### In-Store Redemption
1. Customer presents QR code at point of sale
2. Merchant scans QR using Beza Merchant app or POS integration
3. Merchant enters redemption amount
4. Beza processes as above
5. Customer receives SMS notification of redemption
6. Merchant credited net amount (face value - commission)

## Expiration & Breakage

| Card Type | Expiration | Breakage Policy |
|---|---|---|
| Standard gift card | 12 months from purchase | 100% revenue after expiry |
| Promotional card | 3 months | 100% revenue after expiry |
| Enterprise bulk | Custom (negotiated) | Per contract |

### Expiration Flow
1. D-30: Send reminder notification to card holder
2. D-7: Send final reminder
3. D-0: Card auto-expires, status changed to EXPIRED
4. D+1: Breakage revenue recognized in platform financials

## Partial Redemption

- Gift cards support partial redemption
- Multiple redemptions until balance reaches 0
- Each redemption recorded in marketplace_gift_cards redemption history
- Remaining balance always available for query
- Minimum partial redemption: 1,000 SYP
