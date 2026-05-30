# Savings UX Strategy

## Core Principles
1. **Effortless** — Saving should require zero willpower. Auto-save and round-up do the work.
2. **Celebratory** — Every milestone is an achievement worth celebrating with animations, confetti, badges.
3. **Transparent** — Users always know: how much saved, how much earned in profit, what fees apply.
4. **Social (optional)** — Share goals with family, join teams, encourage each other.
5. **Sharia-first** — Never use words like "interest" or "riba." Always "ربح" (profit), "مشاركة" (sharing).

## Behavioral Design

### Commitment Devices
- Goal lock: voluntary lock prevents temptation to withdraw early
- Auto-save commitment: user sets amount once, system deducts automatically
- Team accountability: social pressure from team members encourages contributions

### Nudge Strategy
| Trigger | Timing | Nudge |
|---------|--------|-------|
| After large transaction | Real-time | "هل تريد توفير جزء من هذا المبلغ؟" |
| Daily at 9 PM | Scheduled | "لم تقم بتوفير اليوم — وفر 1,000 ل.س الآن؟" |
| Payday (weekly detected pattern) | Automated | "يوم الراتب! ضاعف توفير اليوم" |
| Goal milestone (25/50/75/100%) | Event | Celebration + "هل تريد زيادة الهدف؟" |
| 3 missed auto-saves | Alert | "فاتتك 3 أيام من التوفير" |
| Profit distributed | Event | "تم توزيع 2,500 ل.س أرباحاً على هدفك" |

### Emotional Design
- Progress bar uses gradient color: red → yellow → green (visual urgency → achievement)
- Milestone celebrations: confetti animation, haptic feedback, celebratory message
- Goal completion: full-screen celebration, shareable achievement card
- Team goals: member avatars around a central progress circle

## Arabic-First Design
- RTL layout throughout savings screens
- Number formatting: ١٬٢٣٤٬٥٦٧ (Arabic-Indic digits option)
- All currency in SYP format: "ل.س" after amounts
- Date format: dd/mm/yyyy, Arabic month names
- Voice: friendly, warm, encouraging — like a supportive friend
- Error messages: empathetic, solution-oriented
