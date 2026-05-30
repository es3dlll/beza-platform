# 07 — Non-Functional Requirements

---

## Performance

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-01 | Batch processing throughput | ≤ 5 seconds per 1,000 employees |
| NFR-02 | CSV validation | ≤ 2 seconds for 5 MB file |
| NFR-03 | API response time (p95) | ≤ 500 ms |
| NFR-04 | Payslip generation | ≤ 1 second per employee |
| NFR-05 | Dashboard page load | ≤ 3 seconds (including data fetch) |
| NFR-06 | Concurrent batches | Support 50 companies running simultaneously |

## Availability

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-07 | Uptime (core payroll API) | 99.9 % (8.76 hrs downtime/year) |
| NFR-08 | Uptime (dashboard) | 99.5 % |
| NFR-09 | Planned maintenance window | Sunday 02:00–04:00 (lowest usage) |
| NFR-10 | Batch processing during maintenance | Queue and resume; no data loss |

## Security

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-11 | Batch confirmation requires 2FA (OTP or PIN) | Mandatory |
| NFR-12 | All API calls over mTLS | Mandatory |
| NFR-13 | Payslip PDFs encrypted at rest | AES-256 |
| NFR-14 | Audit log — all batch operations immutable | Append-only DB |

## Compliance

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-15 | Data residency — all data stored in Syria (Damascus) | Mandatory |
| NFR-16 | Sharia compliance review for settlement periods | Certificate from Sharia board |
| NFR-17 | Records retention: 7 years (Syrian labour law) | Archival system |
| NFR-18 | AML screening on all companies onboarding | Sanctions list check |

## Scalability

| ID | Requirement | Target |
|----|-------------|--------|
| NFR-19 | Max employees per company | 5,000 (Phase 1: 500) |
| NFR-20 | Max batch amount | SYP 500,000,000 per single batch |
| NFR-21 | Storage for payslips | 50 GB/month — auto-archive after 12 months |
