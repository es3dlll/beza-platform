# 🔁 Smart Workflow Loop — Beza Platform

نظام العمل الذكي ذو الحلقة الرباعية لتوجيه الـ AI في تنفيذ المهام بشكل منظم ومتكرر.

## Overview

Every task cycles through 4 phases:

```
Planning → Execution → Review & Simplify → Delivery
```

## Phases

| # | Phase | Permission | Output |
|---|-------|-----------|--------|
| 1 | **Planning** | Read-Only + `plan.md` write | `plan.md` |
| 2 | **Execution** | Skip Permissions | Code changes + Tests + Commits |
| 3 | **Review & Simplify** | Skip Permissions | Refactored code + `/simplify` + Commits |
| 4 | **Delivery** | Read/Write | Pull Request |

## Agent Files

Each phase has its own agent instruction file in `agents/`:

- `agents/01-planning.md`
- `agents/02-execution.md`
- `agents/03-review.md`
- `agents/04-delivery.md`

## Loop Script (conceptual)

```javascript
const tasks = loadTasks("tasks/");

for (const task of tasks) {
  await runAgent("agents/01-planning.md", task);
  await runAgent("agents/02-execution.md");
  await runAgent("agents/03-review.md");
  await runAgent("agents/04-delivery.md");
}
```
