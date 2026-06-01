export interface QueueItem {
  id?: number;
  method: "POST" | "PUT" | "PATCH" | "DELETE";
  endpoint: string;
  body: Record<string, unknown>;
  idempotencyKey: string;
  status: "pending" | "processing" | "completed" | "failed";
  createdAt: number;
  retryCount: number;
  maxRetries: number;
  lastError: string | null;
  lastAttempt: number | null;
}

const DB_NAME = "beza_wap_offline";
const STORE_NAME = "queue";
const DB_VERSION = 1;

function openDB(): Promise<IDBDatabase> {
  return new Promise((resolve, reject) => {
    const req = indexedDB.open(DB_NAME, DB_VERSION);
    req.onupgradeneeded = () => {
      const db = req.result;
      if (!db.objectStoreNames.contains(STORE_NAME)) {
        const store = db.createObjectStore(STORE_NAME, {
          keyPath: "id",
          autoIncrement: true,
        });
        store.createIndex("status", "status", { unique: false });
        store.createIndex("idempotencyKey", "idempotencyKey", {
          unique: true,
        });
      }
    };
    req.onsuccess = () => resolve(req.result);
    req.onerror = () => reject(req.error);
  });
}

export class OfflineQueueService {
  async add(
    method: QueueItem["method"],
    endpoint: string,
    body: Record<string, unknown>,
    idempotencyKey: string
  ): Promise<void> {
    const db = await openDB();
    const tx = db.transaction(STORE_NAME, "readwrite");
    tx.objectStore(STORE_NAME).add({
      method,
      endpoint,
      body,
      idempotencyKey,
      status: "pending",
      createdAt: Date.now(),
      retryCount: 0,
      maxRetries: 5,
      lastError: null,
      lastAttempt: null,
    } satisfies Omit<QueueItem, "id">);
  }

  async getAll(): Promise<QueueItem[]> {
    const db = await openDB();
    const tx = db.transaction(STORE_NAME, "readonly");
    const store = tx.objectStore(STORE_NAME);
    return new Promise((resolve, reject) => {
      const req = store.getAll();
      req.onsuccess = () => resolve(req.result);
      req.onerror = () => reject(req.error);
    });
  }

  async getPending(): Promise<QueueItem[]> {
    const all = await this.getAll();
    return all.filter((i) => i.status === "pending");
  }

  async update(item: QueueItem): Promise<void> {
    const db = await openDB();
    const tx = db.transaction(STORE_NAME, "readwrite");
    tx.objectStore(STORE_NAME).put(item);
  }

  async processQueue(
    sendFn: (item: QueueItem) => Promise<boolean>
  ): Promise<void> {
    const items = await this.getPending();
    for (const item of items) {
      item.status = "processing";
      await this.update(item);

      try {
        const ok = await sendFn(item);
        item.status = ok ? "completed" : "failed";
        if (!ok) {
          item.retryCount++;
          item.lastError = "الخادم رفض الطلب";
        }
      } catch (err) {
        item.retryCount++;
        item.lastError = String(err);
        item.status =
          item.retryCount >= item.maxRetries ? "failed" : "pending";
      }
      item.lastAttempt = Date.now();
      await this.update(item);
    }
  }

  async count(): Promise<{ pending: number; failed: number }> {
    const all = await this.getAll();
    return {
      pending: all.filter((i) => i.status === "pending").length,
      failed: all.filter((i) => i.status === "failed").length,
    };
  }

  async clearCompleted(): Promise<void> {
    const all = await this.getAll();
    const db = await openDB();
    const tx = db.transaction(STORE_NAME, "readwrite");
    const store = tx.objectStore(STORE_NAME);
    for (const item of all) {
      if (item.status === "completed") {
        store.delete(item.id!);
      }
    }
  }
}

export const offlineQueue = new OfflineQueueService();
