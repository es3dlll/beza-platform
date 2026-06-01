# I4: سير عمل Git وتهيئة المستودع

**المعرف:** `I4-git-workflow`  
**الوحدة:** ⚙️ بنية تحتية  
**الأولوية:** 🔴 P0 — حرجة  

---

## الهدف

تهيئة سير عمل Git للمشروع: الفروع، الالتزامات، والرفع.

## هيكل الفروع

```
main            ← الإنتاج (محمي)
  └─ dev        ← التطوير (محمي)
      ├─ feature/xxx  ← الميزات الجديدة
      ├─ fix/xxx      ← الإصلاحات
      └─ refactor/xxx ← إعادة الهيكلة
```

## قواعد التسمية

| النوع | النمط | مثال |
|-------|-------|------|
| ميزة جديدة | `feature/{code}-{short-name}` | `feature/a1-register` |
| إصلاح | `fix/{code}-{description}` | `fix/a1-phone-validation` |
| تحسين | `refactor/{module}-{what}` | `refactor/auth-middleware` |

## نمط الالتزام (Commit)

```
feat(module): A1-register user registration + auto wallet | ref: tasks/01-auth/A1-register.md
     ^         ^                                          ^
     |         |                                          └─ مرجع التوثيق
     |         └─ وصف مختصر بالإنكليزية
     └─ type: feat/fix/refactor/test/docs
```

### الأنواع المسموحة

| النوع | المعنى |
|-------|--------|
| `feat` | ميزة جديدة |
| `fix` | إصلاح خطأ |
| `refactor` | إعادة هيكلة بدون تغيير وظيفي |
| `test` | إضافة أو تعديل اختبارات |
| `docs` | توثيق فقط |
| `chore` | مهام تشغيلية (deps, config) |

## ملف .gitignore

```gitignore
/vendor/
/node_modules/
/.env
/.env.*
!/.env.example
/storage/**/*.key
!/storage/**/.gitkeep
/Horizon.php
/phpunit.xml
.phpunit.result.cache
*.log
.DS_Store
Thumbs.db
```

## بوّابات الجودة قبل الرفع

- [ ] `php artisan test` — جميع الاختبارات ناجحة
- [ ] `php artisan analyze` — بدون تحذيرات جديدة
- [ ] `git status` — لا ملفات غير مقصودة
- [ ] `git diff` — مراجعة التغييرات
- [ ] رسالة الالتزام تطابق النمط المتفق عليه

## معايير القبول

- [ ] الفروع `main` + `dev` منشأة
- [ ] `main` محمي (protected branch)
- [ ] `.gitignore` محدّث
- [ ] نمط الالتزام موثّق
- [ ] جميع أعضاء الفريق يتبعون نفس convention
