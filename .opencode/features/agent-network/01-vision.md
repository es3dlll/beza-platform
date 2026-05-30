# Agent Network Feature Vision

## Elevator Pitch
Beza Agent Network is Syria's largest physical financial distribution layer — thousands of local shopkeepers, grocery stores, and pharmacies converted into cash-in/cash-out points serving the unbanked and underbanked. Every agent runs a secure POS terminal connected to the Beza platform, enabling any Syrian to deposit or withdraw cash from their digital wallet without needing a bank account, smartphone, or internet access.

## Problem Statement
- 85%+ of Syrian economy runs on cash with no accessible digital on/off ramps
- <30% banking penetration means most Syrians travel 30+ minutes to access financial services
- Existing agent networks (Syriatel Cash, MTN MoMo) are telecom-locked and offer limited functionality
- Shopkeepers already act as informal financial intermediaries (holding cash, providing credit) without digital record-keeping
- No standardized agent commission model exists — rates vary wildly by region and relationship
- Agent fraud and float mismanagement are rampant in informal networks

## Target Users
- **Primary**: Shopkeepers (agents) aged 25-60 running small retail businesses across Syria (10,000 target)
- **Secondary**: Unbanked/rural customers accessing Beza through agents (3M target)
- **Tertiary**: Urban customers using agents for convenience (1M target)

## Core Capabilities
| Capability | Priority | Description |
|------------|----------|-------------|
| Agent registration | P0 | Document collection, KYC, device assignment, training tracking |
| Cash-in (user → wallet) | P0 | User gives cash to agent, agent debits float, user wallet credited |
| Cash-out (wallet → cash) | P0 | User requests cash, wallet debited, agent float credited, cash handed over |
| Float management | P0 | Agent float top-up, float transfer between agents, float monitoring |
| Commission settlement | P0 | T+1 automatic commission settlement to agent wallet |
| Transaction history | P0 | Agent-level transaction log with search and export |
| Agent POS app | P0 | Dedicated Flutter-based POS application for Android devices |
| Offline mode | P0 | Queue transactions offline, sync when connectivity restored |
| Receipt printing | P1 | Bluetooth thermal printer support for paper receipts |
| Agent tier system | P1 | Bronze/Silver/Gold/Platinum tiers with graduated limits and rates |
| Agent locator | P1 | Map-based agent finder for customers (nearest agent, distance, status) |
| Agent messaging | P1 | In-app announcements, float alerts, commission notifications |
| Predictive float | P2 | ML-based cash demand forecasting per agent |
| Automated restocking | P2 | Proactive float top-up recommendations |
| Agent performance analytics | P2 | Volume trends, customer satisfaction, uptime scoring |
| Agent-to-agent float transfer | P2 | Float sharing between nearby agents |

## Success Metrics
| Metric | Y1 Target | Y3 Target |
|--------|-----------|-----------|
| Registered agents | 2,000 | 10,000 |
| Active agents (30d) | 1,500 | 8,000 |
| Avg daily txns per agent | 25 | 80 |
| Agent float balance (avg) | 500,000 SYP | 2,000,000 SYP |
| Cash-in/out monthly volume | 50B SYP | 500B SYP |
| Agent retention (12mo) | 70% | 85% |
| Commission satisfaction rate | 75% | 90% |
| Agent activation rate (post-reg) | 80% | 95% |
| Offline txn success rate | 95% | 99% |
| Float discrepancy incidents/month | <50 | <10 |
