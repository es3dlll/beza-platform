# Agent Network Event Architecture

## Events Produced

### AgentRegistered
```json
{
  "specversion": "1.0",
  "id": "evt_agent_reg_abc123",
  "source": "/beza/agent/1.0",
  "type": "com.beza.agent.registered",
  "datacontenttype": "application/json",
  "subject": "agent_10234",
  "time": "2026-06-01T09:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "agent_id": 10234,
    "agent_code": "BZ-10234",
    "full_name": "محمد أحمد",
    "phone": "+963933123456",
    "shop_name": "بقالة أبو محمد",
    "shop_type": "grocery",
    "city": "دمشق",
    "district": "المزة",
    "location": {"lat": 33.5138, "lng": 36.2765},
    "status": "pending",
    "documents_uploaded": ["national_id", "shop_registration", "proof_of_address"],
    "registered_at": "2026-06-01T09:00:00Z"
  }
}
```
**Consumers**: Compliance (KYC queue), Analytics, AgentOnboarding (notification to ops team), CRM (agent record created)

### AgentApproved
```json
{
  "specversion": "1.0",
  "id": "evt_agent_appr_def456",
  "source": "/beza/agent/1.0",
  "type": "com.beza.agent.approved",
  "datacontenttype": "application/json",
  "subject": "agent_10234",
  "time": "2026-06-02T10:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "agent_id": 10234,
    "agent_code": "BZ-10234",
    "full_name": "محمد أحمد",
    "phone": "+963933123456",
    "approved_by": 5,
    "tier": "bronze",
    "device_assigned": {
      "serial": "DEVICE-SN-ABC123",
      "model": "Samsung Galaxy Tab A9"
    },
    "initial_float": 500000,
    "approved_at": "2026-06-02T10:00:00Z"
  }
}
```
**Consumers**: Notification (SMS to agent: "تم قبول طلبك"), Analytics, DeviceManagement, AgentOnboarding (training scheduling)

### AgentSuspended
```json
{
  "specversion": "1.0",
  "id": "evt_agent_susp_ghi789",
  "source": "/beza/agent/1.0",
  "type": "com.beza.agent.suspended",
  "datacontenttype": "application/json",
  "subject": "agent_10234",
  "time": "2026-06-15T14:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "agent_id": 10234,
    "agent_code": "BZ-10234",
    "full_name": "محمد أحمد",
    "reason": "float_discrepancy",
    "reason_detail": "Float mismatch > 500,000 SYP for 3 consecutive days",
    "suspended_by": 5,
    "previous_status": "active",
    "suspended_at": "2026-06-15T14:00:00Z",
    "notifications_sent": {
      "sms": true,
      "in_app": true
    }
  }
}
```
**Consumers**: Notification (agent + ops), Compliance, FraudDetection, FloatManagement (freeze float)

### AgentCashInCompleted
```json
{
  "specversion": "1.0",
  "id": "evt_cash_in_abc123",
  "source": "/beza/agent/1.0",
  "type": "com.beza.agent.cash_in.completed",
  "datacontenttype": "application/json",
  "subject": "agent_10234",
  "time": "2026-06-01T10:30:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "transaction_id": "CI-20260601-87142",
    "agent_id": 10234,
    "agent_code": "BZ-10234",
    "customer_phone": "+963912345678",
    "customer_wallet_id": 42,
    "amount": 100000,
    "currency": "SYP",
    "fee": 0,
    "commission_earned": 500,
    "agent_float_before": 1000000,
    "agent_float_after": 900000,
    "customer_balance_before": 150000,
    "customer_balance_after": 250000,
    "device_id": "DEVICE-SN-ABC123",
    "location": {"lat": 33.5138, "lng": 36.2765},
    "offline": false,
    "created_at": "2026-06-01T10:30:00Z"
  }
}
```
**Consumers**: Notification (SMS to customer "تم إيداع 100,000 ل.س"), Analytics, FloatManagement (update balance), Compliance (AML check), CommissionService (accrue commission), WalletService (credit customer)

### AgentCashOutCompleted
```json
{
  "specversion": "1.0",
  "id": "evt_cash_out_def456",
  "source": "/beza/agent/1.0",
  "type": "com.beza.agent.cash_out.completed",
  "datacontenttype": "application/json",
  "subject": "agent_10234",
  "time": "2026-06-01T11:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "transaction_id": "CO-20260601-45231",
    "agent_id": 10234,
    "agent_code": "BZ-10234",
    "customer_phone": "+963912345678",
    "customer_wallet_id": 42,
    "amount": 50000,
    "fee": 750,
    "total_deducted": 50750,
    "currency": "SYP",
    "commission_earned": 375,
    "agent_float_before": 900000,
    "agent_float_after": 950000,
    "customer_balance_before": 250000,
    "customer_balance_after": 199250,
    "verification_method": "pin",
    "biometric_verified": false,
    "device_id": "DEVICE-SN-ABC123",
    "location": {"lat": 33.5138, "lng": 36.2765},
    "offline": false,
    "created_at": "2026-06-01T11:00:00Z"
  }
}
```
**Consumers**: Notification (SMS to customer "تم سحب 50,000 ل.س"), Analytics, FloatManagement, Compliance (AML), CommissionService, WalletService (debit customer)

### AgentFloatLow
```json
{
  "specversion": "1.0",
  "id": "evt_float_low_ghi789",
  "source": "/beza/agent/1.0",
  "type": "com.beza.agent.float.low",
  "datacontenttype": "application/json",
  "subject": "agent_10234",
  "time": "2026-06-01T10:35:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "agent_id": 10234,
    "agent_code": "BZ-10234",
    "full_name": "محمد أحمد",
    "phone": "+963933123456",
    "current_balance": 50000,
    "low_threshold": 100000,
    "critical_threshold": 50000,
    "is_critical": true,
    "daily_cash_in_volume": 1500000,
    "daily_cash_out_count": 8,
    "recommended_top_up": 500000,
    "nearest_agents_with_surplus": [
      {"agent_id": 10235, "distance_km": 0.3, "surplus_estimate": 2000000}
    ],
    "created_at": "2026-06-01T10:35:00Z"
  }
}
```
**Consumers**: Notification (SMS + in-app to agent "⚠️ رصيد الصندوق منخفض"), Operations (monitoring dashboard), FloatManagement (trigger alert), ML (float prediction model input)

### CommissionEarned
```json
{
  "specversion": "1.0",
  "id": "evt_comm_earn_abc123",
  "source": "/beza/agent/1.0",
  "type": "com.beza.agent.commission.earned",
  "datacontenttype": "application/json",
  "subject": "agent_10234",
  "time": "2026-06-01T10:30:01Z",
  "tenant_id": "tenant_1",
  "data": {
    "agent_id": 10234,
    "transaction_id": "CI-20260601-87142",
    "transaction_type": "cash_in",
    "amount": 100000,
    "commission": 500,
    "rate_applied": 0.005,
    "tier": "bronze",
    "pending_commission_total": 12500,
    "created_at": "2026-06-01T10:30:01Z"
  }
}
```
**Consumers**: Analytics, CommissionService (update pending), Notification (in-app "💰 ربحت 500 ل.س"), AgentPerformance

### CommissionSettled
```json
{
  "specversion": "1.0",
  "id": "evt_comm_settle_def456",
  "source": "/beza/agent/1.0",
  "type": "com.beza.agent.commission.settled",
  "datacontenttype": "application/json",
  "subject": "agent_10234",
  "time": "2026-06-02T03:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "settlement_id": 1,
    "batch_reference": "SET-20260601",
    "agent_id": 10234,
    "settled_date": "2026-06-01",
    "amount": 42500,
    "transaction_count": 25,
    "destination_wallet_id": 42,
    "wallet_balance_after": 1042500,
    "settled_at": "2026-06-02T03:00:00Z"
  }
}
```
**Consumers**: Notification (SMS to agent "تم تسوية عمولاتك: 42,500 ل.س"), Analytics, Finance (journal entry)

### AgentOfflineTransactionSynced
```json
{
  "specversion": "1.0",
  "id": "evt_offline_sync_abc123",
  "source": "/beza/agent/1.0",
  "type": "com.beza.agent.offline_transaction_synced",
  "datacontenttype": "application/json",
  "subject": "agent_10234",
  "time": "2026-06-01T12:00:00Z",
  "tenant_id": "tenant_1",
  "data": {
    "agent_id": 10234,
    "device_id": "DEVICE-SN-ABC123",
    "batch_size": 5,
    "successful": 4,
    "failed": 1,
    "failed_transactions": [
      {
        "idempotency_key": "uuid-3",
        "reason": "customer_not_found",
        "performed_at": "2026-06-01T11:30:00Z"
      }
    ],
    "online_since": "2026-06-01T11:55:00Z",
    "offline_duration_minutes": 25,
    "synced_at": "2026-06-01T12:00:00Z"
  }
}
```
**Consumers**: Analytics (offline metrics), Monitoring (device health), Operations (follow-up on failures)

## Event Flow Diagram
```
CashInController::execute()
    │
    ▼
ExecuteCashInAction::handle()
    │
    ├── Validate (agent active, float sufficient, limits)
    ├── Verify customer (SMS code)
    ├── Debit agent float → Credit customer wallet
    ├── Accrue commission
    │
    ├── emit(AgentCashInCompleted) ─────────────────────────────┐
    │    ├── Queue: SendCashInNotification (SMS to customer)    │
    │    ├── Queue: AccrueCommission (credit pending)           │
    │    ├── Queue: AnalyticsIngestion                          │
    │    ├── Queue: AMLScreening (if >1M SYP)                  │
    │    └── Queue: FloatAlertCheck (if now low)                │
    │                                                           │
    └── emit(CommissionEarned) ─────────────────────────────────┤
         ├── Queue: UpdateAgentPendingCommission                │
         ├── Queue: AnalyticsIngestion                          │
         └── Queue: AgentPerformanceUpdate                      │

Daily (03:00 AM) — SettleCommissionsJob
    │
    ▼
SettleCommissionAction::handle()
    │
    ├── Query: all unsettled commissions from yesterday
    ├── Create settlement batch
    ├── For each agent: credit wallet, mark settled
    │
    └── emit(CommissionSettled) × N agents ─────────────────────┐
         ├── Queue: SendCommissionSettlementNotification (SMS)  │
         ├── Queue: FinanceLedgerEntry                          │
         └── Queue: AnalyticsIngestion                          │

Float Monitor (continuous)
    │
    ▼
CheckLowFloats command (every 5 min)
    │
    ├── Query: agents with float < threshold
    │
    └── emit(AgentFloatLow or AgentFloatCritical) ──────────────┐
         ├── Queue: SendFloatAlert (SMS + in-app)               │
         ├── Queue: OperationsDashboardAlert                    │
         └── Queue: MLFloatPrediction (feature input)           │
```
