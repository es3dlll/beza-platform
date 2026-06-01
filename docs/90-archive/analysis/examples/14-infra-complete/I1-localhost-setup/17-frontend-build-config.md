# 17 - إعداد بناء الواجهات (Frontend Build Configuration)

## Admin Dashboard (Vite)

```javascript
// admin-dashboard/vite.config.js
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    server: { port: 5173, strictPort: true },
    build: { outDir: 'dist', sourcemap: false },
});
```

## User Frontend (Vite)

```javascript
// user-frontend/vite.config.js
import { defineConfig } from 'vite';
import react from '@vitejs/plugin-react';

export default defineConfig({
    plugins: [react()],
    server: {
        port: 5174,
        strictPort: true,
        proxy: { '/api': { target: 'http://localhost:8000', changeOrigin: true } },
    },
});
```

## Landing Page (Next.js)

```javascript
// landing-page/next.config.js
const nextConfig = {
    async rewrites() {
        return [{ source: '/api/:path*', destination: 'http://localhost:8000/api/:path*' }];
    },
};
module.exports = nextConfig;
```

## متغيرات البيئة

```env
# admin-dashboard/.env
VITE_API_URL=http://localhost:8000/api/v1

# user-frontend/.env
VITE_API_URL=http://localhost:8000/api/v1

# landing-page/.env.local
NEXT_PUBLIC_API_URL=http://localhost:8000/api/v1
```
