# Merchant User Personas

## Persona 1: Abu Bilal — Street Fruit Vendor
```
Age: 52
Occupation: Fruit cart vendor in Bab Touma, Damascus
Income: 200,000-400,000 SYP/month (seasonal)
Tech literacy: None (illiterate)
Smartphone: Budget Android (son set it up)
Banked: No (cash-only his entire life)
Current payment: Cash only — customers often don't have small change
Pain points:
  - Loses sales when customers don't have cash
  - Handles counterfeit 500 and 1000 SYP notes daily
  - Cannot give change for large notes (loses sale)
  - Hides daily earnings under his mattress
  - No way to track daily sales (relies on feeling the bag weight)
Needs:
  - Fixed QR code laminated and tied to his cart
  - Customer enters amount — Abu Bilal cannot type
  - Voice notification when payment received ("تم استلام 5000 ل.س")
  - No PIN, no password — just receives payments
  - Son helps him withdraw from wallet each week
  - Must work offline (poor internet in old Damascus)
Product: Static QR Code (laminated, tied to cart), merchant app with voice
```

## Persona 2: Al-Sham Supermarket — Small Retail
```
Age: 38
Occupation: Supermarket owner, Baramkeh, Damascus
Employees: 3 (himself + 2 cashiers)
Income: 2,000,000-4,000,000 SYP/month
Tech literacy: Medium (uses WhatsApp, Facebook)
Smartphone: Yes (Samsung A series)
Banked: No (cash-only) — tried to open bank account, gave up after 3 months
Current payment: Cash + sometimes "on credit" for regulars
Pain points:
  - Cashiers steal — no way to track individual sales
  - Daily counting takes 45 minutes (prone to errors)
  - Cannot accept cards from customers who ask
  - Has lost 500K+ in counterfeit notes last year
  - Supplier demands cash payments — risky to carry large sums
  - Wants to offer delivery but no payment solution for delivery boys
Needs:
  - POS terminal with receipt printer (2 terminals — one per cashier)
  - Employee PIN per cashier (track who sold what)
  - Daily sales report by cashier
  - QR code at checkout counter too
  - Settlement to wallet (can pay suppliers via Beza transfer)
  - Works with unstable electricity/internet
Product: POS Terminal (x2) + Static QR, employee management
```

## Persona 3: Damascus Bazar — Online Shop
```
Age: 26
Occupation: E-commerce entrepreneur, runs DamascusBazar.com
Income: 3,000,000-6,000,000 SYP/month
Tech literacy: High (builds his own website on WordPress/WooCommerce)
Smartphone: Yes (iPhone 14)
Banked: Yes (has BBS account for USD)
Current payment: Cash on delivery (COD) — 60% of orders; Bank transfer — 30%;
  Syriatel Cash — 10%
Pain points:
  - COD has 25% rejection rate (customers don't answer door)
  - Bank transfers take 1-3 days to confirm
  - No real-time payment confirmation
  - Cannot offer checkout without bank account requirement
  - Syriatel Cash only works for Syriatel subscribers
  - No way to do recurring billing for subscription customers
Needs:
  - Web checkout API (REST + redirect)
  - Webhook to confirm payment instantly (fulfill same minute)
  - Hosted payment page (mobile-optimized, Arabic)
  - Plugin for WooCommerce
  - Settlement to bank account OR Beza wallet
  - Refund API (customer returns → partial refund)
Product: Web Checkout API + WooCommerce plugin + Webhooks
```

## Persona 4: Beit al-Sham Restaurant — F&B
```
Age: 45
Occupation: Restaurant owner, Old City, Damascus
Employees: 8 (kitchen + waitstaff)
Income: 4,000,000-8,000,000 SYP/month
Tech literacy: Low
Smartphone: Yes (uses for WhatsApp orders)
Banked: No
Current payment: Cash + occasionally customers ask to "pay later"
Pain points:
  - Busy lunch rush — cash handling slows everything down
  - Waitstaff make mistakes with change
  - Customers want to split bill — impossible with cash
  - Takes WhatsApp delivery orders but customers pay cash on delivery
  - Monthly reconciliation takes 2+ hours
  - No way to track which menu items sell best
Needs:
  - QR code on each table (table number encoded)
  - Customer scans QR → sees menu (future) → pays → waiter notified
  - Split bill: 4 friends eating → each scans and pays their share
  - Receipt printer for kitchen
  - POS terminal at counter for walk-in payments
  - Integration with delivery orders (customer pays via link)
Product: Table QR (split bill) + POS Terminal + Payment Links
```
