# Offline Queue — IndexedDB + Background Sync

## IndexedDB Schema
```typescript
interface QueueItem {
  id?: number;
  method: 'POST' | 'PUT' | 'PATCH' | 'DELETE';
  endpoint: string;
  body: Record<string, unknown>;
  headers: Record<string, string>;
  idempotency_key: string;
  status: 'pending' | 'processing' | 'completed' | 'failed';
  created_at: number;
  retry_count: number;
  max_retries: number;        // default: 5
  last_error: string | null;
  last_attempt: number | null;
}
```

## OfflineQueueService (TypeScript)
```typescript
class OfflineQueueService {
  private dbName = 'beza_wap_offline';
  private storeName = 'queue';

  async add(method: string, endpoint: string, body: object, idempotencyKey: string): Promise<void> {
    const db = await this.openDB();
    const tx = db.transaction(this.storeName, 'readwrite');
    await tx.store.add({
      method, endpoint, body,
      idempotency_key: idempotencyKey,
      status: 'pending',
      created_at: Date.now(),
      retry_count: 0,
      max_retries: 5,
    });
  }

  async processQueue(): Promise<void> {
    const db = await this.openDB();
    const items = await db.getAll(this.storeName);
    const pending = items.filter(i => i.status === 'pending');

    for (const item of pending) {
      item.status = 'processing';
      await db.put(this.storeName, item);

      try {
        const res = await fetch(item.endpoint, {
          method: item.method,
          headers: { 'Content-Type': 'application/json', ...item.headers },
          body: JSON.stringify(item.body),
        });
        if (res.ok) {
          item.status = 'completed';
        } else {
          throw new Error(`HTTP ${res.status}`);
        }
      } catch (err) {
        item.retry_count++;
        item.last_error = err.message;
        item.last_attempt = Date.now();
        item.status = item.retry_count >= item.max_retries ? 'failed' : 'pending';
      }
      await db.put(this.storeName, item);
    }
  }
}
```

## التكامل مع Service Worker
```javascript
// Service Worker — fetch event
self.addEventListener('fetch', (event) => {
  const { method, url } = event.request;

  // فقط POST المؤهلة (تحويلات)
  if (method === 'POST' && url.includes('/api/v1/wap/wallet/transfer')) {
    event.respondWith(handleTransfer(event.request));
  }
});

async function handleTransfer(request) {
  try {
    return await fetch(request);  // حاول الشبكة أولاً
  } catch (err) {
    // فشل — احفظ في IndexedDB
    const clone = await request.clone();
    const body = await clone.json();
    await saveToQueue(request.method, request.url, body, body.idempotency_key);
    return new Response(JSON.stringify({
      success: true,
      data: { queued: true, idempotency_key: body.idempotency_key }
    }), { status: 202 });
  }
}
```

## واجهة المستخدم — OfflineIndicator
مكون React يعرض:
- أيقونة رصيد عندما يكون الاتصال متاحاً
- أيقونة معطل مع عداد `pending` عندما لا يتوفر اتصال
- نقرة ← يفتح نافذة Queue (المعلقة، المكتملة، الفاشلة)
- زر "إعادة المحاولة" للطلبات الفاشلة
