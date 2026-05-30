# Accessibility — Fraud Management UI

## Standards & Compliance

| Standard     | Compliance Level           | Notes                     |
| ------------ | -------------------------- | ------------------------- |
| WCAG 2.1 AA  | Target                     | All operations dashboards |
| WCAG 2.1 AAA | Target for critical alerts | P0 alert screens          |
| Syrian NA    | No local law, follow WCAG  | Best practice             |

## Color-Independent Risk Indicators

**Critical Rule:** NEVER convey risk status through color alone.

| Risk Level          | Color        | Icon                | Text                          | Pattern              |
| ------------------- | ------------ | ------------------- | ----------------------------- | -------------------- |
| Safe                | Green        | ✓ Check circle      | "Safe"                        | Solid background     |
| Suspicious          | Yellow/Amber | ⚠ Warning triangle  | "Suspicious — Review"         | Diagonal stripes     |
| Blocked             | Red          | 🚫 Prohibited       | "Blocked — Action required"   | Crosshatch           |
| Under Investigation | Blue         | 🔍 Magnifying glass | "Under Investigation"         | Dotted border        |
| Confirmed Fraud     | Dark Red     | ⚠ Exclamation       | "Confirmed Fraud — Escalated" | Solid + thick border |
| False Positive      | Green        | ✓ Check circle      | "False Alarm — Resolved"      | Dashed border        |

### Example: Accessible Risk Badge

```
Inaccessible (color only):         Accessible:
┌──────────────┐                   ┌──────────────────┐
│              │ ← Red box only    │ 🚫 BLOCKED       │ ← Icon + text
│              │                   │ Unusual activity │     + pattern
└──────────────┘                   └──────────────────┘
                                    🟥 ← Red (color is supplement)
```

## Screen Reader Support

### Dashboard

| Element      | ARIA Role                     | Accessibility                                                                                          |
| ------------ | ----------------------------- | ------------------------------------------------------------------------------------------------------ |
| KPI Cards    | `region` + `aria-label`       | "Fraud rate: zero point zero eight percent. Target: zero point one percent. Decreased from yesterday." |
| Alert Feed   | `list` + `aria-live="polite"` | "New P0 alert: Account takeover detected for user 8492. 500 thousand SYP at risk."                     |
| Charts       | `img` + `aria-describedby`    | "Line chart: fraud rate over 7 days. Current rate: 0.08 percent. Lowest: 0.05 percent on Wednesday."   |
| Tables       | `table` + proper `<th>`       | Sortable table with column headers announced                                                           |
| Auto-refresh | `aria-live="assertive"`       | Announcements only on data change, not every refresh cycle                                             |

### Transaction Detail

| Element         | Accessibility                                                                                                                        |
| --------------- | ------------------------------------------------------------------------------------------------------------------------------------ |
| Risk Score      | `aria-valuenow="78"` `aria-valuemin="0"` `aria-valuemax="100"` `aria-valuetext="Medium-high risk, seventy-eight out of one hundred"` |
| Rules Triggered | Screen reader reads each rule with impact: "Rule DEV-001, New Device, added 25 points"                                               |
| Action Buttons  | Clear descriptive labels: "Approve this transaction", "Block this transaction and freeze account"                                    |
| Factor Scores   | List each factor with its contribution: "New device: scored 85 out of 100, weighted 25 points"                                       |

### Case Management

| Element             | Accessibility                                                                                                                                                                              |
| ------------------- | ------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------------ |
| Case List           | Sortable table with `aria-sort` on columns                                                                                                                                                 |
| Case Detail         | Proper heading hierarchy (h1-h4)                                                                                                                                                           |
| Investigation Notes | `aria-live="polite"` for new notes                                                                                                                                                         |
| Timeline            | `role="list"` with `aria-label="Case timeline"`                                                                                                                                            |
| Transaction Graph   | Alternative text description: "Transaction flow diagram showing Layla K. sending 500,000 SYP to account 7712, which then sent 200,000 SYP to account 7713 and 150,000 SYP to account 7715" |

## Keyboard Navigation

| Function                | Shortcut        | Context            |
| ----------------------- | --------------- | ------------------ |
| Navigate between panels | Tab / Shift+Tab | All screens        |
| Open selected item      | Enter           | Tables, lists      |
| Quick actions menu      | Ctrl+K          | Dashboard          |
| Search                  | Ctrl+F          | All screens        |
| Next alert              | Alt+Down        | Dashboard          |
| Previous alert          | Alt+Up          | Dashboard          |
| Approve transaction     | A               | Transaction detail |
| Block transaction       | B               | Transaction detail |
| Create case             | C               | Transaction detail |
| Escalate                | E               | Case detail        |
| Confirm fraud           | F               | Case detail        |
| Mark false positive     | P               | Case detail        |
| Refresh dashboard       | R               | Dashboard          |

All shortcuts have visible labels in tooltips or a shortcut cheat sheet: `?` key shows all shortcuts.

## Contrast Requirements

| Element            | Minimum Contrast              | Our Implementation             |
| ------------------ | ----------------------------- | ------------------------------ |
| Normal text        | 4.5:1                         | 5.2:1 (#333 on #FFF)           |
| Large text (18px+) | 3:1                           | 4.5:1 (#222 on #FFF)           |
| Risk indicators    | 3:1 for UI components         | Red #D32F2F on white = 4.1:1 ✓ |
| Disabled elements  | 3:1                           | #999 on #EEE = 3.1:1 ✓         |
| Charts             | Distinguishable without color | Patterns + labels + color      |

## Font & Typography

| Requirement  | Implementation                                       |
| ------------ | ---------------------------------------------------- |
| Font size    | Minimum 14px for data tables, 16px for body text     |
| Adjustable   | Font size slider in settings (14px-24px)             |
| Font family  | System font stack (Noto Sans Arabic for Arabic text) |
| Line height  | 1.5 minimum                                          |
| Text spacing | Supports user override (WCAG 1.4.12)                 |

## Reduced Motion

| Animation          | Reduced Motion Behavior                            |
| ------------------ | -------------------------------------------------- |
| Auto-refresh       | Static update (no fade/pulse)                      |
| Alert slide-in     | Instant appear (no slide)                          |
| Risk score gauge   | Instant number change (no animation)               |
| Status transitions | Instant change (no crossfade)                      |
| Push notifications | Standard system notification (no custom animation) |

Detect via `prefers-reduced-motion: reduce` media query.

## Screen Reader Announcements for Live Updates

| Event                   | Announcement                                                                             |
| ----------------------- | ---------------------------------------------------------------------------------------- |
| New P0 alert            | "Critical alert: Account takeover in progress. User ID 8492. Amount 500 thousand SYP."   |
| New P1 alert            | "High alert: Suspicious transaction detected. Transaction ID 28492."                     |
| Auto-refresh complete   | "Dashboard updated. Fraud rate zero point zero eight percent. 2 P0 alerts, 5 P1 alerts." |
| Case status changed     | "Case FR-2025-5678 status changed to confirmed fraud."                                   |
| False positive resolved | "False positive resolved for transaction 28492. Transaction approved."                   |
| Rule deployed           | "New rule VEL-051 deployed in monitoring mode."                                          |

## Operations Team Accessibility Needs

| Role                                | Accessibility Need           | Solution                                    |
| ----------------------------------- | ---------------------------- | ------------------------------------------- |
| Fraud analyst (visual impairment)   | Screen reader compatible     | Full ARIA support, keyboard nav             |
| Fraud analyst (mobility impairment) | Keyboard-only operation      | Command palette, keyboard shortcuts         |
| Fraud analyst (color blindness)     | Color-independent indicators | Text + icon + pattern for all risk levels   |
| Ops manager (reading difficulty)    | Clear, simple language       | Plain Arabic labels, tooltips               |
| All team members                    | Low-glare mode               | Dark mode option for 24/7 operations center |

## Testing Requirements

| Test            | Tool                         | Criteria                               |
| --------------- | ---------------------------- | -------------------------------------- |
| Screen reader   | NVDA / VoiceOver             | All screens navigable                  |
| Keyboard only   | Manual testing               | All functions accessible without mouse |
| Color blindness | Color Oracle / Sim Daltonism | All information conveyed without color |
| Contrast        | axe DevTools / WAVE          | Pass WCAG AA (AAA for critical alerts) |
| Reduced motion  | Manual testing               | All animations respect reduced-motion  |
| Zoom            | Browser zoom to 200%         | No content loss or overlap             |
| RTL (Arabic)    | Manual testing               | Proper Arabic layout and reading order |
