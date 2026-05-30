# 04 — User Journeys

## Journey 1: Parent pays school fees (happy path)

1. Layla opens Beza app → taps **Education** card
2. Sees dashboard: "Al-Salam School — Fees Due: 15 May 2026"
3. Taps "Pay Now" → sees fee breakdown (Tuition: 750K, Activity: 50K, Books: 80K)
4. Selects **Pay in full** → confirms total 880,000 SYP
5. Authenticates via fingerprint + PIN
6. Payment processing confirmation → receipt generated (PDF + QR)
7. Receipt auto-emailed + available in **Payments → History**
8. School dashboard updates in real-time
9. Layla gets push notification: "Payment confirmed — 880,000 SYP"

## Journey 2: School finance manager bulk reminder

1. Sami logs to **Beza Education Dashboard** (web)
2. Filters "Outstanding Fees" → 142 students unpaid
3. Clicks **Send Reminder** → selects WhatsApp + SMS
4. Bulk reminder template: "Dear parent, Term-2 fee of 900,000 SYP was due on 1 May. Please pay via Beza to avoid 2% late fee. — Al-Mohajer School"
5. System sends 142 WhatsApp messages + 142 SMS
6. Dashboard shows delivery stats: 138 delivered, 4 failed
7. Sami downloads list of failed numbers for follow-up

## Journey 3: Parent sets up recurring tuition

1. Nour (parent of SPU student) goes to **Payments → Schedule**
2. Selects **Recurring** → "Syrian Private University — Engineering"
3. Fee: 1,400,000 SYP/term × 3 terms
4. Selects **Auto-pay from Beza Wallet**
5. Sets: 1 Sep / 1 Jan / 1 May
6. Confirms → system schedules first payment
7. 3 days before each date, Beza sends reminder (WhatsApp)
8. On due date, payment auto-debits → receipt generated

## Journey 4: Tutor centre monthly billing

1. Ahmad opens Beza Merchant app → Education dashboard
2. Sees **120 active subscribers** this month
3. System has already generated 120 invoices (200K SYP each)
4. 80 auto-charged on 1st → success; 40 failed (insufficient balance)
5. System auto-retries on 5th, 10th, 15th
6. After 3rd failure, sends SMS: "Dear parent, your Al-Tafawuq subscription payment failed. Please top up your Beza wallet."
7. On 20th, remaining unpaid → late fee (10K SYP) added
8. Ahmad sees real-time revenue chart: 24 M SYP collected, 4 M SYP outstanding

## Journey 5: University bursar views faculty reports

1. Nour logs to dashboard → selects **Faculty → Reports**
2. Picks "Faculty of Medicine — Spring Term 2026"
3. Dashboard: 1,200 students → 1,050 paid (87.5 %) / 150 outstanding
4. Revenue collected: 4.2 B SYP / Revenue expected: 4.8 B SYP
5. Exports PDF report → emails to Rector
6. Drills into top 10 overdue students → clicks **Call Guardian**
7. System shows guardian phone number from profile

## Journey 6: Diaspora parent pays from Europe

1. Hassan (in Berlin) opens Beza → Remittance → Education
2. Selects "IUST — Tuition for Omar Hassan"
3. Invoice: 4,800,000 SYP (convert from EUR)
4. Beza shows: €185.36 at today's rate (25,890 SYP/EUR)
5. Hassan pays with his Beza EUR wallet balance
6. System converts via FX Engine → credits IUST account in SYP
7. Both Hassan and IUST receive confirmation
8. Omar can download receipt for university registration

## Journey 7: Parent applies for fee financing

1. Khalid (father of 2) sees total annual fees: 4.5 M SYP
2. Taps **Finance available → Apply**
3. Beza checks his credit score (payment history, wallet transactions)
4. Approved: 4.5 M SYP at 8 % flat, 12 monthly instalments
5. Khalid accepts → Beza pays school upfront in full
6. School receives full payment → marks student as fully paid
7. Khalid sees monthly payment: ~405,000 SYP for 12 months
8. Auto-deducted from wallet every 1st of month

## Journey 8: Student self-service portal

1. Khaled logs to Beza → Education → Damascus University
2. Sees enrolled courses, fee status: "Outstanding: 175,000 SYP"
3. Clicks **Pay** → pays 175,000 SYP from wallet
4. Receipt appears instantly → **Download PDF** for registration
5. System auto-updates university portal (via API)
6. Khaled proceeds to course registration without printing anything

## Journey 9: School enrolment day — high-volume processing

1. Sami activates **Enrolment Mode** in dashboard
2. 200 parents arrive at school, each handed a QR code
3. Parent scans QR → opens Beza → sees child's fee invoice
4. Parent pays in 30 seconds → Sami's screen shows **Paid** in green
5. Child moves to next station (uniform, books) immediately
6. End of day: Sami exports report — 198/200 paid, 2 pending
7. 2 pending contacted same day → resolves before child starts

## Journey 10: Automated fee structure update

1. Al-Sham University announces 15 % fee increase for new academic year
2. Bursar logs to dashboard → **Fee Templates → Update**
3. Selects all faculties → "Increase by 15 %"
4. System recalculates all invoices → notifies affected parents
5. Parents see updated amounts in app with note: "Fee updated per university notice dated 15 Aug 2026"
6. Objection window: 14 days to dispute
7. After 14 days, new invoices become binding
