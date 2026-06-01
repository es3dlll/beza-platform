# Upgrade Log — 2026-05-31

**Performed by:** Open Code AI / es3dlll  
**Backup branch:** `backup/pre-upgrade-20260531`  

## Summary
All packages were already at their latest compatible versions within `composer.json` constraints. No actual version bumps occurred.

## Packages Reviewed

| Package | Current | Latest Available | Action |
|---------|---------|-----------------|--------|
| laravel/framework | v11.54.0 | v13.12.0 | Deferred — major version jump requires Laravel upgrade guide review |
| phpunit/phpunit | 11.5.55 | 13.1.13 | Deferred — major version jump requires PHPUnit upgrade guide review |
| laravel/tinker | 2.11.1 | 3.0.2 | Deferred — major version jump |
| brianium/paratest | 7.8.5 | 7.22.4 | Not updated — constraint ^7.8, still compatible |

## Manual Changes Required
- **None.** All packages stayed within their current major versions.

## Test Results

| Phase | Tests | Assertions | Failures |
|-------|-------|-----------|----------|
| Baseline (pre-update) | 153 | 559 | 0 |
| Post-update | 153 | 559 | 0 |

## OpenAPI Spec
No drift detected — spec regenerated and checked via `git diff --exit-code`.

## Frontend
- **React (admin):** Not set up yet — no `package.json` in `frontend/admin/`
- **Flutter (mobile):** Not set up yet — `frontend/mobile/` does not exist

## Infrastructure
- **Docker:** Not configured
- **Dev tools:** Not globally installed (PHP-CS-Fixer, PHPStan, Rector)

## Environment Variables
`.env.example` contains CBS and alerting variables (`CBS_API_BASE_URL`, `ALERTING_PAGERDUTY_KEY`, etc.) not present in local `.env`. These are expected for production/staging only.

## Deferred Upgrades (Requires Manual Review)
1. **Laravel 11 → 13**: Review https://laravel.com/docs/upgrade for breaking changes (Laravel 12 upgrade guide may apply)
2. **PHPUnit 11 → 13**: Review PHPUnit 12+ changelog for breaking changes
3. **Tinker 2 → 3**: Review tinker changelog

## Rollback Plan
If any issue arises:
```bash
git checkout backup/pre-upgrade-20260531
composer install --no-interaction
```
