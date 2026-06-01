"use client";

import { useEffect, useState, useCallback } from "react";
import { useRouter } from "next/navigation";
import { apiGet } from "@/lib/api/client";
import { ENDPOINTS } from "@/lib/api/endpoints";
import Card from "@/components/ui/Card";
import Button from "@/components/ui/Button";
import type { Agent } from "@/lib/api/agent-types";

const statusBadge = (status: Agent["status"]) => {
  const map: Record<Agent["status"], { label: string; style: string }> = {
    pending: {
      label: "قيد الانتظار",
      style: "bg-yellow-100 text-yellow-700 dark:bg-yellow-900/30 dark:text-yellow-300",
    },
    active: {
      label: "نشط",
      style: "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300",
    },
    suspended: {
      label: "موقوف",
      style: "bg-red-100 text-red-700 dark:bg-red-900/30 dark:text-red-300",
    },
    closed: {
      label: "مغلق",
      style: "bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400",
    },
  };
  const m = map[status];
  return (
    <span className={`rounded-full px-2.5 py-0.5 text-xs font-medium ${m.style}`}>
      {m.label}
    </span>
  );
};

export default function AgentListPage() {
  const router = useRouter();
  const [agents, setAgents] = useState<Agent[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const fetchAgents = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const res = await apiGet<Agent[]>(ENDPOINTS.ADMIN_AGENTS);
      if (res.success) {
        setAgents(res.data);
      } else {
        setError(res.error?.message || "فشل تحميل البيانات");
      }
    } catch {
      setError("تعذر الاتصال بالخادم");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchAgents();
  }, [fetchAgents]);

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold">الوكلاء</h1>
        <Button onClick={fetchAgents} variant="secondary" loading={loading}>
          تحديث
        </Button>
      </div>

      {error && (
        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
          {error}
        </div>
      )}

      {loading ? (
        <div className="flex items-center justify-center py-20">
          <div className="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-blue-600" />
        </div>
      ) : agents.length === 0 ? (
        <Card className="py-12 text-center">
          <p className="text-gray-400">لا يوجد وكلاء حتى الآن</p>
        </Card>
      ) : (
        <Card>
          <div className="overflow-x-auto">
            <table className="w-full text-sm text-right">
              <thead>
                <tr className="border-b border-gray-200 dark:border-gray-700">
                  <th className="px-4 py-3 font-semibold text-gray-500">ID</th>
                  <th className="px-4 py-3 font-semibold text-gray-500">اسم النشاط التجاري</th>
                  <th className="px-4 py-3 font-semibold text-gray-500">رقم الترخيص</th>
                  <th className="px-4 py-3 font-semibold text-gray-500">المنطقة</th>
                  <th className="px-4 py-3 font-semibold text-gray-500">الحالة</th>
                  <th className="px-4 py-3 font-semibold text-gray-500">نسبة العمولة</th>
                  <th className="px-4 py-3 font-semibold text-gray-500">الرصيد</th>
                  <th className="px-4 py-3 font-semibold text-gray-500">الإجراءات</th>
                </tr>
              </thead>
              <tbody>
                {agents.map((agent) => (
                  <tr
                    key={agent.id}
                    onClick={() => router.push(`/agents/${agent.id}`)}
                    className="cursor-pointer border-b border-gray-100 transition-colors hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-700/50"
                  >
                    <td className="px-4 py-3 font-mono text-xs text-gray-500">
                      {agent.id}
                    </td>
                    <td className="px-4 py-3 font-medium">
                      {agent.business_name}
                      {agent.user && (
                        <p className="text-xs text-gray-400">{agent.user.name}</p>
                      )}
                    </td>
                    <td className="px-4 py-3 font-mono text-xs text-gray-500">
                      {agent.license_number}
                    </td>
                    <td className="px-4 py-3 text-gray-600">
                      {agent.region || "—"}
                    </td>
                    <td className="px-4 py-3">{statusBadge(agent.status)}</td>
                    <td className="px-4 py-3">{agent.commission_rate}%</td>
                    <td className="px-4 py-3 font-medium" dir="ltr">
                      {agent.balance.toLocaleString("ar-SA")}
                    </td>
                    <td className="px-4 py-3">
                      <Button
                        variant="secondary"
                        className="text-xs px-3 py-1"
                        onClick={(e) => {
                          e.stopPropagation();
                          router.push(`/agents/${agent.id}`);
                        }}
                      >
                        تفاصيل
                      </Button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </Card>
      )}
    </div>
  );
}
