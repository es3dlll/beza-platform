"use client";

import { useEffect, useState, useCallback } from "react";
import { apiGet, apiPut } from "@/lib/api/client";
import { ENDPOINTS } from "@/lib/api/endpoints";
import Card from "@/components/ui/Card";
import Button from "@/components/ui/Button";

interface WapDevice {
  fingerprint: string;
  user_agent: string;
  request_count: number;
  last_seen: string;
}

interface QueueCounts {
  pending: number;
  completed: number;
  failed: number;
}

interface QueueItem {
  id: number;
  user: string;
  amount: number;
  currency: string;
  status: string;
  created_at: string;
}

interface WapRoute {
  id: number;
  method: string;
  pattern: string;
  target: string;
  roles: string[] | null;
  priority: number;
  is_active: boolean;
}

export default function WapManagementPanel() {
  const [devices, setDevices] = useState<WapDevice[]>([]);
  const [queue, setQueue] = useState<{ counts: QueueCounts; recent: QueueItem[] } | null>(null);
  const [routes, setRoutes] = useState<WapRoute[]>([]);
  const [error, setError] = useState("");

  const fetchAll = useCallback(async () => {
    try {
      const [dRes, qRes, rRes] = await Promise.all([
        apiGet<WapDevice[]>(ENDPOINTS.ADMIN_WAP_DEVICES),
        apiGet<{ counts: QueueCounts; recent: QueueItem[] }>(ENDPOINTS.ADMIN_WAP_QUEUE),
        apiGet<WapRoute[]>(ENDPOINTS.ADMIN_WAP_ROUTES),
      ]);
      if (dRes.success) setDevices(dRes.data);
      if (qRes.success) setQueue(qRes.data);
      if (rRes.success) setRoutes(rRes.data);
    } catch {
      setError("فشل تحميل البيانات");
    }
  }, []);

  useEffect(() => { fetchAll(); }, [fetchAll]);

  const toggleRoute = async (route: WapRoute) => {
    const res = await apiPut(ENDPOINTS.ADMIN_WAP_ROUTE_UPDATE(route.id), {
      is_active: !route.is_active,
    });
    if (res.success) {
      setRoutes((prev) =>
        prev.map((r) => (r.id === route.id ? { ...r, is_active: !r.is_active } : r))
      );
    }
  };

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold">إدارة WAP</h1>
        <Button onClick={fetchAll} variant="secondary">تحديث</Button>
      </div>

      {error && (
        <p className="mb-4 text-sm text-red-600">{error}</p>
      )}

      {/* Queue Status */}
      <Card title="حالة طابور المعالجة" className="mb-6">
        {queue ? (
          <div className="grid grid-cols-3 gap-4">
            <div className="text-center">
              <p className="text-2xl font-bold text-yellow-600">{queue.counts.pending}</p>
              <p className="text-xs text-gray-500">معلقة</p>
            </div>
            <div className="text-center">
              <p className="text-2xl font-bold text-green-600">{queue.counts.completed}</p>
              <p className="text-xs text-gray-500">مكتملة</p>
            </div>
            <div className="text-center">
              <p className="text-2xl font-bold text-red-600">{queue.counts.failed}</p>
              <p className="text-xs text-gray-500">فاشلة</p>
            </div>
          </div>
        ) : (
          <p className="text-sm text-gray-400">جاري التحميل...</p>
        )}

        {queue && queue.recent.length > 0 && (
          <div className="mt-4">
            <h4 className="mb-2 text-xs font-semibold text-gray-500 uppercase">آخر المعاملات</h4>
            <div className="space-y-1">
              {queue.recent.slice(0, 5).map((item) => (
                <div key={item.id} className="flex justify-between text-xs">
                  <span>{item.user}</span>
                  <span>{item.amount / 100} {item.currency}</span>
                  <span className={
                    item.status === "completed" ? "text-green-600" :
                    item.status === "failed" ? "text-red-600" : "text-yellow-600"
                  }>{item.status}</span>
                </div>
              ))}
            </div>
          </div>
        )}
      </Card>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {/* Registered Devices */}
        <Card title="الأجهزة المسجلة">
          {devices.length === 0 ? (
            <p className="text-sm text-gray-400">لا توجد أجهزة بعد</p>
          ) : (
            <div className="space-y-2 max-h-80 overflow-y-auto">
              {devices.map((d, i) => (
                <div key={i} className="border-b border-gray-100 pb-2 last:border-0 dark:border-gray-700">
                  <p className="truncate text-xs font-mono text-gray-500" dir="ltr">
                    {d.fingerprint}
                  </p>
                  <p className="truncate text-xs text-gray-400">{d.user_agent}</p>
                  <div className="flex justify-between text-xs text-gray-500">
                    <span>{d.request_count} طلب</span>
                    <span>{new Date(d.last_seen).toLocaleDateString("ar-SA")}</span>
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>

        {/* Routing Rules */}
        <Card title="قواعد التوجيه / الصلاحيات">
          {routes.length === 0 ? (
            <p className="text-sm text-gray-400">لا توجد قواعد بعد</p>
          ) : (
            <div className="space-y-2">
              {routes.map((route) => (
                <div key={route.id} className="flex items-center justify-between rounded-lg border border-gray-100 p-2 dark:border-gray-700">
                  <div className="flex-1 min-w-0">
                    <div className="flex items-center gap-2">
                      <span className="rounded bg-gray-100 px-1.5 py-0.5 text-xs font-mono dark:bg-gray-700">
                        {route.method}
                      </span>
                      <span className="truncate text-xs font-mono" dir="ltr">
                        {route.pattern}
                      </span>
                    </div>
                    <p className="mt-0.5 text-xs text-gray-500">
                      ← {route.target}
                      {route.roles && !route.roles.includes("*") && (
                        <span className="mr-2 text-gray-400">| {route.roles.join(", ")}</span>
                      )}
                    </p>
                  </div>
                  <button
                    onClick={() => toggleRoute(route)}
                    className={`mr-2 rounded-full px-2 py-0.5 text-xs font-medium ${
                      route.is_active
                        ? "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300"
                        : "bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400"
                    }`}
                  >
                    {route.is_active ? "فعال" : "معطل"}
                  </button>
                </div>
              ))}
            </div>
          )}
        </Card>
      </div>
    </div>
  );
}
