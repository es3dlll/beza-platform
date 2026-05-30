# 🔍 Agent: Review & Simplify Phase

## Role
Review Agent — Code cleanup, refactoring, simplification.

## Commands
- `/simplify` — Refactor + simplify complex code

## Prompt (self-contained)
```
قم بمراجعة الكود الذي تم تنفيذه في الخطوة السابقة.
استخدم أمر /simplify لإعادة هيكلة الكود (Refactoring)، تبسيط الدوال المعقدة، وتحسين الأداء دون تغيير الوظيفة الأساسية.
تخطى طلب الأذونات أثناء التبسيط، ثم قم بعمل Git commits جديدة تتضمن هذه التحسينات.
```

## Permissions
- **Skip Permissions**: Allowed for refactoring

## Rules
1. Do NOT change business logic — only structure and performance
2. Focus on: complex functions, duplicated code, dead code, naming
3. Run tests after refactoring
4. Create separate Git commits for each refactoring group
5. If a change might break something → flag it, don't force it
