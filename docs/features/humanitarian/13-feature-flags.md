# Feature Flags & Phased Rollout

## Flag Configuration

| Flag | Description | Default | Phase |
|------|-------------|---------|-------|
| `humanitarian.enabled` | Master toggle for entire humanitarian module | `false` | — |
| `humanitarian.mpc` | Multi-purpose cash distribution | `false` | Phase 1 |
| `humanitarian.vouchers` | E-voucher programs | `false` | Phase 2 |
| `humanitarian.conditional_cash` | Conditional cash transfers (education, health) | `false` | Phase 3 |
| `humanitarian.biometric_verification` | Fingerprint + face verification | `false` | Phase 1 |
| `humanitarian.offline_agent` | Offline verification queue for agents | `false` | Phase 1 |
| `humanitarian.spending_monitoring` | MPC spending analytics dashboard | `false` | Phase 1 |
| `humanitarian.donor_reports` | Automated donor report generation | `false` | Phase 2 |
| `humanitarian.sanctions_screening` | UN/EU/OFAC sanctions screening | `false` | Phase 1 |
| `humanitarian.self_service_portal` | Beneficiary self-service (check balance, view history) | `false` | Phase 4 |

## Rollout Phases

### Phase 1 — Core MPC (Month 1-2)
| Feature | NGO Pilot Partners |
|---------|-------------------|
| MPC program creation | Syrian Arab Red Crescent (Damascus pilot) |
| CSV beneficiary upload | 10,000 beneficiaries |
| Sanctions screening | Automated against OFAC SDN |
| Biometric verification | Fingerprint at 20 agent points |
| Batch distribution | Up to 50,000 per batch |
| Basic SMS notification | Cash credited alert |

### Phase 2 — Vouchers & Reporting (Month 3-4)
| Feature | NGO Pilot Partners |
|---------|-------------------|
| E-voucher programs | WFP food voucher pilot (Aleppo, Idlib) |
| Merchant settlement | 200 partner merchants onboarded |
| MPC spending dashboard | UNHCR monitoring team |
| Donor reports | ECHO quarterly report automation |
| Conditional cash (basic) | UNICEF education cash (100 schools) |

### Phase 3 — Scale & Conditional (Month 5-6)
| Feature | Reach |
|---------|-------|
| Scale to 500k beneficiaries | All governorates |
| Conditional cash (full) | Health, education, winterisation |
| Cross-program beneficiary management | Beneficiary enrolled in multiple programs |
| Advanced fraud detection | ML-based duplicate detection |
| API access for NGO systems | Integration with NGO MIS (ActivityInfo, RedRose) |

### Phase 4 — Beneficiary Self-Service (Month 7+)
- Self-service portal (USSD + web) for beneficiaries to check balance, view spending, update profile
- Community feedback mechanism (rating system for merchants)
- Integration with broader Syria humanitarian cluster coordination

## Implementation Pattern

```typescript
// Feature flag service
class HumanitarianFeatureFlags {
  isEnabled(feature: HumanitarianFeature, ngoId: string): boolean {
    // 1. Check global toggle
    if (!this.globalFlags.get('humanitarian.enabled')) return false;
    // 2. Check NGO-specific override
    if (this.ngoOverrides.has(ngoId, feature)) return true;
    // 3. Check phased rollout percentage
    return this.rolloutPercentage(feature) > Math.random() * 100;
  }
}
```
