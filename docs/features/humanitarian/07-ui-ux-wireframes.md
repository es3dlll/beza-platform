# UI/UX Wireframes & User Flows

## Design Principles
1. **Arabic-first** — All screens designed for RTL; English is secondary
2. **Low-literacy friendly** — Heavy iconography, voice guidance, colour-coded status
3. **Offline resilient** — Agent app works with local cache; syncs when online
4. **Minimal taps** — Core flows (verify, distribute) ≤ 3 taps from home

---

## Flow 1: Program Manager — Create Program

```
Dashboard
  └─ "Create New Program" button
       ├─ Step 1: Program Type (MPC / CCT / Voucher) — card selection
       ├─ Step 2: Program Details (name Ar/En, budget, currency, dates)
       ├─ Step 3: Distribution Rules (amount, frequency, conditional logic)
       ├─ Step 4: Beneficiary Upload (drag-drop CSV, or manual entry)
       ├─ Step 5: Review & Confirm
       └─ Success → Redirect to Program Detail page
```

## Flow 2: Field Agent — Verify Beneficiary

```
Agent Login (PIN + fingerprint)
  └─ "Verify Beneficiary" screen
       ├─ Option A: Scan UNHCR ID barcode
       ├─ Option B: Search by name/phone
       └─ Beneficiary found → Biometric prompt
            ├─ Scan fingerprint (left thumb, right thumb)
            ├─ Capture face photo
            ├─ Match result: ✅ Verified / ❌ No Match
            └─ If ✅ → "Assistance ready" screen
                 └─ Beneficiary signs on screen (or thumbprint for signature)
```

## Flow 3: Beneficiary — Redeem Voucher at Merchant

```
Merchant opens Beza Merchant App
  └─ "Redeem Voucher" button
       ├─ Enter 12-digit voucher code (or scan QR)
       ├─ System shows: Beneficiary name (partial), voucher value, approved items
       ├─ Merchant selects items purchased:
       │    └─ 5kg rice × 1  ($6.00)
       │    └─ 1L cooking oil × 2  ($4.00)
       │    └─ ... remaining: $35.00
       ├─ Confirm redemption
       ├─ Beneficiary confirms total (on-screen signature)
       └─ Success → SMS notification to beneficiary
```

## Flow 4: Donor — Generate Report

```
Donor Portal (login)
  └─ "Reports" tab
       ├─ Select NGO / Program
       ├─ Date range picker
       ├─ Report type: Disbursement / Spending / Reconciliation
       ├─ Preview (aggregated charts + table)
       └─ Export: PDF / CSV / XLSX
```

## Key Screens (Mobile & Web)

| Screen | Platform | Description |
|--------|----------|-------------|
| Program List | Web | Cards showing active/paused/completed programs with budget bar |
| Program Detail | Web | Beneficiary count, distribution history, spending chart |
| Beneficiary Upload | Web | Drag-drop zone, validation error table, preview before submit |
| Agent Verification | Mobile (Android) | Camera + fingerprint scanner UI, single-beneficiary workflow |
| Distribution Trigger | Web | "Distribute Now" button with confirmation modal, progress bar |
| Voucher Redemption | Mobile (Android) | Code entry, item selection with photos, totals |
| MPC Spending Dashboard | Web | Pie chart (categories), burn rate line chart, filters |
| Donor Report | Web | KPI cards, embedded charts, export buttons |
