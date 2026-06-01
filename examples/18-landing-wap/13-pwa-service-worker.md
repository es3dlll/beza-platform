# PWA + Service Worker

## Manifest
```json
{
  "name": "Beza WAP",
  "short_name": "Beza",
  "description": "محفظة رقمية سريعة وخفيفة",
  "start_url": "/wap/dashboard",
  "display": "standalone",
  "background_color": "#0f172a",
  "theme_color": "#3b82f6",
  "dir": "rtl",
  "lang": "ar",
  "icons": [
    { "src": "/icons/icon-192.png", "sizes": "192x192", "type": "image/png" },
    { "src": "/icons/icon-512.png", "sizes": "512x512", "type": "image/png" }
  ]
}
```

## استراتيجية Cache
| النوع | الاستراتيجية |
|-------|-------------|
| صفحات HTML | Network First (cache fallback) |
| CSS/JS (built) | Cache First (stale-while-revalidate) |
| Fonts/SVG | Cache First (long TTL) |
| API GET | Network First + cache (15 min) |
| API POST | Network Only (أو Offline Queue) |

## Pre-cache List
```
/wap/login
/wap/dashboard
/wap/user
/wap/merchant
/wap/agent
/fonts/NotoSansArabic.woff2
/icons/icon-192.png
/icons/icon-512.png
```

## Install Prompt
مكون `InstallPrompt` يعرض زر "تثبيت التطبيق" للمستخدمين الذين لم يثبتوه بعد. يختفي إذا كان التطبيق مثبتاً أو المتصفح لا يدعم PWA.
