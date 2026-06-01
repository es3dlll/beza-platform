# 03 - تدفق البيانات الكامل (Sequence Diagram)

## تسلسل الاستدعاءات الكامل (Complete Call Sequence)

\\\
                    SEQUENCE DIAGRAM - Agent Settlement

  User            Flutter/React           Laravel API            AgentSettlementService         Database
   |                    |                      |                    |              |
   |  Trigger action    |                      |                    |              |
   |------------------->|                      |                    |              |
   |                    |                      |                    |              |
   |  Fill form data    |                      |                    |              |
   |------------------->|                      |                    |              |
   |                    |                      |                    |              |
   |                    |  POST /api/v1/agent/settlement    |                    |              |
   |                    |---------------------->|                    |              |
   |                    |                      |                    |              |
   |                    |  Middleware: auth, throttle               |              |
   |                    |                      |                    |              |
   |                    |  validate(request)   |                    |              |
   |                    |                      |                    |              |
   |                    |  AgentSettlementService::execute()       |                    |              |
   |                    |---------------------->|                    |              |
   |                    |                      |                    |              |
   |                    |  Business validation  |                    |              |
   |                    |                      |                    |              |
   |                    |  DB::transaction {   |                    |              |
   |                    |  INSERT/UPDATE ...   |                    |              |
   |                    |----------------------|------------------->|              |
   |                    |  }                    |                    |              |
   |                    |                      |                    |              |
   |                    |  dispatch(SettlementCompleted)        |                    |              |
   |                    |---------------------->|                    |              |
   |                    |                      |                    |              |
   |                    |  Return response     |                    |              |
   |                    |<----------------------|                    |              |
   |                    |                      |                    |              |
   |  Show success      |                      |                    |              |
   |<-------------------|                      |                    |              |
\\\

## Timing Target: < 200ms p95

| Step | Time | Notes |
|------|------|-------|
| Network Request | ~30ms | Depends on connection |
| Middleware | ~10ms | Auth + Rate Limit |
| Validation | ~5ms | Input validation |
| Business Logic | ~20ms | Service layer checks |
| DB Transaction | ~30ms | Atomic operations |
| Response | ~30ms | JSON serialization |
| Total | ~125ms | Within target |
