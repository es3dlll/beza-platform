"use client";

import { useEffect, useState } from "react";
import { apiGet } from "@/lib/api/client";
import { ENDPOINTS } from "@/lib/api/endpoints";
import Card from "@/components/ui/Card";

interface WapSummary {
  total_users: number;
  wap_users: number;
  total_transactions: number;
  pending_transactions: number;
  total_devices: number;
}

export default function DashboardHome() {
  const [summary, setSummary] = useState<WapSummary | null>(null);

  useEffect(() => {
    apiGet<WapSummary>(ENDPOINTS.ADMIN_WAP_SUMMARY)
      .then((res) => {
        if (res.success) setSummary(res.data);
      })
      .catch(() => {});
  }, []);

  const stats = [
    { label: "إجمالي المستخدمين", value: summary?.total_users ?? "—", color: "text-blue-600" },
    { label: "مستخدمي WAP", value: summary?.wap_users ?? "—", color: "text-green-600" },
    { label: "إجمالي المعاملات", value: summary?.total_transactions ?? "—", color: "text-purple-600" },
    { label: "معاملات معلقة", value: summary?.pending_transactions ?? "—", color: "text-yellow-600" },
    { label: "الأجهزة المسجلة", value: summary?.total_devices ?? "—", color: "text-indigo-600" },
  ];

  return (
    <div>
      <h1 className="mb-6 text-2xl font-bold">لوحة التحكم</h1>
      <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
        {stats.map((s) => (
          <Card key={s.label}>
            <p className="text-sm text-gray-500 dark:text-gray-400">{s.label}</p>
            <p className={`mt-1 text-3xl font-bold ${s.color}`}>{s.value}</p>
          </Card>
        ))}
      </div>
    </div>
  );
}
