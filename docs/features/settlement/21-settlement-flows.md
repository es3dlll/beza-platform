# Settlement-to-External Flows

## Flow 1: Bank Payment File Transmission

### Overview
Settlement payment orders are transmitted to partner banks via their APIs or SFTP drops. The system supports multiple transmission protocols.

### Supported Protocols
| Protocol | Usage | Details |
|----------|-------|---------|
| REST API | Primary | HTTPS POST with JSON/XML payload |
| SFTP | Fallback | Drop CSV/ISO 20022 file, poll for response |
| SWIFT | Cross-border | MT103/MX messages via SWIFT gateway |

### Transmission Flow: REST API
```
PaymentOrderService     BankIntegrationService     Bank API
      │                         │                     │
      │── transmit(order) ─────>│                     │
      │                         │── POST /payments ──>│
      │                         │   {                  │
      │                         │     reference: "PO-001",
      │                         │     amount: 45000000,
      │                         │     currency: "SYP",
      │                         │     account: "BSF-SETTLEMENT",
      │                         │     value_date: "2026-05-29"
      │                         │   }                  │
      │                         │                     │
      │                         │<── 202 Accepted ────│
      │                         │   {                  │
      │                         │     status: "accepted",
      │                         │     bank_reference: "BSF-20260529-8877",
      │                         │     estimated_confirm: "2026-05-29T23:45:00Z"
      │                         │   }                  │
      │                         │                     │
      │<── transmitted ────────│                     │
```

### Transmission Flow: SFTP
```
PaymentOrderService     FileGenerator     SFTP Client      Bank SFTP
      │                     │                 │                │
      │── transmit(order) ─>│                 │                │
      │                     │── build file ──>│                │
      │                     │<── file.csv ───│                │
      │                     │                 │                │
      │                     │── upload ──────────────────────>│
      │                     │                 │<── stored ────│
      │                     │                 │                │
      │                     │── poll(120s) ──────────────────>│
      │                     │                 │<── confirmed ─│
      │<── transmitted ────│                 │                │
```

### Retry Strategy
```php
class PaymentTransmissionRetry
{
    public const MAX_RETRIES = 3;
    public const RETRY_DELAYS = [30, 120, 600]; // seconds

    public function shouldRetry(SettlementPaymentOrder $order): bool
    {
        return $order->retryCount < self::MAX_RETRIES
            && $order->status === PaymentOrderStatus::REJECTED
            && !$order->failureReason === 'invalid_account'; // don't retry fatal errors
    }

    public function getNextDelay(SettlementPaymentOrder $order): int
    {
        return self::RETRY_DELAYS[$order->retryCount] ?? 600;
    }
}
```

## Flow 2: Bank Confirmation Polling

### Overview
After transmitting, the system polls banks for confirmations. Each bank has a defined polling cadence.

### Polling Configuration
```php
'bank_polling' => [
    'bemo_saudi_fransi' => [
        'protocol' => 'api',
        'endpoint' => '/api/v1/payments/status',
        'interval' => 30,          // seconds
        'timeout' => 3600,         // 1 hour max wait
        'method' => 'reference',   // confirm by batch reference
    ],
    'bank_of_syria' => [
        'protocol' => 'sftp',
        'file_pattern' => 'CONF_*.csv',
        'interval' => 120,         // 2 minutes
        'timeout' => 7200,         // 2 hours max wait
    ],
]
```

### Polling Job
```php
class PollBankConfirmationJob
{
    public function handle(): void
    {
        $pendingOrders = PaymentOrder::where('status', PaymentOrderStatus::TRANSMITTED)
            ->where('transmitted_at', '>', now()->subHours(4))
            ->get();

        foreach ($pendingOrders as $order) {
            $config = config("bank_polling.{$order->entity_id}");

            try {
                $result = match($config['protocol']) {
                    'api' => $this->pollApi($order, $config),
                    'sftp' => $this->pollSftp($order, $config),
                };

                if ($result['confirmed']) {
                    app(PaymentOrderService::class)->confirm(
                        $order,
                        $result['amount'],
                        $result['bank_reference'],
                    );
                }
            } catch (\Throwable $e) {
                Log::warning("Bank poll failed for order {$order->id}: {$e->getMessage()}");

                if (now()->diffInMinutes($order->transmitted_at) > $config['timeout'] / 60) {
                    // Timeout — create exception
                    app(ExceptionService::class)->createFromTimeout($order);
                }
            }
        }
    }
}
```

## Flow 3: External File Generation Formats

### CSV Format (MVP)
```
Reference,Amount,Currency,SettlementAccount,ValueDate,Description
PO-001,45000000,SYP,acc_bsf_settlement,2026-05-29,Settlement EOD 2026-05-29
PO-004,11000000,SYP,acc_merch_pool,2026-05-29,Settlement EOD 2026-05-29
```

### ISO 20022 camt.053 (Bank Statement)
```xml
<?xml version="1.0" encoding="UTF-8"?>
<Document xmlns="urn:iso:std:iso:20022:tech:xsd:camt.053.001.08">
  <BkToCstmrStmt>
    <GrpHdr>
      <MsgId>STL-20260529-0001</MsgId>
      <CreDtTm>2026-05-29T23:15:00Z</CreDtTm>
      <NbOfTxs>2</NbOfTxs>
    </GrpHdr>
    <Stmt>
      <Acct>
        <Id><Othr><Id>acc_bsf_settlement</Id></Othr></Id>
      </Acct>
      <Ntry>
        <Amt Ccy="SYP">45000000</Amt>
        <CdtDbtInd>DBIT</CdtDbtInd>
        <Ref>PO-001</Ref>
      </Ntry>
    </Stmt>
  </BkToCstmrStmt>
</Document>
```

### SWIFT MT103 (Cross-border)
```
{1:F01BEZASYSAXXX1234567890}
{2:O1031200052905BSFRSYSAXXX12345678900529051200N}
{3:{108:PO-001}}
{4:
:20:STL-20260529-0001
:23B:CRED
:32A:260529SYP45000000,
:50K:/acc_bsf_settlement
Beza Financial Services
Damascus, Syria
:59:/BSF-ACCOUNT-001
Bemo Saudi Fransi
Damascus, Syria
:71A:OUR
:72:/REC/Batch settlement EOD 2026-05-29
-}
```
