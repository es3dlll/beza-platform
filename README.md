# Beza Platform — Syria's Financial Operating System

[![CI](https://github.com/es3dlll/beza-platform/actions/workflows/ci.yml/badge.svg)](https://github.com/es3dlll/beza-platform/actions/workflows/ci.yml)
![PHP](https://img.shields.io/badge/PHP-8.5-777BB4?logo=php)
![Laravel](https://img.shields.io/badge/Laravel-11-FF2D20?logo=laravel)
![Flutter](https://img.shields.io/badge/Flutter-3.41-02569B?logo=flutter)
![Tests](https://img.shields.io/badge/Tests-361%20passing-brightgreen)

**Beza (بزة)** is a full-stack financial operating system for Syria — serving 22M citizens and 6M diaspora with a **Laravel modular monolith**, **Flutter mobile app**, and **React admin panel**.

---

## Structure

```
beza-platform/
├── backend/          ← Laravel 11 API (31 modules, 361 tests)
│   └── README.md     ← Detailed backend docs
├── frontend/
│   ├── admin/        ← React admin panel
│   └── mobile/       ← Flutter super app (62 tests)
└── .opencode/        ← Plans, docs, ADRs (120+ files)
```

## Quick Links

| Area | Location |
|------|----------|
| Backend API | [`backend/`](backend/) — setup, test, run |
| Mobile App | [`frontend/mobile/`](frontend/mobile/) — Flutter |
| Admin Panel | [`frontend/admin/`](frontend/admin/) — React |
| Documentation | [`.opencode/docs/`](.opencode/docs/) — 120+ Markdown files |
| Build Plans | [`.opencode/plans/`](.opencode/plans/) — V0 through V5 |
| API Spec | [`backend/docs/openapi.yaml`](backend/docs/openapi.yaml) |

## Test Status

| Suite | Count | Status |
|-------|-------|--------|
| Backend Modules | 361 tests, 842 assertions | ✅ All passing |
| Flutter Mobile | 62 tests | ✅ All passing |

---

*Beza — المالية للجميع. Financial inclusion for everyone.*
