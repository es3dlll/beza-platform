# Agent Network Operations

## Agent Onboarding Workflow

### End-to-End Process
```
                                                    ┌──────────────┐
                                                    │  الزبون يشتكي  │
                                                    │  أو يطلب الدعم  │
                                                    └──────┬───────┘
                                                           │
                     ┌──────────────────────┐              │
                     │  طلب التسجيل          │              │
                     │  (Agent Registration) │              │
                     └──────────┬───────────┘              │
                                │                          │
                                ▼                          │
                     ┌──────────────────────┐              │
                     │  فحص المستندات        │              │
                     │  (Document Review)    │              │
                     │  الرد خلال 24 ساعة    │              │
                     └──────────┬───────────┘              │
                                │                          │
                    ┌───────────┴───────────┐              │
                    ▼                       ▼              │
          ┌──────────────────┐  ┌──────────────────┐       │
          │  ✅ قبول          │  │  ❌ رفض          │       │
          │  (Approved)      │  │  (Rejected)      │       │
          └──────────┬───────┘  └──────────────────┘       │
                    │                                      │
                    ▼                                      │
          ┌──────────────────┐                             │
          │  تدريب الوكيل     │                             │
          │  (Training)       │                             │
          │  جلسة ساعة واحدة  │                             │
          └──────────┬───────┘                             │
                    │                                      │
                    ▼                                      │
          ┌──────────────────┐                             │
          │  تفعيل الجهاز     │                             │
          │  (Device Activation)│                          │
          └──────────┬───────┘                             │
                    │                                      │
                    ▼                                      │
          ┌──────────────────┐                             │
          │  إيداع الصندوق    │                             │
          │  (Initial Float)  │                             │
          │  الحد الأدنى 500K │                             │
          └──────────┬───────┘                             │
                    │                                      │
                    ▼                                      │
          ┌──────────────────┐                             │
          │  ✅ جاهز للعمل    │                             │
          │  (Active)         │                             │
          └──────────────────┘                             │
                                                           │
                    ┌──────────────────────────────────────┘
                    │
                    ▼
          ┌──────────────────┐
          │  دعم مستمر        │
          │  (Ongoing Support) │
          │  - خط ساخن        │
          │  - واتساب         │
          │  - زيارات ميدانية │
          │  - تقييم شهري     │
          └──────────────────┘
```

### Milestone Timeline
| Milestone | Target | Owner | Success Criteria |
|-----------|--------|-------|------------------|
| Lead submission | Day 0 | Agent | Application submitted |
| Document review | Day 0-1 | Operations Team | All docs verified within 24h |
| Field visit | Day 1-3 | Field Officer | Shop visited and photos verified |
| KYC approval | Day 1-2 | Compliance | Background check cleared |
| Training session | Day 2-4 | Trainer | Agent completes 1-hour POS training |
| Device assignment | Day 3-5 | Logistics | POS device delivered and configured |
| First float deposit | Day 3-7 | Agent | Minimum 500,000 SYP float deposited |
| First transaction | Day 3-7 | Agent | First cash-in completed |

### Required Collateral
- **Field Officer Kit**: tablet with registration app, portable printer, training materials, SIM cards
- **Agent Welcome Kit**: POS device, thermal printer, charging cable, Beza-branded signage ("وكيل معتمد Beza"), commission rate card, support hotline sticker, quick reference guide (laminated)

### Agent Activation Checklist
```
□ جميع المستندات مكتملة وصحيحة
□ KYC معتمد
□ الجهاز مفعل ومقترن بالوكيل
□ التدريب مكتمل (توقيع الوكيل)
□ الإيداع الأول للصندوق (500,000 ل.س كحد أدنى)
□ تم تعيين الرقم السري الأولي
□ لافتة Beza مثبتة في المحل
□ رقم الدعم محفوظ في هاتف الوكيل
□ تم أول معاملة إيداع تجريبية
□ تم أول معاملة سحب تجريبية
```

## Agent Support

### Support Channels
| Channel | Hours | Response Time | Use Case |
|---------|-------|---------------|----------|
| 📞 خط ساخن (Hotline) | 24/7 | < 30 seconds | Urgent: transaction failure, login issue, dispute |
| 💬 واتساب (WhatsApp) | 7AM-10PM | < 5 minutes | General inquiries, float questions, commissions |
| 📧 بريد إلكتروني | Business hours | < 2 hours | Document submission, formal complaints |
| 📍 زيارة ميدانية | Monthly | Scheduled | Performance review, training refresh |
| 📱 إشعار في التطبيق | 24/7 | Instant | Announcements, low float alerts, tips |

### Tier-Based Support Levels
| Tier | Channel | Response SLO | Account Manager |
|------|---------|-------------|-----------------|
| Bronze | WhatsApp + Hotline | 30 min (hotline) | Shared (1:200) |
| Silver | WhatsApp + Hotline | 15 min (hotline) | Shared (1:100) |
| Gold | Priority Hotline + WhatsApp | 5 min | Dedicated (1:50) |
| Platinum | VIP Line + Personal WhatsApp | 1 min | 1:1 Account Manager |

### Common Support Scenarios & Solutions
```
المشكلة: الوكيل لا يستطيع تسجيل الدخول
  الحل: 1. التحقق من الرقم السري (إعادة تعيين عبر SMS)
        2. التحقق من حالة الحساب (محظور؟ معلق؟)
        3. التحقق من حالة الجهاز (مقترن؟)
        4. إعادة تعيين الجلسة

المشكلة: فشل المعاملة — رصيد غير كافٍ
  الحل: 1. شرح للوكيل أن رصيد الصندوق غير كافٍ
        2. توجيهه لتعبة الصندوق
        3. في حال وجود خطأ: تحويل إلى الفريق الفني

المشكلة: العمولة غير صحيحة
  الحل: 1. التحقق من تصنيف الوكيل
        2. التحقق من سعر العمولة المطبق
        3. التحقق من مبلغ المعاملة
        4. إذا كان هناك خطأ: تعديل وإضافة العمولة الناقصة

المشكلة: الزبون يدّعي أنه لم يستلم النقود
  الحل: 1. التحقق من سجل المعاملة (التاريخ، الوقت، الموقع)
        2. التأكيد مع الوكيل هل ضغط "تم التسليم"
        3. إذا كان هناك خلاف: فتح نزاع وتحويله للامتثال
```

## Agent Performance Reviews

### Monthly Review Process
```
Day 1-5 of each month:

1. Automated Performance Summary Generated:
   - Transaction volume (total, daily avg)
   - Commission earned (total, per txn avg)
   - Uptime (active days this month)
   - Float management (low float incidents)
   - Customer satisfaction score
   - Compliance flags (if any)

2. Tier Evaluation:
   - Check if agent meets criteria for next tier
   - If eligible: auto-upgrade, send congratulations
   - If not eligible: show gap analysis

3. Review Call:
   - Gold+ agents: personal call from account manager
   - Bronze/Silver: automated SMS summary

4. Action Items:
   - Training recommendations (if satisfaction < 4.0)
   - Float management coaching (if >5 low float incidents)
   - Compliance reminders (if near threshold)
```

### Performance Report (Agent-Facing)
```
تقرير أداء الوكيل — يونيو 2026
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
الوكيل: أبو محمد (BZ-10234)
التصنيف: 🥉 برونز
━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━━
📊 المعاملات:
  - إجمالي الإيداع: 45,000,000 ل.س (350 معاملة)
  - إجمالي السحب: 22,500,000 ل.س (180 معاملة)
  - معدل يومي: 1.5M إيداع / 750K سحب

💰 العمولات:
  - عمولات الشهر: 325,000 ل.س
  - إجمالي مستحق: 325,000 ل.س
  - تم التسوية: 280,000 ل.س

⭐ التقييم:
  - رضا الزبائن: 4.2/5.0
  - نسبة النزاعات: 0.3% (معدل ممتاز)
  - أيام النشاط: 28/30

📈 التوصيات:
  - 🟢 أداء جيد — مستعد للترقية إلى Silver
  - 💡 قم بتعبئة الصندوق مبكراً أيام أول الشهر (متوقع إقبال عالي)
  - 📱 شاهد فيديو التدريب الجديد عن كشف التزوير
```

## Tier Upgrade Workflow

### Automatic Upgrade
```
Criteria met → System detects → Automated process:

1. System check performed daily at 02:00 AM
2. If agent exceeds current tier thresholds:
   - Calculate new tier
   - Update agent.tier
   - Send notification:
     "تهانينا! تم ترقيتك إلى التصنيف {tier}!
     استمتع بالمزايا الجديدة:
     • عمولة أعلى: {rate}%
     • حد سحب أعلى: {limit} ل.س
     • دعم ذو أولوية"
   - Update POS app with new limits (on next sync)
   - Log tier change in agent_tier_history
```

### Manual Upgrade (Compliance Override)
```
Management discretion:
  - Performance Score > 85 for 3 consecutive months (even if volume below tier threshold)
  - Strategic importance (only agent in remote area)
  - Length of service (> 24 months with no violations)

Process:
  - Operations manager submits upgrade request
  - Compliance approves
  - Admin manually sets tier in dashboard
  - Notification sent to agent
```

## Dispute Resolution

### Customer vs Agent Dispute
```
Types of disputes:
  1. Customer claims they gave more cash than credited (cash-in)
  2. Customer claims they received less cash than debited (cash-out)
  3. Customer claims they never received cash (cash-out handover)
  4. Agent claims customer provided counterfeit cash
  5. Customer claims unauthorized transaction

Resolution Process:
  Step 1: Automated evidence collection
    - Transaction record (timestamp, amount, location, device)
    - Verification log (SMS code sent, code verified by agent)
    - Receipt (if printed)
    - GPS location of both agent and customer phones (if available)
    - CCTV (if shop has camera — agent provided voluntarily)

  Step 2: Initial assessment (within 2 hours)
    - If evidence clear: decide in favor of clear party
    - If unclear: escalate to manual review

  Step 3: Manual review (within 24 hours)
    - Compliance officer reviews all evidence
    - Calls both parties for statements
    - Decision:
      a. Agent at fault: debit agent float, credit customer
      b. Customer at fault: no action
      c. System error: correct transaction, fix bug
      d. Inconclusive: split difference (50/50)

  Step 4: Resolution
    - Adjust affected accounts
    - Notify both parties of outcome
    - Update dispute log

Appeal: Either party can appeal within 7 days
Final: Beza management decision is final
```
