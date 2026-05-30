# Merchant Security

## Merchant Identity Verification

### Registration Verification
```
Verification Requirements by Tier:

Tier 1 (Micro) — Street vendors, kiosks:
  - Phone number (SMS OTP)
  - Business name
  - Business type selection
  - 1 photo of business location
  - No license required (informal economy)
  - Daily limit: 1,000,000 SYP

Tier 2 (Small) — Corner shops, bakeries:
  - All Tier 1 requirements
  - Business license photo (if available)
  - 2 photos (storefront + interior)
  - Manual verification within 2 hours
  - Daily limit: 5,000,000 SYP

Tier 3 (Mid) — Supermarkets, restaurants:
  - All Tier 2 requirements
  - Valid business license (mandatory)
  - Owner national ID
  - Google Maps location pin
  - Manual verification within 4 hours
  - Daily limit: 20,000,000 SYP

Tier 4 (Enterprise) — Chains, e-commerce:
  - All Tier 3 requirements
  - Commercial registry extract
  - Tax registration number
  - Bank account (for settlement backup)
  - Enhanced due diligence
  - Custom daily limit
```

### Document Verification
```php
class MerchantVerifier
{
    public function verify(Merchant $merchant, array $documents): VerificationResult
    {
        $score = 0;
        $checks = [];

        // 1. Verify phone number (already done via OTP)
        $checks[] = ['phone_verified' => true];

        // 2. Business license OCR + validation
        if ($license = $documents['license'] ?? null) {
            $ocrResult = $this->ocrService->extractText($license);
            $licenseValid = $this->validateLicenseNumber($ocrResult->licenseNumber);
            $checks[] = ['license_valid' => $licenseValid, 'ocr_confidence' => $ocrResult->confidence];
            if ($licenseValid) $score += 40;
        }

        // 3. Shop photo analysis
        if ($photos = $documents['shop_photos'] ?? []) {
            foreach ($photos as $photo) {
                $analysis = $this->imageAnalyzer->analyze($photo);
                // Check: contains storefront, signage readable, not a stock photo
                $checks[] = [
                    'has_storefront' => $analysis->hasStorefront,
                    'has_signage' => $analysis->hasSignage,
                    'is_authentic' => !$analysis->isStockPhoto,
                ];
                if ($analysis->hasStorefront && $analysis->hasSignage) $score += 30;
            }
        }

        // 4. Duplicate check (same license, same phone, same location)
        $duplicateCheck = $this->checkDuplicates($merchant);
        $checks[] = ['duplicate_check' => !$duplicateCheck->hasDuplicate];
        if ($duplicateCheck->hasDuplicate) {
            return VerificationResult::rejected('تاجر مكرر — موجود مسبقاً');
        }

        // 5. Sanctions screening (name, phone)
        $sanctionsCheck = $this->sanctionsScreener->screenMerchant($merchant);
        $checks[] = ['sanctions_check' => $sanctionsCheck->passed];
        if ($sanctionsCheck->isBlocked) {
            return VerificationResult::rejected('مرفوض — فحص العقوبات');
        }

        $threshold = $merchant->licenseNumber ? 70 : 50; // Higher threshold if license provided
        $isApproved = $score >= $threshold;

        return new VerificationResult(
            approved: $isApproved,
            score: $score,
            checks: $checks,
            reviewerNote: $isApproved ? null : 'لم يتم الوصول إلى الحد الأدنى للتحقق',
        );
    }
}
```

## QR Code Security

### Tamper Protection
```
QR Code Security Measures:
  1. QR payload is signed with HMAC:
     payload = beza://pay/merchant/{id}?type={type}[&amount={amount}]&sig={hmac}
     HMAC = HMAC-SHA256(payload_without_sig, merchant_secret)
  2. QR printed on tamper-evident sticker (laminated)
  3. If QR is replaced/scammed:
     - Customer app validates HMAC before showing merchant name
     - Invalid HMAC → "رمز QR غير صالح — يرجى التواصل مع الدعم"
     - Alerts security team of possible QR swap scam
  4. Merchant can regenerate QR (old QR is deactivated)
  5. Scan count tracking: unusual scan volume without payment → alert

QR Swap Attack Prevention:
  - Customer app verifies QR signature matches merchant ID
  - If merchant ID in payload doesn't match expected → block
  - Error: "QR code تالف أو مستبدل — يرجى إعلام التاجر"
```

## POS Terminal Binding

### Secure Pairing
```php
class PosPairingService
{
    public function pair(PosTerminal $terminal, Merchant $merchant): PairingResult
    {
        // 1. Generate pairing token (one-time use)
        $pairingToken = Str::random(32);
        Cache::put("pos:pairing:{$pairingToken}", [
            'terminal_serial' => $terminal->serialNumber,
            'expires_at' => now()->addMinutes(5),
        ], 300);

        // 2. Display pairing QR on merchant app
        // QR contains: pairing_token, merchant_id, api_url

        // 3. Terminal scans QR → sends POST with token + cert CSR
        // POST /api/v1/merchant/pos/complete-pairing
        // Body: { token, csr, terminal_id }

        // 4. Server validates token
        $session = Cache::get("pos:pairing:{$token}");
        if (!$session || $session['expires_at']->isPast()) {
            throw new PosPairingFailedException('انتهت صلاحية رمز الاقتران');
        }

        // 5. Sign terminal certificate
        $certificate = $this->certAuthority->signCsr(
            csr: $request->csr,
            validityDays: 730, // 2 years
            san: "terminal:{$terminal->serialNumber}, merchant:{$merchant->id}",
        );

        // 6. Store certificate (encrypted at rest)
        $terminal->certificate_sn = $certificate->serialNumber;
        $terminal->certificate_pem = encrypt($certificate->certificatePem);
        $terminal->status = PosTerminalStatus::ACTIVE;
        $terminal->last_paired_at = now();
        $terminal->save();

        // 7. Return certificate + key to terminal (mutual TLS)
        return new PairingResult(
            certificatePem: $certificate->certificatePem,
            privateKey: $certificate->privateKey, // Transmitted once, never stored by server
            caChain: $this->certAuthority->getCaChain(),
        );
    }
}
```

## Payment Link Security

### Expiry & Integrity
```
Payment Link Security:
  1. Default expiry: 24 hours (configurable 30 min — 7 days)
  2. Link ID: UUID v4 (unguessable, 128-bit entropy)
  3. One-time use: Once paid, link shows "مدفوع مسبقاً" (Already Paid)
  4. Amount immutable: Cannot change amount in URL (signed in backend)
  5. Only same-currency: Link created in SYP → must be paid in SYP
  6. Merchant can cancel link at any time (status: cancelled)
  7. Link view tracking: Merchant can see if customer opened link
```

## Webhook Signature

### HMAC-SHA256 Verification
```php
class WebhookSigner
{
    public function signPayload(array $payload, string $secret): string
    {
        $json = json_encode($payload, JSON_UNESCAPED_UNICODE);
        return 'sha256=' . hash_hmac('sha256', $json, $secret);
    }

    public function verifySignature(string $payload, string $signature, string $secret): bool
    {
        $expected = 'sha256=' . hash_hmac('sha256', $payload, $secret);
        return hash_equals($expected, $signature);
    }
}

// Webhook Delivery Headers:
// X-Beza-Signature: sha256=abc123def456...
// X-Beza-Event: payment.completed
// X-Beza-Timestamp: 2026-06-01T10:30:00Z
// X-Beza-Delivery-Attempt: 1

// Merchant's verification code (example in Node.js):
/*
const crypto = require('crypto');
const secret = 'whsec_abc123';

function verifyBezaWebhook(payload, signature, timestamp) {
  const expected = 'sha256=' + crypto
    .createHmac('sha256', secret)
    .update(JSON.stringify(payload))
    .digest('hex');
  
  // Also verify timestamp is within 5 minutes (prevent replay)
  const age = (Date.now() - new Date(timestamp).getTime()) / 1000;
  if (age > 300) return false; // Replay attack
  
  return crypto.timingSafeEqual(
    Buffer.from(expected),
    Buffer.from(signature)
  );
}
*/
```

## Transaction Limits by Merchant Tier

| Limit | Micro | Small | Mid | Enterprise |
|-------|-------|-------|-----|------------|
| Per transaction (min) | 1,000 | 1,000 | 1,000 | 1,000 |
| Per transaction (max) | 200,000 | 1,000,000 | 5,000,000 | 20,000,000 |
| Daily total | 1,000,000 | 5,000,000 | 20,000,000 | 100,000,000 |
| Monthly total | 10,000,000 | 50,000,000 | 200,000,000 | 1,000,000,000 |
| Daily refund count | 3 | 10 | 50 | 200 |
| Refund window | 24h | 48h | 7 days | 30 days |
| Settlement period | Daily | Daily | Daily | Daily or T+0 |

## Sensitive Actions
```
Actions requiring step-up authentication (SMS OTP + admin approval):
  - Change webhook URL
  - Change settlement bank/wallet
  - Request tier upgrade
  - Increase transaction limits
  - Unpair POS terminal
  - Close merchant account
  - Change business name

Actions requiring PIN confirmation:
  - Generate dynamic QR with amount > 500,000 SYP
  - Create payment link > 1,000,000 SYP
  - Full refund > 100,000 SYP
```

## Fraud Prevention Rules
```
Rule M-1: Velocity — No more than 10 payments to same merchant within 15 minutes
Rule M-2: Amount Pattern — No more than 5x merchant's daily average in one txn
Rule M-3: New Merchant — First 24 hours limited to 500,000 SYP total
Rule M-4: Refund Abuse — Merchant refunding > 20% of txns flagged for review
Rule M-5: Round Amounts — Repeated exact amounts (250,000, 500,000) flagged
Rule M-6: Rapid Registration — Same IP/device registering 5+ merchants in 1h
Rule M-7: Self-Payment — Merchant paying own QR code blocked
Rule M-8: QR Scan without Payment — 50+ scans with 0 payments → merchant notified
Rule M-9: POS offline for 30 days → auto-deactivate, re-pairing required
Rule M-10: Webhook URL changes > 3 times in 24h → security hold
```
