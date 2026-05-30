# Acceptance Criteria

## AC1 — Mobile Top-Up
- **AC1.1**: Phone number validation accepts 093x-xxx-xxx, 094x-xxx-xxx (Syriatel), 095x-xxx-xxx, 096x-xxx-xxx (MTN)
- **AC1.2**: Invalid numbers show "رقم غير صحيح" (Invalid number) within 1s of input
- **AC1.3**: Network is auto-detected from prefix and displayed
- **AC1.4**: Preset amounts render in SYP with visual selection state
- **AC1.5**: Wallet balance is displayed; insufficient balance shows "الرصيد غير كافٍ" (Insufficient balance)
- **AC1.6**: Top-up is credited to the target number within 10 seconds
- **AC1.7**: Success notification includes transaction ID, amount, and recipient number
- **AC1.8**: Failure notification with clear error and immediate refund to wallet
- **AC1.9**: Top-up history shows all past transactions with status badges

## AC2 — Product Search & Browse
- **AC2.1**: Categories load within 2 seconds (p95)
- **AC2.2**: Full-text search returns results within 1s for < 1,000 products
- **AC2.3**: Filters (category, price range, vendor, rating) update results immediately
- **AC2.4**: Out-of-stock products are clearly labeled "نفد من المخزون"
- **AC2.5**: Product detail page shows: images, description, price, vendor, rating, stock
- **AC2.6**: Multiple images render in a gallery with pinch-to-zoom

## AC3 — Cart & Checkout
- **AC3.1**: Add to cart shows confirmation toast with undo option (5s)
- **AC3.2**: Cart icon badge updates immediately
- **AC3.3**: Cart supports max 50 items per order
- **AC3.4**: Quantity selector prevents negative values and shows max stock
- **AC3.5**: Promo code field accepts codes; invalid codes show "رمز غير صالح"
- **AC3.6**: Order summary itemizes products, fees, taxes, discounts
- **AC3.7**: Payment confirmation creates order with unique ID
- **AC3.8**: Wallet hold is released if order fails within 30 minutes

## AC4 — Digital Goods Delivery
- **AC4.1**: Digital code is displayed in-app within 5s of payment
- **AC4.2**: Code is also sent via SMS and email
- **AC4.3**: "Copy Code" button copies to clipboard
- **AC4.4**: Delivery failure triggers automatic refund within 1 hour
- **AC4.5**: Vendor receives notification of successful fulfillment

## AC5 — Gift Cards
- **AC5.1**: Gift card is generated with unique 16-digit code
- **AC5.2**: QR code is included for in-store redemption
- **AC5.3**: Send via WhatsApp opens WhatsApp with pre-filled message and link
- **AC5.4**: Recipient sees card details upon opening link (with or without Beza app)
- **AC5.5**: Unused gift cards are fully refundable within 14 days
- **AC5.6**: Expired gift cards show "منتهية الصلاحية" (Expired) status

## AC6 — Vendor Dashboard
- **AC6.1**: Product creation form validates required fields (title, description, price, stock)
- **AC6.2**: CSV upload processes up to 1,000 products at once
- **AC6.3**: Orders tab shows pending fulfillment orders sorted by oldest first
- **AC6.4**: Fulfillment marks order as "تم التوصيل" (Delivered)
- **AC6.5**: Commission dashboard shows: today, this week, this month totals
- **AC6.6**: Payout request minimum: 50,000 SYP

## AC7 — Admin Moderation
- **AC7.1**: Pending vendor applications show with submitted date
- **AC7.2**: Approve/Reject with reason field for rejection
- **AC7.3**: Product moderation queue shows all unmoderated products
- **AC7.4**: Commission rate change is logged with before/after values
- **AC7.5**: Dispute resolution panel shows buyer and vendor messages thread
