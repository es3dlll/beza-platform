# Current Infrastructure Versions

> **Last updated:** 2026-05-31 by Open Code AI / es3dlll
> **Upgrade status:** All packages already at latest compatible versions within `composer.json` constraints (^11, ^7, etc.)
> **Deferred upgrades:** Laravel 11→13, PHPUnit 11→13, Tinker 2→3 — require manual upgrade guide review

## PHP Runtime
| Component | Version | Source |
|-----------|---------|--------|
| PHP | 8.5.6 (NTS VC17 x64) | Laragon |
| MySQL | 8.4.3 (Community Server) | Laragon |
| Redis | Not installed locally (available via Laragon) | — |

## Backend (Composer)

| Package | Version |
|---------|---------|
| laravel/framework | v11.54.0 |
| laravel/pail | v1.2.7 |
| laravel/pint | v1.29.1 |
| laravel/prompts | v0.3.18 |
| laravel/sail | v1.61.0 |
| laravel/sanctum | v4.3.2 |
| laravel/tinker | v2.11.1 |
| phpunit/phpunit | 11.5.55 |
| nunomaduro/collision | v8.9.4 |
| guzzlehttp/guzzle | 7.10.5 |
| spatie/... | Not installed |

## Frontend Admin (React)
> Not yet set up — `frontend/admin/src/` contains individual `.jsx`/`.css` files only (no `package.json`)

## Mobile (Flutter)
> Not yet set up — `frontend/mobile/` does not exist

## Infrastructure
| Tool | Version |
|------|---------|
| Node.js | v26.1.0 |
| npm | 11.13.0 |
| Flutter | 3.41.9 (stable) — Dart 3.11.5 |
| Docker | Not configured |

## CI / Tooling
| Tool | Version |
|------|---------|
| Composer | 2.9.4 |
| Git | (per environment) |
