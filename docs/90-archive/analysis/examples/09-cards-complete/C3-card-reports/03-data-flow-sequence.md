# 03 - تدفق البيانات (Data Flow Sequence) - تقارير البطاقة

## تسلسل الاستدعاءات الكامل (Complete Call Sequence)

\\\
                    SEQUENCE DIAGRAM - Card Reports

  User            Flutter/React           Laravel API            CardReportService         Database
   |                    |                      |                    |              |
   |  Trigger action    |                      |                    |              |
   |------------------->|                      |                    |              |
   |                    |                      |                    |              |
   |  Fill form data    |                      |                    |              |
   |------------------->|                      |                    |              |
   |                    |                      |                    |              |
   |                    |  GET /api/v1/cards/{id}/transactions    |                    |              |
   |                    |---------------------->|                    |              |
   |                    |                      |                    |              |
   |                    |  Middleware: auth, throttle               |              |
   |                    |                      |                    |              |
   |                    |  validate(request)   |                    |              |
   |                    |                      |                    |              |
   |                    |  CardReportService::execute()       |                    |              |
   |                    |---------------------->|                    |              |
   |                    |                      |                    |              |
   |                    |  Business validation  |                    |              |
   |                    |                      |                    |              |
   |                    |  DB::transaction {   |                    |              |
   |                    |  INSERT/UPDATE ...   |                    |              |
   |                    |----------------------|------------------->|              |
   |                    |  }                    |                    |              |
   |                    |                      |                    |              |
   |                    |  dispatch(none)        |                    |              |
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
