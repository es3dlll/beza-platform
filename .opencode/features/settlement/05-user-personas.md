# Settlement User Personas

## Persona 1: Layla — Settlement Operations Lead
- **Role**: Settlement operations manager at Beza
- **Goals**: Ensure every batch settles on time, zero unreconciled items at EOD
- **Pain Points**: Manual reconciliation takes 3+ hours daily, exceptions buried in spreadsheets
- **Needs**: Real-time batch status dashboard, automated exception alerts, one-click resolution
- **Arabic context**: Needs Arabic interface for day-to-day operations

## Persona 2: Khalid — Finance Controller
- **Role**: Beza finance controller overseeing settlement risks
- **Goals**: Accurate settlement reporting, audit trail for every batch
- **Pain Points**: No single source of truth for settlement movements
- **Needs**: Daily settlement reports, netting summaries, confirmation tracking
- **Arabic context**: Reports must serve both Arabic-speaking ops and English-speaking auditors

## Persona 3: Nour — Partner Bank Operations
- **Role**: Operations officer at Beza's partner bank (Bemo Saudi Fransi, etc.)
- **Goals**: Receive clean payment files, match confirmations quickly
- **Pain Points**: Payment files in inconsistent formats, delayed confirmations
- **Needs**: Standardized ISO 20022 files, automated confirmation exchange

## Persona 4: Sami — Compliance Officer
- **Role**: Beza internal compliance, preparing for CBS/CMT audits
- **Goals**: Demonstrate settlement integrity, trace every transaction
- **Pain Points**: Months of effort to compile audit evidence
- **Needs**: Immutable settlement log, exception history, regulator-ready reports

## Persona 5: Rana — Agent Network Manager
- **Role**: Manages Beza's cash-in/cash-out agent network
- **Goals**: Agent settlement completed daily, float positions tracked
- **Pain Points**: Agent settlement lags cause service disruptions
- **Needs**: Agent net position calculation, settlement batches for agent payouts

## Persona 6: Tarek — Software Engineer
- **Role**: Backend engineer building settlement features
- **Goals**: Clear architecture, testable services, observable flows
- **Pain Points**: Complex netting logic, hard-to-debug reconciliation mismatches
- **Needs**: Comprehensive domain models, event-driven architecture, detailed logging
