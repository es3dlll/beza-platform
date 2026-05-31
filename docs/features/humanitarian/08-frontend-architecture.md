# Frontend Architecture

## Stack
- **Framework:** Next.js 14+ (App Router) — ISR for static NGO dashboard pages
- **UI Library:** shadcn/ui + Tailwind CSS — RTL-aware component library
- **State Management:** Zustand (client state) + TanStack Query (server state)
- **Forms:** React Hook Form + Zod validation
- **Charts:** Recharts for spending dashboards
- **Map:** MapLibre GL (leaflet-based, no Google Maps due to Syria sanctions)
- **PWA:** Agent app uses Next.js PWA wrapper with service worker for offline

## Module Architecture

```
frontend/
  app/
    [locale]/                          # Arabic-first routing
      (public)/                        # Public pages
      (ngo)/                           # NGO dashboard (auth required)
        dashboard/
        programs/
        beneficiaries/
        distributions/
        reports/
        compliance/
      (agent)/                         # Agent mobile web app
        verify/
        distributions/
      (merchant)/                      # Merchant mobile web app
        redeem/
        settlements/
      (donor)/                         # Donor portal
        reports/
  components/
    features/
      humanitarian/
        programs/
        beneficiaries/
        distributions/
        vouchers/
        monitoring/
        reporting/
        compliance/
    shared/                            # Shared UI components
  lib/
    api-client/
    hooks/
    utils/
```

## Design System Adaptations for Humanitarian
| Standard Component | Humanitarian Adaptation |
|-------------------|------------------------|
| Buttons | Large touch targets (min 48px) for field use with gloves |
| Typography | Arabic-optimised font (Noto Naskh Arabic), minimum 16px body |
| Colours | High-contrast mode for outdoor agent use |
| Alerts | SMS-like notification banners for low-bandwidth |
| Forms | Voice-input fields for illiterate beneficiaries |
| Tables | Paginated with CSV download, never infinite scroll (agent may lose connection) |
