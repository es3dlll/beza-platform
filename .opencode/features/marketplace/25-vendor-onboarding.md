# Vendor Onboarding

## Application Process

### Step 1: Submit Application
```
Vendor submits via beza.sy/vendors
├── Business name (Arabic + English)
├── Owner full name
├── Phone number
├── Email address
├── Business category
├── Business license upload (PDF/JPG, max 10MB)
├── Tax ID (optional)
└── Product catalog sample (CSV optional)
```

### Step 2: Review (Admin)
```
Admin reviews application
├── Verify business license authenticity
├── Check phone/email validity
├── Background check (if applicable)
├── Approval (auto if documentation complete)
│   └── Sent to integration queue
└── Rejection (with reason)
    └── Vendor can re-apply after 30 days
```

### Step 3: Integration
```
Approved vendor
├── Receives welcome email with vendor portal link
├── Sets up payout method (wallet/bank)
├── Reads marketplace terms and accepts commission rates
├── Completes vendor profile (logo, description, cover)
├── Creates first product listing(s)
│   └── Product(s) enter moderation queue
├── Integrates fulfillment API (optional, for auto-delivery)
└── Goes live after first product approved
```

### Step 4: Go Live
```
Vendor dashboard enabled
├── Products visible in marketplace
├── Orders start flowing in
├── Real-time notifications enabled
└── First 30 days: enhanced support, reduced commission (10% off)
```

## SLA Commitments for Vendors

| Activity | Target |
|---|---|
| Application review | Within 2 business days |
| Product moderation | Within 24 hours |
| First payout | Within 7 days of first sale |
| Support response | Within 4 hours (business hours) |
| Dispute resolution | Within 48 hours |

## Vendor Requirements

| Requirement | Digital Goods | Physical Goods |
|---|---|---|
| Business license | Required | Required |
| Tax ID | Recommended | Required |
| Product images | Min 1, recommended 3+ | Min 3 |
| Inventory system | API or manual | Manual mandatory |
| Response time SLA | < 1 hour | < 24 hours |
| Refund policy | Must accept | Must accept |
| Minimum order fulfillment | 95% within SLA | 90% within SLA |

## Vendor Dashboard Features

- Real-time sales counter
- Order management with fulfillment queue
- Product CRUD with bulk CSV import
- Inventory alerts (low stock, out of stock)
- Commission and earnings dashboard
- Payout request
- Customer reviews (read-only)
- Analytics (top products, conversion rate, traffic)
- Support ticket system
