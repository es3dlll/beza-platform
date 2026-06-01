# 11. صفحة الهبوط التسويقية (Landing Page)

مستضافة بشكل منفصل - Next.js (SSG).

## 11.1 هيكل المشروع

```
landing-page/
├── components/
│   ├── Hero.jsx
│   ├── Features.jsx
│   ├── Testimonials.jsx
│   ├── Pricing.jsx
│   ├── FAQ.jsx
│   └── Footer.jsx
├── pages/
│   ├── index.js
│   ├── merchants.js (صفحة مخصصة للتجار)
│   ├── agents.js (صفحة مخصصة للوكلاء)
│   └── download.js (صفحة تحميل التطبيق)
└── public/
    └── images/
```

## 11.2 مكون Hero

```jsx
export default function Hero() {
  return (
    <section className="bg-gradient-to-r from-indigo-600 to-green-500 text-white">
      <div className="container mx-auto px-4 py-20 text-center">
        <h1 className="text-5xl md:text-6xl font-extrabold mb-6">
          بيزى .. مستقبل مالك بين يديك
        </h1>
        <p className="text-xl md:text-2xl mb-8 max-w-2xl mx-auto">
          محفظة رقمية مزدوجة العملة، بطاقات افتراضية، صفقات استثمارية في تمويل الشحنات،
          وخدمات مالية متكاملة.
        </p>
        <div className="flex flex-col sm:flex-row justify-center gap-4">
          <a
            href="/download"
            className="bg-white text-indigo-600 px-8 py-3 rounded-lg font-semibold hover:shadow-lg transition"
          >
            حمّل التطبيق الآن
          </a>
          <a
            href="/merchants"
            className="border-2 border-white px-8 py-3 rounded-lg font-semibold hover:bg-white hover:text-indigo-600 transition"
          >
            افتح متجرك مع Beza
          </a>
        </div>
        <div className="mt-12 flex justify-center gap-8">
          <img src="/images/google-play-badge.png" alt="Google Play" className="h-12" />
          <img src="/images/app-store-badge.png" alt="App Store" className="h-12" />
        </div>
      </div>
    </section>
  );
}
```
