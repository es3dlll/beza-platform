# إعداد frontend/wap/

## الإنشاء
```bash
cd frontend
npx create-next-app@latest wap --typescript --app --tailwind --eslint --use-npm
cd wap
```

## إعدادات package.json
```json
{
  "name": "beza-wap",
  "version": "0.1.0",
  "private": true,
  "scripts": {
    "dev": "next dev -p 3002",
    "build": "next build",
    "start": "next start -p 3002",
    "test": "npx playwright test",
    "lint": "next lint"
  }
}
```

## next.config.ts
```typescript
import type { NextConfig } from 'next';
import withPWA from 'next-pwa';

const nextConfig: NextConfig = {
  reactStrictMode: true,
  // basePath: '',  // WAP في الجذر
  env: {
    NEXT_PUBLIC_API_URL: process.env.NEXT_PUBLIC_API_URL || 'http://localhost:8000',
  },
};

export default withPWA({
  dest: 'public',
  register: true,
  skipWaiting: true,
  disable: process.env.NODE_ENV === 'development',
})(nextConfig);
```

## إعدادات Tailwind
```javascript
// tailwind.config.ts
export default {
  content: ['./src/**/*.{ts,tsx}'],
  theme: {
    extend: {
      fontFamily: {
        sans: ['Noto Sans Arabic', 'sans-serif'],
      },
    },
  },
  plugins: [],
};
```

## RTL + Dark Mode
```css
/* src/app/globals.css */
@tailwind base;
@tailwind components;
@tailwind utilities;

@layer base {
  html {
    direction: rtl;
    font-family: 'Noto Sans Arabic', sans-serif;
  }
}

@media (prefers-color-scheme: dark) {
  /* تلقائي من Tailwind */
}
```
