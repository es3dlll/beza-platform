# 8. الواجهة الأمامية: موقع المستخدم العادي (React SPA)

## 8.1 هيكل المشروع مع تحسينات SEO

```
user-frontend/
├── src/
│   ├── components/
│   │   ├── Layout/
│   │   │   ├── Header.jsx
│   │   │   ├── Footer.jsx
│   │   │   └── MobileNav.jsx
│   │   ├── Wallet/
│   │   │   ├── BalanceCard.jsx
│   │   │   └── TransactionHistory.jsx
│   │   ├── Transfer/
│   │   │   ├── TransferForm.jsx
│   │   │   └── QrScanner.jsx
│   │   └── Common/
│   │       ├── SEO.jsx
│   │       └── LoadingSpinner.jsx
│   ├── pages/
│   │   ├── HomePage.jsx
│   │   ├── Dashboard.jsx
│   │   ├── TransferPage.jsx
│   │   ├── InvestPage.jsx
│   │   ├── CardsPage.jsx
│   │   ├── MerchantPayPage.jsx
│   │   ├── ProfilePage.jsx
│   │   └── SupportPage.jsx
│   └── App.jsx
```

## 8.2 مكون SEO المحسن

```jsx
import { Helmet } from 'react-helmet-async';

export default function SEO({ title, description, keywords, image, url, type = 'website' }) {
  const siteUrl = 'https://beza.com';
  const defaultImage = `${siteUrl}/og-image.jpg`;

  return (
    <Helmet>
      <html lang="ar" dir="rtl" />
      <title>{title} | Beza</title>
      <meta name="description" content={description} />
      <meta name="keywords" content={keywords} />
      <meta property="og:title" content={title} />
      <meta property="og:description" content={description} />
      <meta property="og:image" content={image || defaultImage} />
      <meta property="og:url" content={url || siteUrl} />
      <meta property="og:type" content={type} />
      <meta name="twitter:card" content="summary_large_image" />
      <link rel="canonical" href={url || siteUrl} />
    </Helmet>
  );
}
```
