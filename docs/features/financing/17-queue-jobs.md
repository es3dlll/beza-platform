# قوائم الانتظار والمهام المجدولة — Queue & Job Scheduling

## Architecture
Job queue powered by **Apache Kafka** for event streaming + **Bull** (Redis-backed) for delayed/scheduled jobs.

## Topics & Consumers

### 1. Kafka Topics
| Topic | Partitions | Retention | Description |
|-------|------------|-----------|-------------|
| financing.application.submitted | 10 | 7 days | New application submitted |
| financing.scoring.completed | 10 | 7 days | Credit score calculated |
| financing.application.decided | 10 | 30 days | Application approved/rejected |
| financing.offer.accepted | 5 | 30 days | User accepted offer |
| financing.disbursement.initiated | 5 | 30 days | Disbursement started |
| financing.disbursement.completed | 5 | 30 days | Disbursement succeeded |
| financing.payment.received | 10 | 90 days | Payment recorded |
| financing.payment.overdue | 10 | 90 days | Payment overdue |
| financing.default.triggered | 5 | 365 days | Default declared |
| financing.restructure.requested | 5 | 90 days | Restructure requested |

### 2. Bull Queues (Scheduled Jobs)
```typescript
interface JobQueueConfig {
  name: string;
  concurrency: number;
  attempts: number;
  backoff: { type: 'exponential', delay: number };
}
```

| Queue Name | Concurrency | Description |
|------------|-------------|-------------|
| scoring-queue | 10 | Process credit scoring |
| disbursement-queue | 5 | Execute disbursements |
| repayment-queue | 20 | Process auto-deductions |
| collection-queue | 15 | Handle collection escalations |
| notification-queue | 30 | Send notifications |
| contract-generation-queue | 5 | Generate contract PDFs |
| charity-disbursement-queue | 2 | Quarterly charity disbursement |

## Scheduled Cron Jobs

### Daily Jobs (07:00 Asia/Damascus)
```typescript
// Process due payments
cron.schedule('0 7 * * *', async () => {
  const dueToday = await RepaymentService.getDueToday();
  for (const payment of dueToday) {
    await repaymentQueue.add('auto-deduct', { contractId: payment.contractId, installmentNumber: payment.installmentNumber });
  }
});

// Retry failed payments
cron.schedule('0 9,13,17 * * *', async () => {
  const failedPayments = await RepaymentService.getFailedForRetry();
  for (const payment of failedPayments) {
    await repaymentQueue.add('retry-deduct', { contractId: payment.contractId, installmentNumber: payment.installmentNumber, retryCount: payment.retryCount + 1 });
  }
});

// Check for new defaults (90 days overdue)
cron.schedule('0 6 * * *', async () => {
  const defaultCandidates = await CollectionService.getDefaultCandidates();
  for (const contract of defaultCandidates) {
    await CollectionService.markDefault(contract.id);
    await kafkaProducer.send('financing.default.triggered', { contractId: contract.id });
  }
});
```

### Weekly Jobs (Sunday 04:00)
```typescript
// Generate collection priority list
cron.schedule('0 4 * * 0', async () => {
  await CollectionService.generatePriorityQueue();
});

// Score model evaluation
cron.schedule('0 4 * * 0', async () => {
  await ScoringService.runModelEvaluation();
});
```

### Monthly Jobs (1st of Month 03:00)
```typescript
// Monthly portfolio report
cron.schedule('0 3 1 * *', async () => {
  await ReportService.generateMonthlyPortfolioReport();
});

// Provision calculation
cron.schedule('0 3 1 * *', async () => {
  await ProvisioningService.calculateAndBookProvision();
});

// Credit score model retraining
cron.schedule('0 3 1 * *', async () => {
  await MLPipelineService.retrainModel();
});
```

### Quarterly Jobs (Month 3,6,9,12 — Day 15)
```typescript
// Charity fee disbursement
cron.schedule('0 10 15 3,6,9,12 *', async () => {
  await CharityService.disburseQuarterlyFees();
});
```

## Job Processing Flow

### Auto-Deduction Job
```
1. repaymentQueue: 'auto-deduct'
2. Fetch contract + installment details
3. Check user wallet balance
4. If balance >= installment:
   a. Debit wallet (via CFE)
   b. Mark installment as 'paid'
   c. Update contract progress
   d. Send success notification
   e. Emit 'financing.payment.received'
5. If balance < installment:
   a. Mark installment as 'overdue'
   b. Calculate and record late fee → charity
   c. Schedule retry (+2 hours)
   d. Send insufficient balance notification
   e. Emit 'financing.payment.overdue'
```

### Scoring Job
```
1. scoringQueue: 'calculate-score'
2. Fetch user wallet history (12 months)
3. Feature engineering (80+ features)
4. Load XGBoost model
5. Predict score
6. Store score in financing_credit_scores
7. Emit 'financing.scoring.completed'
8. If score available: trigger decision engine
```

## Error Handling & Dead Letter Queue
```typescript
// Failed after max attempts → DLQ
const dlq = new Queue('financing-dlq', {
  defaultJobOptions: {
    attempts: 3,
    backoff: { type: 'exponential', delay: 60000 }
  }
});

// DLQ Processor
dlq.process(async (job) => {
  logger.error(`[DLQ] Job ${job.name} failed after 3 attempts`, {
    originalQueue: job.data.originalQueue,
    data: job.data,
    failedReason: job.failedReason
  });
  
  // Notify admin
  await NotificationService.sendAdminAlert(`Job failed: ${job.name}`, job.data);
});
```

## Monitoring
- **Prometheus metrics**: Queue depth, processing time, success/failure rate per queue
- **Grafana dashboard**: Real-time queue visualization
- **Alerting**: Queue depth > 1000, processing time > 30s, failure rate > 5%
