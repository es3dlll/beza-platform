# Money Handling Standard

> Standard for all monetary values across backend, database, API, and frontend.

---

## 1. Backend (PHP)

### 1.1 Always Use `Money` ValueObject

```php
use App\Domain\ValueObjects\Money;
use App\Domain\ValueObjects\Currency;

// Construction
$amount = Money::fromInt(50000, Currency::SYP());      // SYP 500.00
$amount = Money::fromFloat(500.00, Currency::SYP());   // SYP 500.00
$zero   = Money::zero(Currency::SYP());

// Arithmetic
$total  = $a->add($b);
$diff   = $a->subtract($b);
$fee    = $amount->multiplyBy(0.005);  // 0.5%

// Comparison
$amount->greaterThan($other);
$amount->greaterThanOrEqual($other);
$amount->lessThan($other);
$amount->equals($other);

// Output
$amount->toInt();     // 50000 (minor units)
$amount->toFloat();   // 500.00 (major units)
```

### 1.2 Never Use Float

```php
// ❌ FORBIDDEN
$balance = 500.00;
$fee = $amount * 0.005;
$total = (float) $dbValue;

// ✅ CORRECT
$balance = Money::fromInt(50000, Currency::SYP());
$fee = $amount->multiplyBy(0.005);
$total = Money::fromInt((int) $dbValue, Currency::SYP());
```

## 2. Database

### 2.1 Column Types

| Concept | Column Type | Example |
|---------|------------|---------|
| Amount | `bigint` NOT NULL | `50000` = SYP 500.00 |
| Balance | `bigint` DEFAULT 0 | `1000000` = SYP 10,000.00 |
| Fee | `bigint` DEFAULT 0 | `500` = SYP 5.00 |
| Rate | `bigint` (scaled 1e6) | `13100` = SYP 13,100 per 1 USD |
| Percentage | `integer` (basis points) | `50` = 0.50% |

### 2.2 Currency Column

```sql
`currency` VARCHAR(3) NOT NULL DEFAULT 'SYP'
```

Always store as ISO 4217 code. Never as numeric ID.

## 3. API

### 3.1 Request/Response Format

All monetary values in API are **integer minor units**.

```json
{
  "amount": 50000,
  "amount_formatted": "500.00",
  "currency": "SYP"
}
```

### 3.2 Error Response

```json
{
  "success": false,
  "error": {
    "code": "INSUFFICIENT_BALANCE",
    "message": "Insufficient balance",
    "message_ar": "الرصيد غير كافٍ",
    "details": {
      "required": 100000,
      "available": 50000,
      "currency": "SYP"
    }
  }
}
```

## 4. Frontend (Flutter / React)

### 4.1 Formatting Rules

| Currency | Minor Unit | Format | Example |
|----------|-----------|--------|---------|
| SYP | 2 (piasters) | `#,##0.00` ل.س | `٥٠٠٫٠٠ ل.س` |
| USD | 2 (cents) | `$#,##0.00` | `$500.00` |

### 4.2 No Arithmetic in Frontend

Frontend NEVER performs:
- Addition/subtraction of amounts
- Fee calculation
- Currency conversion

All calculations happen server-side. Frontend displays only.

## 5. Key Conversions

| Value | Storage (bigint) | Display |
|-------|-----------------|---------|
| SYP 1.00 | 100 | `١٫٠٠ ل.س` |
| SYP 500.00 | 50000 | `٥٠٠٫٠٠ ل.س` |
| SYP 1,000,000.00 | 100000000 | `١٬٠٠٠٬٠٠٠٫٠٠ ل.س` |
| USD 1.00 | 100 | `$1.00` |
| 0.50% fee | 50 (basis points) | 0.50% |
| Rate 13,100 | 1310000000 (×100,000) | 13,100 SYP/USD |

## 6. Validation Rules

- Amount must be positive (> 0) for all financial operations
- Zero-amount transactions are rejected at validation layer
- Negative amounts never enter the system (reversals use opposite direction, not negative)
- Max transaction amount: SYP 10,000,000 (configurable per KYC tier)
- Min transaction amount: SYP 100 (configurable)
