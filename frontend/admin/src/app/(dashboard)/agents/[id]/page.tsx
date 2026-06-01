"use client";

import { useEffect, useState, useCallback } from "react";
import { useParams, useRouter } from "next/navigation";
import { apiGet, apiPost, apiPut } from "@/lib/api/client";
import { ENDPOINTS } from "@/lib/api/endpoints";
import Card from "@/components/ui/Card";
import Button from "@/components/ui/Button";
import type { Agent, AgentCommission, AgentSettlement } from "@/lib/api/agent-types";

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

const commissionStatusBadge = (status: AgentCommission["status"]) => {
  const map: Record<AgentCommission["status"], { label: string; style: string }> = {
    accrued: { label: "مستحقة", style: "bg-blue-100 text-blue-700 dark:bg-blue-900/30 dark:text-blue-300" },
    settled: { label: "مسددة", style: "bg-green-100 text-green-700 dark:bg-green-900/30 dark:text-green-300" },
    voided: { label: "ملغاة", style: "bg-gray-100 text-gray-500 dark:bg-gray-700 dark:text-gray-400" },
  };
  const m = map[status];
  return (
    <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${m.style}`}>
      {m.label}
    </span>
  );
};

interface ConfirmDialogProps {
  open: boolean;
  title: string;
  message: string;
  confirmLabel: string;
  variant?: "primary" | "danger";
  loading?: boolean;
  onConfirm: () => void;
  onCancel: () => void;
}

function ConfirmDialog({
  open,
  title,
  message,
  confirmLabel,
  variant = "primary",
  loading = false,
  onConfirm,
  onCancel,
}: ConfirmDialogProps) {
  if (!open) return null;
  return (
    <div className="fixed inset-0 z-50 flex items-center justify-center bg-black/40">
      <div className="w-full max-w-md rounded-xl bg-white p-6 shadow-lg dark:bg-gray-800">
        <h3 className="text-lg font-bold">{title}</h3>
        <p className="mt-2 text-sm text-gray-600 dark:text-gray-400">{message}</p>
        <div className="mt-6 flex justify-end gap-3">
          <Button variant="secondary" onClick={onCancel} disabled={loading}>
            إلغاء
          </Button>
          <Button variant={variant} onClick={onConfirm} loading={loading}>
            {confirmLabel}
          </Button>
        </div>
      </div>
    </div>
  );
}

export default function AgentDetailPage() {
  const params = useParams();
  const router = useRouter();
  const agentId = Number(params.id);

  const [agent, setAgent] = useState<Agent | null>(null);
  const [commissions, setCommissions] = useState<AgentCommission[]>([]);
  const [settlements, setSettlements] = useState<AgentSettlement[]>([]);
  const [loading, setLoading] = useState(true);
  const [error, setError] = useState("");
  const [actionLoading, setActionLoading] = useState(false);

  // Confirm dialog state
  const [confirm, setConfirm] = useState<{
    open: boolean;
    title: string;
    message: string;
    confirmLabel: string;
    variant: "primary" | "danger";
    action: () => Promise<void>;
  }>({ open: false, title: "", message: "", confirmLabel: "", variant: "primary", action: async () => {} });

  const fetchAgent = useCallback(async () => {
    setLoading(true);
    setError("");
    try {
      const [aRes, cRes, sRes] = await Promise.all([
        apiGet<Agent>(ENDPOINTS.ADMIN_AGENT_DETAIL(agentId)),
        apiGet<AgentCommission[]>(ENDPOINTS.ADMIN_AGENT_COMMISSIONS),
        apiGet<AgentSettlement[]>(ENDPOINTS.ADMIN_AGENT_SETTLEMENTS),
      ]);
      if (aRes.success) setAgent(aRes.data);
      else setError(aRes.error?.message || "فشل تحميل بيانات الوكيل");
      if (cRes.success) {
        const filtered = cRes.data.filter((c) => c.agent_id === agentId);
        setCommissions(filtered);
      }
      if (sRes.success) {
        const filtered = sRes.data.filter((s) => s.agent_id === agentId);
        setSettlements(filtered);
      }
    } catch {
      setError("تعذر الاتصال بالخادم");
    } finally {
      setLoading(false);
    }
  }, [agentId]);

  useEffect(() => {
    fetchAgent();
  }, [fetchAgent]);

  const handleApprove = async () => {
    setActionLoading(true);
    try {
      const res = await apiPost<Agent>(ENDPOINTS.ADMIN_AGENT_APPROVE(agentId));
      if (res.success && res.data) {
        setAgent(res.data);
      } else {
        setError(res.error?.message || "فشل الموافقة على الوكيل");
      }
    } catch {
      setError("حدث خطأ أثناء الموافقة");
    } finally {
      setActionLoading(false);
      setConfirm({ ...confirm, open: false });
    }
  };

  const handleSuspend = async () => {
    setActionLoading(true);
    try {
      const res = await apiPut<Agent>(ENDPOINTS.ADMIN_AGENT_SUSPEND(agentId), { status: "suspended" });
      if (res.success && res.data) {
        setAgent(res.data);
      } else {
        setError(res.error?.message || "فشل إيقاف الوكيل");
      }
    } catch {
      setError("حدث خطأ أثناء الإيقاف");
    } finally {
      setActionLoading(false);
      setConfirm({ ...confirm, open: false });
    }
  };

  const handleReactivate = async () => {
    setActionLoading(true);
    try {
      const res = await apiPut<Agent>(ENDPOINTS.ADMIN_AGENT_SUSPEND(agentId), { status: "active" });
      if (res.success && res.data) {
        setAgent(res.data);
      } else {
        setError(res.error?.message || "فشل إعادة تفعيل الوكيل");
      }
    } catch {
      setError("حدث خطأ أثناء إعادة التفعيل");
    } finally {
      setActionLoading(false);
      setConfirm({ ...confirm, open: false });
    }
  };

  // Loading state
  if (loading) {
    return (
      <div className="flex items-center justify-center py-32">
        <div className="h-8 w-8 animate-spin rounded-full border-4 border-gray-200 border-t-blue-600" />
      </div>
    );
  }

  // Error state
  if (error && !agent) {
    return (
      <div>
        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
          {error}
        </div>
        <Button variant="secondary" onClick={() => router.push("/agents")}>
          العودة إلى القائمة
        </Button>
      </div>
    );
  }

  if (!agent) {
    return (
      <div className="py-12 text-center">
        <p className="text-gray-400">الوكيل غير موجود</p>
        <Button variant="secondary" onClick={() => router.push("/agents")} className="mt-4">
          العودة إلى القائمة
        </Button>
      </div>
    );
  }

  const actionButtons = () => {
    switch (agent.status) {
      case "pending":
        return (
          <Button
            variant="primary"
            loading={actionLoading}
            onClick={() =>
              setConfirm({
                open: true,
                title: "موافقة على الوكيل",
                message: `هل أنت متأكد من الموافقة على الوكيل "${agent.business_name}"؟`,
                confirmLabel: "موافقة",
                variant: "primary",
                action: handleApprove,
              })
            }
          >
            موافقة
          </Button>
        );
      case "active":
        return (
          <Button
            variant="danger"
            loading={actionLoading}
            onClick={() =>
              setConfirm({
                open: true,
                title: "إيقاف الوكيل",
                message: `هل أنت متأكد من إيقاف الوكيل "${agent.business_name}"؟`,
                confirmLabel: "إيقاف",
                variant: "danger",
                action: handleSuspend,
              })
            }
          >
            إيقاف
          </Button>
        );
      case "suspended":
        return (
          <Button
            variant="primary"
            loading={actionLoading}
            onClick={() =>
              setConfirm({
                open: true,
                title: "إعادة تفعيل الوكيل",
                message: `هل أنت متأكد من إعادة تفعيل الوكيل "${agent.business_name}"؟`,
                confirmLabel: "تفعيل",
                variant: "primary",
                action: handleReactivate,
              })
            }
          >
            إعادة تفعيل
          </Button>
        );
      default:
        return null;
    }
  };

  return (
    <div>
      {/* Confirm Dialog */}
      <ConfirmDialog
        open={confirm.open}
        title={confirm.title}
        message={confirm.message}
        confirmLabel={confirm.confirmLabel}
        variant={confirm.variant}
        loading={actionLoading}
        onConfirm={confirm.action}
        onCancel={() => setConfirm({ ...confirm, open: false })}
      />

      {/* Header */}
      <div className="mb-6 flex items-center justify-between">
        <div className="flex items-center gap-3">
          <Button
            variant="secondary"
            className="text-xs px-3 py-1"
            onClick={() => router.push("/agents")}
          >
            ← العودة
          </Button>
          <h1 className="text-2xl font-bold">{agent.business_name}</h1>
          {statusBadge(agent.status)}
        </div>
        <div className="flex gap-2">{actionButtons()}</div>
      </div>

      {error && (
        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 p-4 text-sm text-red-700 dark:border-red-800 dark:bg-red-900/20 dark:text-red-400">
          {error}
        </div>
      )}

      {/* Agent Profile Card */}
      <Card title="ملف الوكيل" className="mb-6">
        <div className="grid grid-cols-1 gap-6 sm:grid-cols-2 lg:grid-cols-3">
          <div>
            <p className="text-xs text-gray-500">ID</p>
            <p className="font-mono text-sm">{agent.id}</p>
          </div>
          <div>
            <p className="text-xs text-gray-500">رقم الترخيص</p>
            <p className="font-mono text-sm">{agent.license_number}</p>
          </div>
          <div>
            <p className="text-xs text-gray-500">تاريخ انتهاء الترخيص</p>
            <p className="text-sm">
              {new Date(agent.license_expiry).toLocaleDateString("ar-SA")}
            </p>
          </div>
          <div>
            <p className="text-xs text-gray-500">المنطقة</p>
            <p className="text-sm">{agent.region || "—"}</p>
          </div>
          <div>
            <p className="text-xs text-gray-500">نسبة العمولة</p>
            <p className="text-sm font-semibold">{agent.commission_rate}%</p>
          </div>
          <div>
            <p className="text-xs text-gray-500">الرصيد</p>
            <p className="text-sm font-semibold" dir="ltr">
              {agent.balance.toLocaleString("ar-SA")}
            </p>
          </div>
          <div>
            <p className="text-xs text-gray-500">حد الإيداع اليومي</p>
            <p className="text-sm" dir="ltr">
              {agent.daily_deposit_limit.toLocaleString("ar-SA")}
            </p>
          </div>
          <div>
            <p className="text-xs text-gray-500">حد السحب اليومي</p>
            <p className="text-sm" dir="ltr">
              {agent.daily_withdrawal_limit.toLocaleString("ar-SA")}
            </p>
          </div>
          <div>
            <p className="text-xs text-gray-500">تاريخ التسجيل</p>
            <p className="text-sm">
              {new Date(agent.created_at).toLocaleDateString("ar-SA")}
            </p>
          </div>
          {agent.user && (
            <>
              <div>
                <p className="text-xs text-gray-500">اسم المستخدم</p>
                <p className="text-sm">{agent.user.name}</p>
              </div>
              <div>
                <p className="text-xs text-gray-500">البريد الإلكتروني</p>
                <p className="text-sm">{agent.user.email}</p>
              </div>
              <div>
                <p className="text-xs text-gray-500">رقم الهاتف</p>
                <p className="text-sm">{agent.user.phone}</p>
              </div>
            </>
          )}
        </div>
      </Card>

      <div className="grid grid-cols-1 gap-6 lg:grid-cols-2">
        {/* Commission History */}
        <Card title="سجل العمولات">
          {commissions.length === 0 ? (
            <p className="text-sm text-gray-400">لا توجد عمولات مسجلة</p>
          ) : (
            <div className="space-y-2 max-h-72 overflow-y-auto">
              {commissions.map((c) => (
                <div
                  key={c.id}
                  className="flex items-center justify-between rounded-lg border border-gray-100 p-3 dark:border-gray-700"
                >
                  <div>
                    <p className="text-sm font-medium">
                      {c.amount.toLocaleString("ar-SA")} {c.currency}
                    </p>
                    <p className="text-xs text-gray-400">{c.type}</p>
                  </div>
                  <div className="text-left">
                    <p>{commissionStatusBadge(c.status)}</p>
                    <p className="mt-1 text-xs text-gray-400">
                      {new Date(c.created_at).toLocaleDateString("ar-SA")}
                    </p>
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>

        {/* Settlement History */}
        <Card title="سجل التسويات">
          {settlements.length === 0 ? (
            <p className="text-sm text-gray-400">لا توجد تسويات مسجلة</p>
          ) : (
            <div className="space-y-2 max-h-72 overflow-y-auto">
              {settlements.map((s) => (
                <div
                  key={s.id}
                  className="flex items-center justify-between rounded-lg border border-gray-100 p-3 dark:border-gray-700"
                >
                  <div>
                    <p className="text-sm font-medium">
                      {s.net_amount.toLocaleString("ar-SA")} {s.currency}
                    </p>
                    <p className="text-xs text-gray-400">
                      {new Date(s.period_start).toLocaleDateString("ar-SA")} →{" "}
                      {new Date(s.period_end).toLocaleDateString("ar-SA")}
                    </p>
                  </div>
                  <div className="text-left">
                    <span className="rounded-full bg-purple-100 px-2.5 py-0.5 text-xs font-medium text-purple-700 dark:bg-purple-900/30 dark:text-purple-300">
                      {s.status}
                    </span>
                    {s.processed_at && (
                      <p className="mt-1 text-xs text-gray-400">
                        {new Date(s.processed_at).toLocaleDateString("ar-SA")}
                      </p>
                    )}
                  </div>
                </div>
              ))}
            </div>
          )}
        </Card>
      </div>
    </div>
  );
}
