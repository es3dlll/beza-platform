# Next.js API Routes — Proxy لـ Laravel

Next.js API Routes تعمل كـ proxy بين المتصفح و Laravel API.
السبب: ضبط Cookie (HttpOnly) من Server Side — JS لا يستطيع ضبط HttpOnly Cookie.

## src/app/wap/api/auth/login/route.ts
```typescript
import { NextRequest, NextResponse } from 'next/server';

export async function POST(request: NextRequest) {
  const body = await request.json();
  const apiUrl = process.env.NEXT_PUBLIC_API_URL;

  const response = await fetch(`${apiUrl}/api/v1/wap/auth/login`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json' },
    body: JSON.stringify({ ...body, device: 'wap' }),
  });

  const data = await response.json();

  if (!response.ok) {
    return NextResponse.json(data, { status: response.status });
  }

  // ضبط Cookie من استجابة Laravel
  const nextResponse = NextResponse.json(data);
  const setCookie = response.headers.get('set-cookie');
  if (setCookie) {
    nextResponse.headers.set('Set-Cookie', setCookie);
  }

  return nextResponse;
}
```

## src/app/wap/api/auth/refresh/route.ts
```typescript
export async function POST(request: NextRequest) {
  const cookie = request.headers.get('cookie');
  const apiUrl = process.env.NEXT_PUBLIC_API_URL;

  const response = await fetch(`${apiUrl}/api/v1/wap/auth/refresh`, {
    method: 'POST',
    headers: { 'Content-Type': 'application/json', 'Cookie': cookie || '' },
  });

  const data = await response.json();
  const nextResponse = NextResponse.json(data);

  const setCookie = response.headers.get('set-cookie');
  if (setCookie) {
    nextResponse.headers.set('Set-Cookie', setCookie);
  }

  return nextResponse;
}
```

## توجيه المسارات في Next.js
| مسار المتصفح | Next.js Route | Laravel API |
|-------------|---------------|-------------|
| `POST /wap/api/auth/login` | `src/app/wap/api/auth/login/route.ts` | `POST /api/v1/wap/auth/login` |
| `POST /wap/api/auth/logout` | `src/app/wap/api/auth/logout/route.ts` | `POST /api/v1/wap/auth/logout` |
| `POST /wap/api/auth/refresh` | `src/app/wap/api/auth/refresh/route.ts` | `POST /api/v1/wap/auth/refresh` |
| `GET /wap/api/wallet/balance` | `src/app/wap/api/wallet/balance/route.ts` | `GET /api/v1/wap/wallet/balance` |
| `POST /wap/api/wallet/transfer` | `src/app/wap/api/wallet/transfer/route.ts` | `POST /api/v1/wap/wallet/transfer` |
