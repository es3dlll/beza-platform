# Feature Requirements

## Functional Requirements

### FR1 — Product Catalog
- FR1.1: Display products organized by category (top-up, internet, gift cards, digital goods, bills)
- FR1.2: Support search with full-text and filter by category, price range, vendor, rating
- FR1.3: Support multiple product types: fixed-price, variable-denomination, subscription
- FR1.4: Display real-time stock/inventory status
- FR1.5: Support product images, descriptions, terms, and vendor info
- FR1.6: Multi-language product descriptions (Arabic primary, English secondary)

### FR2 — Mobile Top-Up
- FR2.1: Support Syriatel (093x, 094x) and MTN (095x, 096x) number prefixes
- FR2.2: Validate phone number format and network on input
- FR2.3: Offer predefined amounts: 250, 500, 1,000, 2,500, 5,000, 10,000, 25,000 SYP
- FR2.4: Support custom amount (within network minimum/maximum limits)
- FR2.5: Show current wallet balance before confirmation
- FR2.6: Auto top-up feature (trigger when balance < threshold)
- FR2.7: Schedule future top-up
- FR2.8: Save favorite numbers with nicknames

### FR3 — Gift Cards
- FR3.1: Offer digital gift cards from partner merchants
- FR3.2: Support fixed and custom denomination per merchant
- FR3.3: Send via WhatsApp, SMS, email, or in-app share
- FR3.4: Recipient can redeem to Beza wallet or merchant directly
- FR3.5: Gift card design/customization per merchant brand
- FR3.6: Expiration management and auto-reminder
- FR3.7: Bulk gift card purchase (enterprise/events)

### FR4 — Digital Goods Delivery
- FR4.1: Instant delivery of codes/keys upon payment confirmation
- FR4.2: Support delayed/scheduled delivery
- FR4.3: View delivery status and history
- FR4.4: Re-deliver or resend code on request
- FR4.5: Partial fulfillment for multi-item orders

### FR5 — Cart & Checkout
- FR5.1: Multi-item cart with quantity management
- FR5.2: Price calculation including commissions, taxes, discounts
- FR5.3: Apply promo codes and loyalty discounts
- FR5.4: Choose delivery method per item (instant, scheduled)
- FR5.5: Order summary before confirmation
- FR5.6: Payment via Beza wallet — hold placed on confirmation

### FR6 — Order Management
- FR6.1: Order history with status tracking
- FR6.2: Cancel order (before fulfillment)
- FR6.3: Request refund (after fulfillment, subject to policy)
- FR6.4: Re-order from past orders
- FR6.5: Rate and review products/vendors after delivery

### FR7 — Vendor Dashboard
- FR7.1: Product listing management (create, edit, pause, delete)
- FR7.2: Inventory management with bulk upload
- FR7.3: Order fulfillment interface
- FR7.4: Sales analytics and payout tracking
- FR7.5: Commission reports
- FR7.6: Customer message/reply system

### FR8 — Fulfillment System
- FR8.1: Auto-fulfill digital goods via vendor API integration
- FR8.2: Manual fulfillment interface for non-integrated vendors
- FR8.3: Verification step for physical goods tracking
- FR8.4: Delivery confirmation and status updates

### FR9 — Commission Engine
- FR9.1: Category-based commission rates (configurable per vendor)
- FR9.2: Tiered commission based on volume thresholds
- FR9.3: Automatic commission deduction at payment settlement
- FR9.4: Commission payout schedule (instant for digital, 7-day for physical)
- FR9.5: Commission reports for vendors and Beza finance

### FR10 — Telecom Integration
- FR10.1: Direct REST API integration with Syriatel and MTN
- FR10.2: Real-time balance check and top-up
- FR10.3: Transaction status polling with timeout
- FR10.4: Retry logic with idempotency keys
- FR10.5: Weekly settlement reconciliation with telecom partners
- FR10.6: Fallback SMS/USSD gateway if API is down

## Non-Functional Requirements

### NFR1 — Performance
- NFR1.1: Product catalog page load < 2s (p95)
- NFR1.2: Top-up fulfillment < 10s (p99)
- NFR1.3: Digital goods delivery < 5s (p99)
- NFR1.4: API response time < 500ms (p95)

### NFR2 — Reliability
- NFR2.1: Marketplace uptime > 99.9%
- NFR2.2: Zero loss of transaction data
- NFR2.3: Idempotent payment processing
- NFR2.4: Fulfillment success rate > 99.5%

### NFR3 — Security
- NFR3.1: All API communication over TLS 1.3
- NFR3.2: PCI-DSS compliance for payment data
- NFR3.3: Rate limiting on all public endpoints
- NFR3.4: Fraud detection on high-velocity transactions
- NFR3.5: Activity logging with audit trail

### NFR4 — Scalability
- NFR4.1: Support 1,000+ concurrent users
- NFR4.2: Handle 10,000+ orders per day
- NFR4.3: Auto-scale during peak (Eid, holidays)
- NFR4.4: Support 500+ active vendors

### NFR5 — Localization
- NFR5.1: Arabic-first UI with full RTL support
- NFR5.2: English as secondary language
- NFR5.3: SYP as primary currency (USD for international products)
- NFR5.4: Local number formatting
