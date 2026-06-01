"use client";

import { useEffect, useState, useCallback } from "react";
import { apiGet, apiPut } from "@/lib/api/client";
import { ENDPOINTS } from "@/lib/api/endpoints";
import Card from "@/components/ui/Card";
import Button from "@/components/ui/Button";
import type { FraudAlert, Agent } from "@/lib/api/agent-types";

const severityConfig: Record<
  FraudAlert["severity"],
  { label: string; border: string; bg: string; badge: string; icon: string }
> = {
  critical: {
    label: "حرج",
    border: "border-red-500",
    bg: "bg-red-50 dark:bg-red-950/30",
    badge: "bg-red-600 text-white",
    icon: "🔴",
  },
  high: {
    label: "عالٍ",
    border: "border-orange-500",
    bg: "bg-orange-50 dark:bg-orange-950/30",
    badge: "bg-orange-500 text-white",
    icon: "🟠",
  },
  medium: {
    label: "متوسط",
    border: "border-yellow-500",
    bg: "bg-yellow-50 dark:bg-yellow-950/30",
    badge: "bg-yellow-500 text-white",
    icon: "🟡",
  },
  low: {
    label: "منخفض",
    border: "border-blue-500",
    bg: "bg-blue-50 dark:bg-blue-950/30",
    badge: "bg-blue-500 text-white",
    icon: "🔵",
  },
};

const statusBadge = (status: FraudAlert["status"]) => {
  const map: Record<FraudAlert["status"], { label: string; style: string }> = {
    open: {
      label: "مفتوح",
      style: "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300",
    },
    investigating: {
      label: "قيد التحقيق",
      style: "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300",
    },
    resolved: {
      label: "تم الحل",
      style: "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300",
    },
  };
  const m = map[status];
  return (
    <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${m.style}`}>
      {m.label}
    </span>
  );
};

export default function FraudAlertDashboard() {
  const [alerts, setAlerts] = useState<FraudAlert[]>([]);
  const [agents, setAgents] = useState<Agent[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [actionLoading, setActionLoading] = useState<number | null>(null);

  const fetchData = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const [alertsRes, agentsRes] = await Promise.all([
        apiGet<FraudAlert[]>(ENDPOINTS.ADMIN_AGENT_FRAUD_ALERTS),
        apiGet<Agent[]>(ENDPOINTS.ADMIN_AGENTS),
      ]);
      if (alertsRes.success) setAlerts(alertsRes.data);
      else setError(alertsRes.error?.message || "فشل تحميل التنبيهات");
      if (agentsRes.success) setAgents(agentsRes.data);
    } catch {
      setError("تعذر الاتصال بالخادم");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchData();
  }, [fetchData]);

  const getAgentName = (agentId: number): string => {
    const agent = agents.find((a) => a.id === agentId);
    return agent?.business_name || `#${agentId}`;
  };

  const updateAlertStatus = async (alertId: number, status: FraudAlert["status"]) => {
    setActionLoading(alertId);
    try {
      const res = await apiPut(`${ENDPOINTS.ADMIN_AGENT_FRAUD_ALERTS}/${alertId}`, {
        status,
      });
      if (res.success) {
        setAlerts((prev) =>
          prev.map((a) => (a.id === alertId ? { ...a, status } : a))
        );
      } else {
        setError(res.error?.message || "فشل تحديث التنبيه");
      }
    } catch {
      setError("حدث خطأ أثناء تحديث التنبيه");
    } finally {
      setActionLoading(null);
    }
  };

  const severityCounts = {
    critical: alerts.filter((a) => a.severity === "critical" && a.status !== "resolved").length,
    high: alerts.filter((a) => a.severity === "high" && a.status !== "resolved").length,
    medium: alerts.filter((a) => a.severity === "medium" && a.status !== "resolved").length,
    low: alerts.filter((a) => a.severity === "low" && a.status !== "resolved").length,
  };

  const openCount = alerts.filter((a) => a.status !== "resolved").length;

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold">لوحة تنبيهات الاحتيال</h1>
        <Button onClick={fetchData} variant="secondary" loading={loading}>
          تحديث
        </Button>
      </div>

      {error && (
        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
          {error}
        </div>
      )}

      {/* Severity Summary Bar */}
      <div className="mb-6 grid grid-cols-4 gap-3">
        <div className="rounded-xl border border-red-200 bg-red-50 p-4 text-center dark:border-red-800 dark:bg-red-950/30">
          <p className="text-2xl font-bold text-red-600">
            {severityCounts.critical}
          </p>
          <p className="text-xs text-red-500">حرج</p>
        </div>
        <div className="rounded-xl border border-orange-200 bg-orange-50 p-4 text-center dark:border-orange-800 dark:bg-orange-950/30">
          <p className="text-2xl font-bold text-orange-600">
            {severityCounts.high}
          </p>
          <p className="text-xs text-orange-500">عالٍ</p>
        </div>
        <div className="rounded-xl border border-yellow-200 bg-yellow-50 p-4 text-center dark:border-yellow-800 dark:bg-yellow-950/30">
          <p className="text-2xl font-bold text-yellow-600">
            {severityCounts.medium}
          </p>
          <p className="text-xs text-yellow-500">متوسط</p>
        </div>
        <div className="rounded-xl border border-blue-200 bg-blue-50 p-4 text-center dark:border-blue-800 dark:bg-blue-950/30">
          <p className="text-2xl font-bold text-blue-600">
            {severityCounts.low}
          </p>
          <p className="text-xs text-blue-500">منخفض</p>
        </div>
      </div>

      {loading ? (
        <div className="flex items-center justify-center py-20">
          <div className="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-blue-600" />
        </div>
      ) : alerts.length === 0 ? (
        <Card className="py-12 text-center">
          <p className="text-4xl">✅</p>
          <p className="mt-2 text-gray-400">لا توجد تنبيهات احتيال</p>
        </Card>
      ) : (
        <div className="space-y-4">
          {alerts.map((alert) => {
            const severity = severityConfig[alert.severity];
            const isResolved = alert.status === "resolved";
            return (
              <div
                key={alert.id}
                className={`rounded-xl border-r-4 p-5 transition-colors ${
                  severity.border
                } ${severity.bg} ${
                  isResolved ? "opacity-60" : ""
                }`}
              >
                <div className="flex items-start justify-between gap-4">
                  <div className="flex-1">
                    <div className="flex items-center gap-2">
                      <span className="text-xl">{severity.icon}</span>
                      <span
                        className={`rounded-full px-2.5 py-0.5 text-xs font-bold ${severity.badge}`}
                      >
                        {severity.label}
                      </span>
                      <span className="text-xs text-gray-400">
                        {alert.type}
                      </span>
                      <span>{statusBadge(alert.status)}</span>
                    </div>

                    <p className="mt-2 text-sm font-medium">
                      {alert.description}
                    </p>

                    <div className="mt-2 flex items-center gap-4 text-xs text-gray-500">
                      <span>
                        الوكيل:{" "}
                        <span className="font-medium text-gray-700 dark:text-gray-300">
                          {getAgentName(alert.agent_id)}
                        </span>
                      </span>
                      <span>
                        {new Date(alert.detected_at).toLocaleDateString("ar-SA", {
                          year: "numeric",
                          month: "short",
                          day: "numeric",
                          hour: "2-digit",
                          minute: "2-digit",
                        })}
                      </span>
                    </div>
                  </div>

                  <div className="flex shrink-0 gap-2">
                    {alert.status === "open" && (
                      <Button
                        variant="secondary"
                        className="text-xs px-3 py-1"
                        loading={actionLoading === alert.id}
                        onClick={() => updateAlertStatus(alert.id, "investigating")}
                      >
                        تحقق
                      </Button>
                    )}
                    {alert.status !== "resolved" && (
                      <Button
                        variant="primary"
                        className="text-xs px-3 py-1"
                        loading={actionLoading === alert.id}
                        onClick={() => updateAlertStatus(alert.id, "resolved")}
                      >
                        حل
                      </Button>
                    )}
                    {alert.status === "open" && (
                      <Button
                        variant="danger"
                        className="text-xs px-3 py-1"
                        loading={actionLoading === alert.id}
                        onClick={() => updateAlertStatus(alert.id, "resolved")}
                      >
                        تجاهل
                      </Button>
                    )}
                  </div>
                </div>
              </div>
            );
          })}

          {openCount > 0 && (
            <div className="mt-4 text-center">
              <p className="text-sm text-gray-500">
                {openCount} تنبيه{openCount !== 1 ? "ات" : ""} مفتوحة
              </p>
            </div>
          )}
        </div>
      )}
    </div>
  );
}
