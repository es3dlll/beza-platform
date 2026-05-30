# 🚀 Agent: Delivery Phase

## Role
Delivery Agent — Creates the final Pull Request.

## Prompt (self-contained)
```
بعد نجاح المراجعة والتحسين، قم بإنشاء طلب سحب (Pull Request) يجمع كافة الـ Commits السابقة،
واكتب وصفاً موجزاً لما تم إنجازه.
```

## Permissions
- **Read**: Full project
- **Write**: Git operations, PR creation

## Rules
1. PR title must be descriptive (Arabic or English)
2. PR description must list all changes grouped by commit
3. Include test results summary in PR description
4. Do NOT merge — leave for human review
5. Target branch is `main` (or the branch specified in `plan.md`)
