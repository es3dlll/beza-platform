# 03 - تسلسل تدفق البيانات (Data Flow Sequence)

**الرمز التشغيلي:** SY2-health  
**النوع:** مخطط تسلسلي (Sequence Diagram)

---

## السيناريو الرئيسي: طلب تحقق صحي عام (Main Scenario: General Health Check)

```
Client                  Router          HealthController       HealthService          Cache         DatabaseChecker    RedisChecker
  │                        │                    │                    │                  │                │                │
  │  GET /system/health    │                    │                    │                  │                │                │
  │───────────────────────►│                    │                    │                  │                │                │
  │                        │  Dispatch Request  │                    │                  │                │                │
  │                        │───────────────────►│                    │                  │                │                │
  │                        │                    │                    │                  │                │                │
  │                        │                    │  index()           │                  │                │                │
  │                        │                    │───────────────────►│                  │                │                │
  │                        │                    │                    │                  │                │                │
  │                        │                    │                    │  has('health:all')│                │                │
  │                        │                    │                    │─────────────────►│                │                │
  │                        │                    │                    │                  │                │                │
  │                        │                    │                    │    ──[found]──    │                │                │
  │                        │                    │                    │◄─────────────────│                │                │
  │                        │                    │                    │                  │                │                │
  │                        │                    │                    │  return cached    │                │                │
  │                        │                    │◄───────────────────│                  │                │                │
  │                        │                    │                    │                  │                │                │
  │  JSON Response (cached)│                    │                    │                  │                │                │
  │◄───────────────────────│◄───────────────────│◄───────────────────│                  │                │                │
  │                        │                    │                    │                  │                │                │

  ── OR ── cache missed ──

Client                  Router          HealthController       HealthService         Cache         DatabaseChecker    RedisChecker
  │                        │                    │                    │                  │                │                │
  │                        │                    │                    │  has('health:all')│                │                │
  │                        │                    │                    │─────────────────►│                │                │
  │                        │                    │                    │                  │                │                │
  │                        │                    │                    │    ──[miss]──     │                │                │
  │                        │                    │                    │◄─────────────────│                │                │
  │                        │                    │                    │                  │                │                │
  │                        │                    │                    │  checkDatabase()│                │                │
  │                        │                    │                    │─────────────────────────────────►│                │
  │                        │                    │                    │                  │                │                │
  │                        │                    │                    │   DB::select('SELECT 1')         │                │
  │                        │                    │                    │◄─────────────────────────────────│                │
  │                        │                    │                    │                  │                │                │
  │                        │                    │                    │  checkRedis()   │                │                │
  │                        │                    │                    │─────────────────────────────────────────────────►
  │                        │                    │                    │                  │                │                │
  │                        │                    │                    │  Redis::ping()   │                │                │
  │                        │                    │                    │◄─────────────────────────────────────────────────│
  │                        │                    │                    │                  │                │                │
  │                        │                    │                    │  نتائج متعددة ──►│                │                │
  │                        │                    │                    │  Cache::put()    │                │                │
  │                        │                    │                    │─────────────────►│                │                │
  │                        │                    │                    │  ttl:30s         │                │                │
  │                        │                    │                    │                  │                │                │
  │                        │                    │                    │  return results  │                │                │
  │                        │                    │◄───────────────────│                  │                │                │
  │                        │                    │                    │                  │                │                │
  │  JSON Response         │                    │                    │                  │                │                │
  │◄───────────────────────│◄───────────────────│◄───────────────────│                  │                │                │
```

---

## السيناريو الثاني: لوحة المشرف (Admin Dashboard Scenario)

```
Client (Admin)         Router            HealthController       HealthService          StorageChecker    PHP Functions
  │                      │                    │                    │                      │                 │
  │  GET /admin/health   │                    │                    │                      │                 │
  │─────────────────────►│                    │                    │                      │                 │
  │                      │  auth:api + role  │                    │                      │                 │
  │                      │───────────────────►│                    │                      │                 │
  │                      │                    │  adminDashboard()  │                      │                 │
  │                      │                    │───────────────────►│                      │                 │
  │                      │                    │                    │                      │                 │
  │                      │                    │                    │  checkStorage()      │                 │
  │                      │                    │                    │─────────────────────►│                 │
  │                      │                    │                    │                      │                 │
  │                      │                    │                    │  is_writable()      │                 │
  │                      │                    │                    │─────────────────────────────────────►│
  │                      │                    │                    │◄─────────────────────────────────────│
  │                      │                    │                    │                      │                 │
  │                      │                    │                    │  disk_free_space()  │                 │
  │                      │                    │                    │─────────────────────────────────────►│
  │                      │                    │                    │◄─────────────────────────────────────│
  │                      │                    │                    │                      │                 │
  │                      │                    │                    │  memory_get_usage() │                 │
  │                      │                    │                    │─────────────────────────────────────►│
  │                      │                    │                    │◄─────────────────────────────────────│
  │                      │                    │                    │                      │                 │
  │                      │                    │◄───────────────────│                      │                 │
  │                      │                    │                    │                      │                 │
  │  تفاصيل كاملة        │                    │                    │                      │                 │
  │  (query_time, disk,  │                    │                    │                      │                 │
  │   memory, uptime)    │                    │                    │                      │                 │
  │◄─────────────────────│◄───────────────────│◄───────────────────│                      │                 │
```

---

## السيناريو الثالث: Redis معطل (Redis Down Scenario)

```
Client                  HealthService          RedisChecker          Redis
  │                        │                      │                  │
  │  GET /system/health    │                      │                  │
  │───────────────────────►│                      │                  │
  │                        │  checkRedis()        │                  │
  │                        │─────────────────────►│                  │
  │                        │                      │  Redis::ping()   │
  │                        │                      │─────────────────►│
  │                        │                      │                  │
  │                        │                      │  !! Exception    │
  │                        │                      │◄─────────────────│
  │                        │                      │                  │
  │                        │                      │  return [        │
  │                        │                      │    status:'down',│
  │                        │                      │    error:'...'   │
  │                        │                      │  ]               │
  │                        │◄─────────────────────│                  │
  │                        │                      │                  │
  │                        │  total status:       │                  │
  │                        │  'degraded'          │                  │
  │                        │                      │                  │
  │  JSON Response         │                      │                  │
  │◄───────────────────────│                      │                  │
  │  status: degraded      │                      │                  │
  │  Redis: down           │                      │                  │
```

---

## قواعد التخزين المؤقت (Caching Rules)

```
طلب عام (index) ──► تخزين في الكاش لمدة 30 ثانية
طلب فردي (db)   ──► لا يتم تخزينه (كل طلب فحص جديد)
طلب مشرف (admin) ──► لا يتم تخزينه (يحتوي بيانات حساسة)
```

---

## تنسيق الاستجابة النهائية (Final Response Format)

```json
{
    "status": "ok",
    "services": [
        {"name": "database",  "status": "up",  "latency_ms": 2.34, "details": {"server": "MySQL 8.0"}},
        {"name": "redis",     "status": "up",  "latency_ms": 1.20, "details": {}},
        {"name": "cache",     "status": "up",  "latency_ms": 0.50, "details": {}},
        {"name": "queue",     "status": "up",  "latency_ms": 0.00, "details": {"driver": "redis"}},
        {"name": "storage",   "status": "up",  "latency_ms": 0.00, "details": {"writable": true}},
        {"name": "php",       "status": "up",  "latency_ms": 0.00, "details": {"version": "8.2.0", "extensions": ["pdo", "mbstring", ...]}}
    ],
    "timestamp": "2026-05-27T10:30:00Z",
    "cached": false
}
```
