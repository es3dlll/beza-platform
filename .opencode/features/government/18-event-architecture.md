# Government Collections Event Architecture

## Event Catalog

| Event | Emitted By | Payload | Subscribers |
|-------|-----------|---------|-------------|
| `GovernmentPaymentInitiated` | PayTaxAction / PayFineAction / etc. | transaction_id, user_id, biller_code, amount, service_type | LogPaymentForAnalytics, StartPaymentTimeout |
| `GovernmentPaymentCompleted` | GovPaymentGatewayService | transaction_id, receipt_ref, biller_code, amount, ministry_reference | SendPaymentConfirmationNotification, UpdateReconciliationStatus, NotifyMinistryOfPayment |
| `GovernmentPaymentFailed` | GovPaymentGatewayService | transaction_id, biller_code, amount, failure_code, failure_reason, retry_allowed | SendPaymentFailedNotification, LogPaymentForAnalytics |
| `GovernmentReceiptGenerated` | InvoiceService | receipt_ref, transaction_id, receipt_hash, qr_data, pdf_path | TrackReceiptGenerated, NotifyReceiptAvailable |
| `GovernmentPaymentRefunded` | ReconciliationService | transaction_id, receipt_ref, refund_amount, reason | SendRefundNotification, UpdateReconciliationStatus |
| `GovernmentReconciliationCompleted` | ReconciliationService | reconciliation_id, biller_code, period_start, period_end, variance, mismatched_count | NotifyReconciliationResult, GenerateReconciliationReport |
| `SettlementToMinistryCompleted` | ProcessMinistrySettlement | biller_code, batch_id, total_amount, transaction_count, settled_at | UpdateTransactionSettlementStatus, NotifyMinistrySettlement |
| `MinistryStatusSyncCompleted` | SyncMinistryStatuses | biller_code, synced_count, updated_count, failed_count | TrackSyncMetrics |
| `GovernmentDeadlineApproaching` | NotifyUpcomingDeadline | user_id, service_type, biller_reference, amount_due, deadline, days_remaining | SendDeadlinePushNotification, SendDeadlineSms |
| `GovernmentPayerSaved` | SavePayerUseCase | user_id, service_type, reference_id, label | UpdateAnalytics |

## Event Payload Schema

```php
// GovernmentPaymentCompleted
[
    'event' => 'government.payment.completed',
    'version' => 1,
    'timestamp' => '2025-08-15T10:23:45Z',
    'data' => [
        'transaction_id' => 'gov_txn_abc123',
        'user_id' => 42,
        'biller' => [
            'code' => 'MOF',
            'name_ar' => 'وزارة المالية',
        ],
        'service_type' => 'tax_income',
        'amount' => 262500,
        'beza_fee' => 1312,
        'total_charged' => 263812,
        'currency' => 'SYP',
        'biller_reference' => '2536894751',         // Tax ID
        'ministry_reference' => 'MOF-CONF-7823',
        'receipt_ref' => 'GOV-2025-0815-7823',
        'paid_at' => '2025-08-15T10:23:45Z',
    ]
]

// GovernmentPaymentFailed
[
    'event' => 'government.payment.failed',
    'version' => 1,
    'timestamp' => '2025-08-15T10:24:00Z',
    'data' => [
        'transaction_id' => 'gov_txn_def456',
        'user_id' => 42,
        'biller' => [
            'code' => 'MOF',
            'name_ar' => 'وزارة المالية',
        ],
        'service_type' => 'tax_income',
        'amount' => 262500,
        'failure_code' => 'MINISTRY_TIMEOUT',
        'failure_reason' => 'وزارة المالية لم تستجب خلال المهلة المحددة',
        'retry_allowed' => true,
        'retry_count' => 1,
        'max_retries' => 3,
    ]
]

// GovernmentReconciliationCompleted
[
    'event' => 'government.reconciliation.completed',
    'version' => 1,
    'timestamp' => '2025-08-16T02:00:00Z',
    'data' => [
        'reconciliation_id' => 'rec_20250815',
        'biller_code' => 'MOF',
        'period_start' => '2025-08-14',
        'period_end' => '2025-08-15',
        'beza_total' => 5250000,
        'ministry_total' => 5248000,
        'variance' => 2000,
        'variance_pct' => 0.038,
        'beza_count' => 20,
        'ministry_count' => 19,
        'matched_count' => 18,
        'mismatched_count' => 2,
        'missing_from_ministry' => 1,
        'auto_resolved' => true,
        'status' => 'has_mismatches',
    ]
]
```
