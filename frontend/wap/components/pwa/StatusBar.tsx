"use client";

import { useEffect, useState } from "react";
import { offlineQueue } from "@/lib/db/offline-queue";

type Status = "online" | "offline" | "syncing" | "error";

export default function StatusBar() {
  const [status, setStatus] = useState<Status>("online");
  const [pendingCount, setPendingCount] = useState(0);
  const [failedCount, setFailedCount] = useState(0);

  useEffect(() => {
    const updateOnline = () => setStatus(navigator.onLine ? "online" : "offline");
    updateOnline();
    window.addEventListener("online", updateOnline);
    window.addEventListener("offline", updateOnline);

    const interval = setInterval(async () => {
      const c = await offlineQueue.count();
      setPendingCount(c.pending);
      setFailedCount(c.failed);
    }, 3000);

    return () => {
      window.removeEventListener("online", updateOnline);
      window.removeEventListener("offline", updateOnline);
      clearInterval(interval);
    };
  }, []);

  const handleSync = async () => {
    setStatus("syncing");
    try {
      await offlineQueue.processQueue(async () => true);
      setStatus(navigator.onLine ? "online" : "offline");
    } catch {
      setStatus("error");
    }
  };

  const colors: Record<Status, string> = {
    online: "bg-green-500",
    offline: "bg-yellow-500",
    syncing: "bg-blue-500",
    error: "bg-red-500",
  };

  const labels: Record<Status, string> = {
    online: "متصل",
    offline: "غير متصل",
    syncing: "جاري المزامنة...",
    error: "فشل المزامنة",
  };

  if (status === "online" && pendingCount === 0 && failedCount === 0) {
    return null;
  }

  return (
    <div
      className={`${colors[status]} flex items-center justify-between px-4 py-1 text-sm text-white`}
    >
      <span>{labels[status]}</span>
      {(pendingCount > 0 || failedCount > 0) && (
        <button onClick={handleSync} className="underline">
          {pendingCount} معلق، {failedCount} فاشل — مزامنة يدوية
        </button>
      )}
    </div>
  );
}
