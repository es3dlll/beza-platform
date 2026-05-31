# 17 - تطبيق React (React Implementation) - 00 - فهرس ملفات L1 (صفحة الهبوط التسويقية)

## هيكل الملفات

```
landing/
├── app/
│   ├── layout.tsx
│   ├── page.tsx
│   ├── merchants/page.tsx
│   ├── agents/page.tsx
│   └── download/page.tsx
├── components/
│   ├── Header.tsx
│   ├── Footer.tsx
│   ├── Hero.tsx
│   ├── Features.tsx
│   ├── Testimonials.tsx
│   ├── Pricing.tsx
│   ├── FAQ.tsx
│   ├── ContactForm.tsx
│   ├── NewsletterForm.tsx
│   └── DownloadLinks.tsx
├── lib/
│   ├── api.ts
│   └── metadata.ts
├── public/
│   ├── images/
│   └── fonts/
└── styles/
    └── globals.css
```

## Layout

```tsx
// app/layout.tsx
import type { Metadata } from 'next';
import Header from '@/components/Header';
import Footer from '@/components/Footer';
import './globals.css';

export const metadata: Metadata = {
  title: 'Beza | المنصة الرقمية للمدفوعات',
  description: 'حوّل أموالك بسهولة وأمان مع Beza - محفظة رقمية، بطاقات مسبقة الدفع، تحويلات فورية',
  openGraph: {
    title: 'Beza - الدفع الرقمي',
    description: 'محفظة رقمية، بطاقات مسبقة الدفع، تحويلات فورية',
    images: ['/og-image.png'],
  },
};

export default function RootLayout({ children }: { children: React.ReactNode }) {
  return (
    <html lang="ar" dir="rtl">
      <body>
        <Header />
        <main>{children}</main>
        <Footer />
      </body>
    </html>
  );
}
```

## الصفحة الرئيسية

```tsx
// app/page.tsx
import Hero from '@/components/Hero';
import Features from '@/components/Features';
import Testimonials from '@/components/Testimonials';
import Pricing from '@/components/Pricing';
import FAQ from '@/components/FAQ';
import ContactForm from '@/components/ContactForm';

export default function HomePage() {
  return (
    <>
      <Hero />
      <Features />
      <Testimonials />
      <Pricing />
      <FAQ />
      <ContactForm />
    </>
  );
}
```

## Hero Component

```tsx
// components/Hero.tsx
import DownloadLinks from './DownloadLinks';

export default function Hero() {
  return (
    <section className="hero">
      <div className="container">
        <h1>حوّل أموالك بسهولة وأمان</h1>
        <p className="subtitle">
          محفظة رقمية، بطاقات مسبقة الدفع، وتحويلات فورية في جميع أنحاء سوريا
        </p>
        <div className="cta-buttons">
          <DownloadLinks />
          <a href="/merchants" className="btn btn-outline">
            فتح متجر إلكتروني
          </a>
        </div>
        <div className="hero-stats">
          <div className="stat">
            <span className="stat-number">+50,000</span>
            <span className="stat-label">مستخدم نشط</span>
          </div>
          <div className="stat">
            <span className="stat-number">+1,000</span>
            <span className="stat-label">تاجر</span>
          </div>
          <div className="stat">
            <span className="stat-number">+200</span>
            <span className="stat-label">وكيل</span>
          </div>
        </div>
      </div>
    </section>
  );
}
```

## Features Component

```tsx
// components/Features.tsx
const features = [
  {
    icon: '💰',
    title: 'محفظة رقمية',
    desc: 'قم بتخزين أموالك وإرسالها واستلامها بسهولة عبر محفظة Beza الرقمية',
  },
  {
    icon: '💳',
    title: 'بطاقات مسبقة الدفع',
    desc: 'احصل على بطاقة Beza للتسوق عبر الإنترنت وفي المحلات',
  },
  {
    icon: '🔄',
    title: 'صفقات وعروض',
    desc: 'استفد من العروض والخصومات الحصرية في متجر Beza',
  },
  {
    icon: '🏢',
    title: 'وكلاء معتمدون',
    desc: 'أودع واسحب النقود من خلال شبكة وكلائنا المنتشرين في كل المحافظات',
  },
];

export default function Features() {
  return (
    <section id="features" className="features">
      <div className="container">
        <h2>مميزات Beza</h2>
        <div className="features-grid">
          {features.map((f, i) => (
            <div key={i} className="feature-card">
              <div className="feature-icon">{f.icon}</div>
              <h3>{f.title}</h3>
              <p>{f.desc}</p>
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
```

## FAQ Component

```tsx
// components/FAQ.tsx
'use client';

import { useState } from 'react';

const faqs = [
  { q: 'ما هي Beza؟', a: 'Beza منصة رقمية للمدفوعات تقدم محفظة رقمية، بطاقات مسبقة الدفع، وتحويلات فورية.' },
  { q: 'كيف يمكنني تحميل التطبيق؟', a: 'يمكنك تحميل التطبيق من Google Play أو App Store عبر زر التحميل في أعلى الصفحة.' },
  { q: 'هل يمكنني فتح متجر في Beza؟', a: 'نعم، يمكنك فتح متجر إلكتروني والبدء في البيع مباشرة بعد الموافقة على طلبك.' },
  { q: 'ما هي رسوم Beza؟', a: 'تحميل التطبيق مجاني، التحويلات بين المستخدمين مجانية. هناك رسوم بسيطة للسحب النقدي.' },
];

export default function FAQ() {
  const [openIndex, setOpenIndex] = useState<number | null>(null);

  return (
    <section id="faq" className="faq">
      <div className="container">
        <h2>الأسئلة الشائعة</h2>
        <div className="faq-list">
          {faqs.map((faq, i) => (
            <div key={i} className={`faq-item ${openIndex === i ? 'open' : ''}`}>
              <button onClick={() => setOpenIndex(openIndex === i ? null : i)}>
                {faq.q}
                <span>{openIndex === i ? '−' : '+'}</span>
              </button>
              {openIndex === i && <p className="faq-answer">{faq.a}</p>}
            </div>
          ))}
        </div>
      </div>
    </section>
  );
}
```

## NewsletterForm

```tsx
// components/NewsletterForm.tsx
'use client';

import { useState, FormEvent } from 'react';

export default function NewsletterForm() {
  const [email, setEmail] = useState('');
  const [status, setStatus] = useState<'idle' | 'loading' | 'success' | 'error'>('idle');
  const [message, setMessage] = useState('');

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setStatus('loading');

    try {
      const res = await fetch('/api/newsletter/subscribe', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ email, source: 'footer' }),
      });

      if (res.ok) {
        setStatus('success');
        setMessage('تم الاشتراك بنجاح!');
        setEmail('');
      } else {
        const data = await res.json();
        setStatus('error');
        setMessage(data.message || 'فشل الاشتراك');
      }
    } catch {
      setStatus('error');
      setMessage('حدث خطأ، يرجى المحاولة لاحقاً');
    }
  };

  return (
    <form onSubmit={handleSubmit} className="newsletter-form">
      <input
        type="email"
        value={email}
        onChange={(e) => setEmail(e.target.value)}
        placeholder="أدخل بريدك الإلكتروني"
        required
        dir="ltr"
      />
      <button type="submit" disabled={status === 'loading'}>
        {status === 'loading' ? 'جاري...' : 'اشترك'}
      </button>
      {message && <p className={`newsletter-message ${status}`}>{message}</p>}
    </form>
  );
}
```

## ContactForm

```tsx
// components/ContactForm.tsx
'use client';

import { useState, FormEvent } from 'react';

export default function ContactForm() {
  const [form, setForm] = useState({ name: '', email: '', subject: '', message: '' });
  const [status, setStatus] = useState({ submitting: false, success: false, error: '' });

  const handleSubmit = async (e: FormEvent) => {
    e.preventDefault();
    setStatus({ submitting: true, success: false, error: '' });

    try {
      const res = await fetch('/api/contact', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify(form),
      });

      if (res.ok) {
        setStatus({ submitting: false, success: true, error: '' });
        setForm({ name: '', email: '', subject: '', message: '' });
      } else {
        const data = await res.json();
        setStatus({ submitting: false, success: false, error: data.message || 'فشل الإرسال' });
      }
    } catch {
      setStatus({ submitting: false, success: false, error: 'حدث خطأ في الاتصال' });
    }
  };

  if (status.success) {
    return <div className="contact-success">شكراً لتواصلك! سنرد عليك قريباً.</div>;
  }

  return (
    <form onSubmit={handleSubmit} className="contact-form">
      <input name="name" placeholder="الاسم" value={form.name} onChange={e => setForm({...form, name: e.target.value})} required />
      <input name="email" type="email" placeholder="البريد الإلكتروني" value={form.email} onChange={e => setForm({...form, email: e.target.value})} required dir="ltr" />
      <input name="subject" placeholder="الموضوع" value={form.subject} onChange={e => setForm({...form, subject: e.target.value})} required />
      <textarea name="message" placeholder="رسالتك" value={form.message} onChange={e => setForm({...form, message: e.target.value})} required rows={5} />
      {status.error && <p className="form-error">{status.error}</p>}
      <button type="submit" disabled={status.submitting}>
        {status.submitting ? 'جاري الإرسال...' : 'إرسال'}
      </button>
    </form>
  );
}
```

## CSS (Tailwind — globals.css)

```css
/* styles/globals.css */
@tailwind base;
@tailwind components;
@tailwind utilities;

:root { --primary: #2563eb; --primary-dark: #1d4ed8; --bg: #ffffff; --text: #111827; }

.container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }

.hero { background: linear-gradient(135deg, #2563eb 0%, #1e40af 100%); color: white; padding: 80px 0; text-align: center; }
.hero h1 { font-size: 3rem; margin-bottom: 16px; }
.hero .subtitle { font-size: 1.2rem; opacity: 0.9; max-width: 600px; margin: 0 auto 32px; }
.cta-buttons { display: flex; gap: 16px; justify-content: center; flex-wrap: wrap; }
.hero-stats { display: flex; justify-content: center; gap: 48px; margin-top: 48px; }
.stat { text-align: center; }
.stat-number { display: block; font-size: 2rem; font-weight: 800; }
.stat-label { opacity: 0.8; font-size: 0.9rem; }

.features { padding: 80px 0; text-align: center; }
.features-grid { display: grid; grid-template-columns: repeat(auto-fit, minmax(250px, 1fr)); gap: 24px; margin-top: 40px; }
.feature-card { padding: 24px; border-radius: 12px; background: #f9fafb; }
.feature-icon { font-size: 3rem; margin-bottom: 16px; }

.faq { padding: 80px 0; background: #f9fafb; }
.faq-item { background: white; border-radius: 8px; margin-bottom: 8px; border: 1px solid #e5e7eb; }
.faq-item button { width: 100%; padding: 16px; display: flex; justify-content: space-between; font-weight: 600; cursor: pointer; border: none; background: none; }
.faq-answer { padding: 0 16px 16px; color: #6b7280; }

.newsletter-form { display: flex; gap: 8px; max-width: 400px; margin: 0 auto; }
.newsletter-form input { flex: 1; padding: 12px; border: 1px solid #d1d5db; border-radius: 8px; font-size: 16px; }
.newsletter-form button { padding: 12px 24px; background: var(--primary); color: white; border: none; border-radius: 8px; cursor: pointer; }

.btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; text-decoration: none; display: inline-block; }
.btn-outline { border: 2px solid white; color: white; }
```
