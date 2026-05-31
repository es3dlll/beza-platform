# 03 - تدفق البيانات (Data Flow Sequence)

## تحميل الصفحة (Page Load)

```
  Browser              main.jsx              Dashboard.jsx           API Server
    │                      │                      │                     │
    │  URL: /?sakk_token=  │                      │                     │
    │─────────────────────>│                      │                     │
    │                      │  save token           │                     │
    │                      │  history.replace()    │                     │
    │                      │──────────────────────>│                     │
    │                      │                      │                     │
    │                      │  createRoot(App)      │                     │
    │                      │──────────────────────>│                     │
    │                      │                      │                     │
    │                      │                      │  useEffect[1]       │
    │                      │                      │  GET /wallet/balance│
    │                      │                      │─────────────────────>│
    │                      │                      │  Response: wallets  │
    │                      │                      │<─────────────────────│
    │                      │                      │                     │
    │                      │                      │  useEffect[2]       │
    │                      │                      │  GET /auth/me       │
    │                      │                      │─────────────────────>│
    │                      │                      │  Response: user     │
    │                      │                      │<─────────────────────│
    │                      │                      │                     │
    │                      │                      │  useEffect[3]       │
    │                      │                      │  Generate QR        │
    │                      │                      │  (qrcode library)   │
    │                      │                      │                     │
    │                      │                      │  useEffect[4]       │
    │                      │                      │  GET /wallet/rates  │
    │                      │                      │─────────────────────>│
    │                      │                      │  Response: USD_SYP  │
    │                      │                      │<─────────────────────│
    │                      │                      │                     │
    │  Render Dashboard    │                      │                     │
    │<─────────────────────│──────────────────────│                     │
```

## تدفق الإرسال (Send Flow)

```
  User          Dashboard.jsx          API (POST /transfer)
   │                   │                        │
   │  Click "إرسال"    │                        │
   │──────────────────>│                        │
   │                   │  showSend = true       │
   │                   │  Bottom Sheet appears  │
   │                   │                        │
   │  Fill form:       │                        │
   │  phone, amount,   │                        │
   │  currency, pin,   │                        │
   │  description      │                        │
   │──────────────────>│                        │
   │                   │                        │
   │  Click تأكيد       │                        │
   │──────────────────>│                        │
   │                   │  POST /transfer        │
   │                   │  {to_phone, amount,    │
   │                   │   currency, pin,       │
   │                   │   description}         │
   │                   │───────────────────────>│
   │                   │                        │
   │                   │  Response: نجاح/فشل    │
   │                   │<───────────────────────│
   │                   │                        │
   │  Show message     │                        │
   │<──────────────────│                        │
```

## تدفق الصرف (Exchange Flow)

```
  User          Dashboard.jsx          API (POST /wallet/exchange)
   │                   │                        │
   │  Enter amount     │                        │
   │──────────────────>│                        │
   │                   │  Compute result locally │
   │                   │  result = amt * rate   │
   │                   │                        │
   │  Click "صرف"      │                        │
   │──────────────────>│                        │
   │                   │  Loading state         │
   │                   │  POST /wallet/exchange │
   │                   │  {from, to, amount}    │
   │                   │───────────────────────>│
   │                   │                        │
   │                   │  Response: {amount_sent│
   │                   │   amount_received,     │
   │                   │   from_currency,       │
   │                   │   to_currency}         │
   │                   │<───────────────────────│
   │                   │                        │
   │  Show result      │                        │
   │  (نجاح/فشل)       │                        │
   │<──────────────────│                        │
```

## تدفق الإشعارات (Notification Dropdown)

```
  User          Dashboard.jsx
   │                   │
   │  Click bell icon  │
   │──────────────────>│
   │                   │  showNotifs = !showNotifs
   │                   │
   │                   │  useEffect[mousedown]
   │                   │  → outside click يخفي
   │                   │
   │  Click notification│
   │──────────────────>│
   │                   │  setShowNotifs(false)
   │                   │  navigate(/notifications/:id)
   │                   │
   │  Click "جميع"     │
   │──────────────────>│
   │                   │  navigate(/notifications)
```
