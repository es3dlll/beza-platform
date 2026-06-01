# 03 - تدفق البيانات (SSG + نماذج الاتصال)

## 1. تحميل الصفحة (Static Generation)

```
Browser                        CDN                        Build Time
  │                             │                             │
  │  ─── GET / ───────────────►│                             │
  │                             │  (HTML جاهز من الـ build)   │
  │  ◄── HTML + CSS + JS ─────│                             │
  │                             │                             │
  │  ─── GET /images/* ──────►│                             │
  │  ◄── Static Images ──────│                             │
  │                             │                             │
  │  [Next.js Hydration]        │                             │
  │  [Interactive Components]   │                             │
```

## 2. إرسال نموذج الاتصال

```
User                         Next.js (Client)              Laravel API              MySQL
  │                              │                             │                       │
  │  يملأ النموذج                 │                             │                       │
  │  يضغط "إرسال"                 │                             │                       │
  │ ──────────────────────────►│                             │                       │
  │                              │  POST /api/contact          │                       │
  │                              │ ──────────────────────────►│                       │
  │                              │                             │  Validate Input        │
  │                              │                             │  ────────────────►    │
  │                              │                             │  INSERT INTO contacts  │
  │                              │                             │ ────────────────────►│
  │                              │                             │  ◄── OK ─────────────│
  │                              │  ◄── { success: true } ────│                       │
  │  ◄── تأكيد الإرسال ────────│                             │                       │
  │                              │                             │  Queue: Send Email     │
  │                              │                             │  ────────────────►    │
```

## 3. الاشتراك في النشرة البريدية

```
User                         Next.js (Client)              Laravel API              MySQL
  │                              │                             │                       │
  │  يدخل البريد الإلكتروني      │                             │                       │
  │ ──────────────────────────►│                             │                       │
  │                              │  POST /api/newsletter       │                       │
  │                              │ ──────────────────────────►│                       │
  │                              │                             │  Validate Email        │
  │                              │                             │  Check Duplicate       │
  │                              │                             │  ────────────────►    │
  │                              │                             │  INSERT INTO subscribers│
  │                              │                             │ ────────────────────►│
  │                              │                             │  ◄── OK ─────────────│
  │  ◄── تأكيد الاشتراك ──────│                             │                       │
  │                              │                             │  Send Welcome Email    │
```

## 4. build ونشر SSG

```
Developer                         Next.js Build                  Vercel
  │                                  │                             │
  │  git push                        │                             │
  │ ───────────────────────────────►│                             │
  │                                  │  next build                  │
  │                                  │  ├── generate HTML          │
  │                                  │  ├── generate CSS           │
  │                                  │  ├── optimize images        │
  │                                  │  ├── generate sitemap.xml   │
  │                                  │  └── generate robots.txt    │
  │                                  │                             │
  │                                  │  next export                │
  │                                  │ ──────────────────────────►│
  │                                  │                             │  Deploy to Edge
  │                                  │                             │  ────────────────►
  │                                  │                             │  CDN Ready!
```

## 5. مخطط تدفق SEO

```
Build Time:
  ├── getStaticProps() → Fetch content (from MDX / CMS)
  ├── generateMetadata() → title, description, OG tags
  ├── generateStaticParams() → /merchants, /agents, /download
  └── sitemap.xml generation

Runtime (CDN):
  ├── HTTP Headers (Cache-Control: public, max-age=31536000)
  ├── robots.txt
  └── 404.html for unknown routes
```
