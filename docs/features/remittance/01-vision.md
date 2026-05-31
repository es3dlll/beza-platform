# Remittance Feature Vision

## Elevator Pitch
Beza Remittance transforms Syria's $2-3B/year diaspora-to-home transfer market from slow, expensive hawala (5-10% fees, 2-3 days) into instant, sub-3% digital transfers. Syrians in 40+ countries send money home in minutes, recipients get SYP or USD directly in their Beza wallet, and the entire flow is compliant with global AML/CTF standards.

## Problem Statement
- 6M Syrian diaspora sends $2-3B/year home, mostly through informal hawala networks
- Hawala fees: 5-10% + unfavorable FX rates = 10-15% effective cost
- Transfer times: 2-3 days average, no tracking visibility
- No digital-first solution purpose-built for Syria's remittance corridors
- Recipients often must travel to hawala agents, losing time and money
- Senders have no delivery confirmation or dispute mechanism
- Recurring remittances handled manually each month

## Target Users
- **Primary**: Syrian diaspora in EU (Germany, Sweden, Netherlands), Turkey, UAE, Saudi Arabia, US, Canada — 6M people
- **Secondary**: Local Syrian P2P users sending between cities (Damascus ↔ Aleppo ↔ Homs)
- **Tertiary**: Syrian refugees in Turkey/Jordan sending to family inside Syria

## Core Capabilities
| Capability | Priority | Description |
|------------|----------|-------------|
| Local P2P transfer (SYP) | P0 | Send SYP to any Beza user by phone number |
| Local P2P transfer (USD) | P0 | Send USD between Beza USD wallets |
| Diaspora remittance (USD→SYP) | P0 | Diaspora sends USD, recipient gets SYP with FX |
| Diaspora remittance (EUR→SYP) | P0 | Diaspora sends EUR, recipient gets SYP with FX |
| Diaspora remittance (USD→USD) | P0 | Diaspora sends USD, recipient holds USD |
| Recurring transfer | P1 | Set up monthly/weekly standing remittance |
| Request money | P1 | Request payment from any Beza user |
| Multi-corridor support | P0 | Separate limits/fees per source country corridor |
| Beneficiary management | P1 | Save and manage beneficiary profiles |
| Transfer tracking | P0 | Real-time status: initiated → FX locked → completed |
| Same-currency transfer | P0 | SYP→SYP, USD→USD, EUR→EUR |
| Cross-currency transfer | P0 | USD→SYP, EUR→SYP, EUR→USD |
| Bulk transfer | P2 | Send to multiple recipients at once |
| Scheduled transfer | P1 | Schedule future-dated transfers |
| Transfer cancellation | P0 | Cancel within 30-min hold window |
| Receipt generation | P0 | PDF receipt in Arabic/English |
| SMS notification to unregistered | P0 | SMS with pickup code if recipient not on Beza |

## Success Metrics
| Metric | Y1 Target | Y3 Target |
|--------|-----------|-----------|
| Remittance TP | $50M/month | $500M/month |
| Active diaspora senders | 50K | 500K |
| Active local recipients | 200K | 2M |
| Avg remittance amount | $250 | $350 |
| Corridors active | 10 | 40+ |
| Recurring transfer adoption | 15% of senders | 35% of senders |
| FX spread revenue | $1M/year | $15M/year |
| Transaction success rate | 99.0% | 99.8% |
| Average delivery time | < 5 min | < 30 sec |
| NPS score | +40 | +65 |
