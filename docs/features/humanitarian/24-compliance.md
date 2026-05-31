# Compliance & Humanitarian Principles

## Humanitarian Principles

### 1. Humanity
- All aid programs must prioritise human dignity
- Beneficiaries are treated as rights-holders, not passive recipients
- Cash assistance is unconditional by default; conditions only applied when they serve the beneficiary's best interest

### 2. Neutrality
- Beza does not take sides in hostilities
- Aid delivery is based on need alone, regardless of:
  - Political affiliation
  - Religious identity (Sunni, Shia, Alawite, Christian, Kurd, etc.)
  - Ethnicity (Arab, Kurd, Turkmen, Armenian, Assyrian, etc.)
  - Area of control (regime-held, opposition-held, SDF-held)
- Beneficiary data never shared with any party to the conflict

### 3. Impartiality
- Assistance is provided solely based on need, with priority to the most vulnerable
- No discrimination in beneficiary selection:
  - All governorates served: Damascus, Aleppo, Idlib, Homs, Hama, Deir ez-Zor, Raqqa, Hasakeh, Daraa, Quneitra, Suwayda, Tartous, Latakia, Rural Damascus
  - Special provisions for persons with disabilities, elderly, pregnant women, unaccompanied minors

### 4. Do-No-Harm
- Aid delivery must not expose beneficiaries to additional risk
- **Privacy:** Beneficiary data is treated as highly sensitive — beneficiaries in opposition areas could face reprisals if data leaks to regime
- **Stigma:** Cash assistance disbursed discreetly; no visible markers of "aid recipient"
- **Security:** Agent verification locations are not published; verification happens privately
- **Inflation:** MPC amounts calibrated not to distort local markets (max 75 USD/month to align with SMPC working group)
- **Conflict sensitivity:** Distribution conducted through local actors (SARC, local NGOs) to avoid perception of foreign interference

## Sanctions Compliance

### Applicable Regimes

| Regime | List | Update Frequency |
|--------|------|------------------|
| **UN Security Council** | UN Consolidated List (ISIL/Da'esh, Al-Qaida, Syria sanctions) | Real-time (via UN XML feed) |
| **EU** | EU Consolidated Financial Sanctions List (CFSP) | Daily |
| **OFAC (US)** | Specially Designated Nationals (SDN) List — Syria-related and non-Syria | Multiple times daily |
| **UK** | UK Sanctions List (OFSI consolidated list) | Daily |

### Screening Process

```
                    ┌─────────────────────────┐
                    │  Beneficiary Enrolled    │
                    │  (name, UNHCR ID, etc.)  │
                    └────────────┬────────────┘
                                 ▼
                    ┌─────────────────────────┐
                    │  Name Normalisation     │
                    │  - Arabic-to-Latin      │
                    │  - Remove diacritics    │
                    │  - Standardise spelling │
                    └────────────┬────────────┘
                                 ▼
                    ┌─────────────────────────┐
                    │  Multi-List Screening   │
                    │  UN: ──────────────────►│
                    │  EU: ──────────────────►│ Fuzzy matching
                    │  OFAC: ────────────────►│ (Levenshtein,
                    │  UK: ──────────────────►│  Soundex, Arabic
                    │                         │  transliteration)
                    └────────────┬────────────┘
                                 ▼
              ┌───────────────────────────────────┐
              │        Score Analysis              │
              │                                    │
              │  Score < 60%  →  Auto-Cleared      │
              │  Score 60-80% →  Suggest-Cleared   │
              │  Score 80-95% →  Manual Review      │
              │  Score > 95%  →  Auto-Blocked      │
              └───────────────────────────────────┘
```

### Fuzzy Matching Algorithm
- **Levenshtein distance:** Latin-script name comparison
- **Arabic transliteration normalisation:** Maps common Arabic spelling variants to standard Latin forms
- **Phonetic matching:** Soundex/DMETAPHONE for English names
- **Partial matching:** Detects names where beneficiary uses a subset of the sanctioned name
- **Reversed name matching:** Catches "GivenName FamilyName" vs "FamilyName, GivenName"

### False Positive Handling
- Compliance officer reviews each potential match
- Resolution options:
  - **False Positive (Clear):** Different person, same/similar name → cleared, rationale logged
  - **Confirmed Match:** Block beneficiary, notify NGO + relevant authorities
  - **Escalate:** Insufficient info → escalate to NGO's legal/compliance team

### Periodic Re-Screening
- All active beneficiaries re-screened every 90 days
- Triggered when sanctions lists are updated (via webhook)
- Re-screening runs as background job, does not disrupt active distributions
- New matches from re-screening → flagged for compliance review within 24 hours

## Data Privacy for Vulnerable Populations

| Principle | Implementation |
|-----------|---------------|
| Data minimisation | Only collect data necessary for aid delivery (no political/religious/ethnic data) |
| Purpose limitation | Beneficiary data used only for humanitarian assistance, never for surveillance |
| Consent | Beneficiary consent obtained at enrolment (verbal + thumbprint for illiterate) |
| Right to access | Beneficiary can request their data via agent (paper printout) |
| Right to erasure | Beneficiary can withdraw consent and request deletion (data purged after 30 days) |
| Data sharing | Never shared with third parties without explicit consent (except sanctions screening) |
| Cross-border data | Data stored in-region (Jordan/Istanbul); no transfer to conflict-party countries |
| Breach notification | Data breach notified to NGOs within 24 hours, to affected beneficiaries within 72 hours |

## Audit Trail for Donor Funds

### Traceability Requirements

| Requirement | Implementation |
|-------------|----------------|
| End-to-end fund traceability | Every dollar tracked: NGO deposit → Beza account → beneficiary wallet → merchant settlement |
| Immutable record | All financial events written to append-only audit log with hash chain |
| Timely reconciliation | Daily automated reconciliation: NGO sent = distributed + fees + unspent |
| Donor-specific tagging | Funds tagged by donor at source; reports filterable by donor |
| Sub-recipient monitoring | If NGO → local partner → Beza, all intermediate steps tracked |

### Audit Log Integrity
```
block_1:
  event_id: "evt_001"
  previous_hash: null
  data: { NGO deposit: $5,000,000 }
  hash: SHA256(event_id + prev_hash + data)
  
block_2:
  event_id: "evt_002"
  previous_hash: "a1b2c3..."  (hash of block_1)
  data: { Distribution batch: $750,000 }
  hash: SHA256(event_id + prev_hash + data)
```

### Regulatory Standards Met
| Standard | Relevance |
|----------|-----------|
| UN Financial Regulations | Full alignment |
| ECHO FPA (Framework Partnership Agreement) | Donor-specific reporting requirements met |
| USAID 2 CFR 200 | Uniform guidance compliance |
| CHS (Core Humanitarian Standard) | Quality and accountability commitment |
| IFRC NSIA | National Society Investment Alliance compliance |
| Grand Bargain cash commitments | Transparency and localisation principles |
