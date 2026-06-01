"use client";

import { useEffect, useState, useCallback } from "react";
import { apiGet } from "@/lib/api/client";
import { ENDPOINTS } from "@/lib/api/endpoints";
import Card from "@/components/ui/Card";
import Button from "@/components/ui/Button";
import type { AgentCommission } from "@/lib/api/agent-types";

const commissionStatusBadge = (status: AgentCommission["status"]) => {
  const map: Record<AgentCommission["status"], { label: string; style: string }> = {
    accrued: {
      label: "مستحقة",
      style: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300",
    },
    settled: {
      label: "مسددة",
      style: "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300",
    },
    voided: {
      label: "ملغاة",
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

export default function CommissionsPage() {
  const [commissions, setCommissions] = useState<AgentCommission[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");

  const fetchCommissions = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const res = await apiGet<AgentCommission[]>(ENDPOINTS.ADMIN_AGENT_COMMISSIONS);
      if (res.success) {
        setCommissions(res.data);
      } else {
        setError(res.error?.message || "فشل تحميل العمولات");
      }
    } catch {
      setError("تعذر الاتصال بالخادم");
    } finally {
      setLoading(false);
    }
  }, []);

  useEffect(() => {
    fetchCommissions();
  }, [fetchCommissions]);

  const totalAccrued = commissions
    .filter((c) => c.status === "accrued")
    .reduce((sum, c) => sum + c.amount, 0);

  const totalSettled = commissions
    .filter((c) => c.status === "settled")
    .reduce((sum, c) => sum + c.amount, 0);

  const totalVoided = commissions
    .filter((c) => c.status === "voided")
    .reduce((sum, c) => sum + c.amount, 0);

  // Unique currencies for display
  const currencies = [...new Set(commissions.map((c) => c.currency))];

  const summaryCards = [
    {
      label: "إجمالي العمولات المستحقة",
      value: totalAccrued.toLocaleString("ar-SA"),
      color: "text-blue-600",
      bg: "bg-blue-50 dark:bg-blue-900/20",
      border: "border-blue-200 dark:border-blue-800",
    },
    {
      label: "إجمالي العمولات المسددة",
      value: totalSettled.toLocaleString("ar-SA"),
      color: "text-green-600",
      bg: "bg-green-50 dark:bg-green-900/20",
      border: "border-green-200 dark:border-green-800",
    },
    {
      label: "العمولات الملغاة",
      value: totalVoided.toLocaleString("ar-SA"),
      color: "text-gray-600",
      bg: "bg-gray-50 dark:bg-gray-800/50",
      border: "border-gray-200 dark:border-gray-700",
    },
  ];

  return (
    <div>
      <div className="mb-6 flex items-center justify-between">
        <h1 className="text-2xl font-bold">نظرة عامة على العمولات</h1>
        <Button onClick={fetchCommissions} variant="secondary" loading={loading}>
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
      ) : (
        <>
          {/* Summary Cards */}
          <div className="mb-6 grid grid-cols-1 gap-4 sm:grid-cols-3">
            {summaryCards.map((card) => (
              <div
                key={card.label}
                className={`rounded-xl border p-5 ${card.bg} ${card.border}`}
              >
                <p className="text-sm text-gray-500 dark:text-gray-400">
                  {card.label}
                </p>
                <p className={`mt-1 text-2xl font-bold ${card.color}`} dir="ltr">
                  {card.value}
                  {currencies.length === 1 && (
                    <span className="mr-1 text-lg font-normal text-gray-400">
                      {currencies[0]}
                    </span>
                  )}
                </p>
              </div>
            ))}
          </div>

          {/* Commissions Table */}
          <Card title="جميع العمولات">
            {commissions.length === 0 ? (
              <p className="py-8 text-center text-sm text-gray-400">
                لا توجد عمولات مسجلة
              </p>
            ) : (
              <div className="overflow-x-auto">
                <table className="w-full text-sm text-right">
                  <thead>
                    <tr className="border-b border-gray-200 dark:border-gray-700">
                      <th className="px-4 py-3 font-semibold text-gray-500">ID</th>
                      <th className="px-4 py-3 font-semibold text-gray-500">الوكيل</th>
                      <th className="px-4 py-3 font-semibold text-gray-500">المبلغ</th>
                      <th className="px-4 py-3 font-semibold text-gray-500">العملة</th>
                      <th className="px-4 py-3 font-semibold text-gray-500">النوع</th>
                      <th className="px-4 py-3 font-semibold text-gray-500">الحالة</th>
                      <th className="px-4 py-3 font-semibold text-gray-500">التاريخ</th>
                    </tr>
                  </thead>
                  <tbody>
                    {commissions.map((c) => (
                      <tr
                        key={c.id}
                        className="border-b border-gray-100 transition-colors hover:bg-gray-50 dark:border-gray-800 dark:hover:bg-gray-700/50"
                      >
                        <td className="px-4 py-3 font-mono text-xs text-gray-500">
                          {c.id}
                        </td>
                        <td className="px-4 py-3 font-mono text-xs text-gray-500">
                          #{c.agent_id}
                        </td>
                        <td className="px-4 py-3 font-medium" dir="ltr">
                          {c.amount.toLocaleString("ar-SA")}
                        </td>
                        <td className="px-4 py-3">{c.currency}</td>
                        <td className="px-4 py-3 text-gray-600">{c.type}</td>
                        <td className="px-4 py-3">
                          {commissionStatusBadge(c.status)}
                        </td>
                        <td className="px-4 py-3 text-xs text-gray-500">
                          {new Date(c.created_at).toLocaleDateString("ar-SA")}
                        </td>
                      </tr>
                    ))}
                  </tbody>
                </table>
              </div>
            )}
          </Card>
        </>
      )}
    </div>
  );
}
