# Bill Payment Feature Vision

## Elevator Pitch
Beza Bill Payment is a unified digital bill payment hub that lets any Syrian pay electricity, water, telecom, internet, government fees, and education bills from a single app — eliminating queues, late fees, and the hassle of visiting separate service centers for each biller.

## Problem Statement
- Syrians spend 2–4 hours monthly in queues at service centers (PEED, Water Authority, Syriatel, etc.)
- Each biller has a separate payment channel — no single app covers all bills
- Late payment penalties on electricity (5% of bill/month) and telecom (service suspension) cost households 10,000–50,000 SYP annually
- Cash-only bill payment forces unsafe cash carrying and lost receipts
- Syrian diaspora cannot pay family bills remotely without sending cash through informal channels

## Target Users
- **Primary**: Urban Syrians age 22–50 with smartphones (3M target) — current bill payers managing household utilities
- **Secondary**: Syrian diaspora who pay family bills remotely (500K target)
- **Tertiary**: Small business owners paying commercial electricity/water/telecom (200K target)

## Core Capabilities
| Capability | Priority | Description |
|------------|----------|-------------|
| Bill fetch (real-time API) | P0 | Fetch bill by customer ID from PEED, Damascus Water, Syriatel, MTN, Syria Telecom |
| Bill payment & confirmation | P0 | Pay fetched bill with PIN, receive biller confirmation immediately |
| Bill history | P0 | Complete payment history filterable by biller, date, status |
| Scheduled/reminder bills | P1 | Set recurring or one-time reminders for due dates |
| CSV batch billing | P2 | Support billers who send CSV files (government fees, university tuition) |
| Auto-pay | P2 | Authorize automatic payment of recurring bills on due date |
| Multi-bill payment | P1 | Pay multiple bills in a single transaction (cart-style) |
| Receipt generation | P0 | Digital receipt (PDF) for every bill payment with biller reference |
| Biller discovery | P1 | Browse all supported billers with customer ID format guidance |
| Late fee calculator | P2 | Show late fees before payment with breakdown |
| Partial payment | P2 | Pay partial bill amount (supported by PEED, Water Authority) |
| Payment splitting | P2 | Split a bill among multiple Beza users |

## Success Metrics
| Metric | Y1 Target | Y3 Target |
|--------|-----------|-----------|
| Supported billers | 10 | 30+ |
| Monthly bill payments | 200K | 2M |
| Bill payment volume (monthly) | 5B SYP | 50B SYP |
| Bill fetch success rate | 95% | 99% |
| Payment success rate | 98% | 99.5% |
| Biller confirmation within 5 min | 85% | 98% |
| Users with scheduled reminders | 50K | 500K |
| Late payment reduction (users) | 30% | 60% |
