# الأسئلة الشائعة واستكشاف الأخطاء — FAQ & Troubleshooting

## User FAQ

### General
**Q: What is Qard Hasan?**
A: A benevolent loan with zero profit/interest. You repay only the principal amount. A small admin fee covers the actual cost of processing. Fully Sharia-compliant.

**Q: Is this Islamic financing?**
A: Yes. All Beza financing products are certified by our Sharia Board. No riba (interest), no gharar (uncertainty), and late fees go to charity, not Beza.

**Q: Who is eligible?**
A: Syrian nationals aged 18–65 with an active Beza wallet for at least 3 months and KYC Level 2 completed.

### Applications
**Q: How long does approval take?**
A: Qard Hasan: 2 hours or less. Murabaha: up to 24 hours. Micro-Enterprise: up to 48 hours (may require manual review).

**Q: Why was my application rejected?**
A: Common reasons: insufficient wallet history (< 3 months), low credit score, existing overdue payments, incomplete documents. Check your credit score for improvement tips.

**Q: Can I apply for multiple products at once?**
A: No. You can have only one active financing at a time (Qard Hasan and Micro: 1 each; Murabaha: up to 3).

### Payments
**Q: How do I make payments?**
A: Payments are auto-deducted from your Beza wallet daily (Qard Hasan) or monthly (Murabaha/Micro). You can also make manual payments anytime.

**Q: What happens if I miss a payment?**
A: You get a 3-day grace period (7 days for Murabaha/Micro). After that, a late fee applies (goes to charity, not Beza). We will remind you via SMS, push notification, and call.

**Q: Can I pay early?**
A: Yes. You can repay the full remaining amount anytime with no penalty. Early repayment improves your credit score.

**Q: Can I restructure my payments?**
A: Yes. If you're facing difficulty, request restructuring before default. Options: extend term, reduce installment, or payment holiday.

### Sharia
**Q: Is the late fee really going to charity?**
A: Yes. Late fees are held in a separate charity account and disbursed quarterly to registered charitable organizations. You receive an annual statement.

**Q: How is Murabaha different from a conventional loan?**
A: In Murabaha, Beza buys the item first and sells it to you at cost + disclosed profit. The profit is fixed and disclosed upfront. This is a sale, not a loan.

### Technical
**Q: I didn't receive the SMS verification.**
A: Check your network connection. Request resend after 2 minutes. If still not received, contact support at 1234 (free from Beza SIM).

**Q: The app shows "processing" for hours.**
A: Force close the app and reopen. If the issue persists, check your application status or contact support.

## Administrator FAQ

**Q: How to manually approve a high-value application?**
A: Admin Dashboard → Applications → Filter by "Underwriting" → Review documents, credit score, cash flow → Approve or Reject with reason.

**Q: How to process a restructuring?**
A: Collection Queue → Select contract → Restructure tab → Review options → Choose new terms → Approve → New schedule generated automatically.

**Q: How to mark a default?**
A: System auto-marks after 90 days overdue. For manual: Collection → Contract → Mark Default → System provisions bad debt.

## Troubleshooting Guide

### Application Issues
| Problem | Root Cause | Solution |
|---------|------------|----------|
| "You have reached the maximum number of active loans" | User already has max active loans | Wait for completion or early repay |
| "KYC verification required" | User not KYC Level 2 | Complete identity verification in Profile |
| "Amount exceeds available limit" | Requested amount > product max | Reduce amount or choose different product |
| "Invalid guarantor" | Guarantor not on Beza | Invite guarantor to download Beza |

### Payment Issues
| Problem | Root Cause | Solution |
|---------|------------|----------|
| Auto-deduction failed | Insufficient wallet balance | Deposit funds before 08:00 AM due date |
| "Payment failed - try again" | Temporary system error | Retry manually from app |
| Payment deducted but status shows pending | Async processing delay | Wait 5 minutes, refresh. If persists, contact support |
| Overdue despite sufficient balance | Auto-deduct not yet retried | System retries 3 times over 48 hours. Manual pay from app |

### Score Issues
| Problem | Root Cause | Solution |
|---------|------------|----------|
| Score not updating | Score refreshed every 24 hours | Wait for next calculation cycle |
| Score dropped unexpectedly | May be due to reduced wallet activity | Increase wallet usage, maintain higher balance |
| Score factors seem inaccurate | Data may be stale (up to 24h old) | Score recalculates on new transactions |

### System Errors
| Error Code | Meaning | Action |
|------------|---------|--------|
| FIN-4001 | Invalid product type | Check product_type parameter |
| FIN-4002 | Amount out of range | Verify min/max for product |
| FIN-4003 | Missing required documents | Upload all required documents |
| FIN-4031 | Application state conflict | Application already processed |
| FIN-5001 | Scoring service unavailable | Retry after 5 minutes |
| FIN-5002 | Disbursement service error | Contact support with application ID |
| FIN-5003 | Contract generation failed | Contact support with contract details |
