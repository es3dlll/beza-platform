# Routing & Navigation

## Route Structure

```
/[locale]/
├─ (ngo)/                              // NGO staff dashboard
│   ├─ /dashboard                      // Overview: active programs, pending actions
│   ├─ /programs                       // List all programs
│   ├─ /programs/new                   // Create program wizard
│   ├─ /programs/[id]                  // Program detail
│   ├─ /programs/[id]/beneficiaries    // Beneficiary management
│   ├─ /programs/[id]/distributions    // Distribution history
│   ├─ /programs/[id]/spending         // MPC spending dashboard
│   ├─ /beneficiaries                  // Cross-program beneficiary search
│   ├─ /distributions                  // All distributions (cross-program)
│   ├─ /vouchers                       // Voucher program management
│   ├─ /vouchers/create                // Create voucher program
│   ├─ /reports                        // Report center
│   └─ /compliance                     // Sanctions screening, audit log
│
├─ (agent)/                            // Field agent mobile app
│   ├─ /agent/login                    // Agent authentication
│   ├─ /agent/verify                   // Beneficiary verification flow
│   ├─ /agent/history                  // Recent verifications
│   └─ /agent/sync-status              // Offline queue status
│
├─ (merchant)/                         // Partner merchant mobile app
│   ├─ /merchant/login                 // Merchant authentication
│   ├─ /merchant/redeem                // Voucher redemption
│   ├─ /merchant/settlements           // Settlement history
│   └─ /merchant/transactions          // Recent transactions
│
└─ (donor)/                            // Donor portal
    ├─ /donor/login                    // Donor authentication
    ├─ /donor/dashboard                // Cross-program donor overview
    ├─ /donor/reports                  // Report generation
    └─ /donor/reports/[id]             // Saved report detail
```

## Navigation Guards

| Route | Guard | Redirect If |
|-------|-------|-------------|
| `(ngo)/*` | `requireAuth('ngo_admin' or 'ngo_staff')` | Not authenticated / wrong role |
| `(agent)/*` | `requireAuth('agent')` | Not authenticated |
| `(merchant)/*` | `requireAuth('merchant')` | Not authenticated |
| `(donor)/*` | `requireAuth('donor')` | Not authenticated |
| `programs/new` | `requirePermission('program:create')` | Insufficient permissions |
| `compliance/*` | `requireRole('ngo_admin', 'compliance_officer')` | Insufficient role |

## Breadcrumbs (Arabic RTL)
Breadcrumbs follow Arabic hierarchy: Home ← Programs ← {Program Name}
Implemented as a reusable `Breadcrumbs` component with RTL-aware separator (← in Arabic, → in English).

## Internationalised Routing
Routes are prefixed by locale: `/ar/ngo/programs`, `/en/ngo/programs`. The `[locale]` segment is:
- **Default:** `ar` (Arabic)
- **Available:** `ar`, `en`
- **Detection:** `Accept-Language` header → browser preference → Arabic default
