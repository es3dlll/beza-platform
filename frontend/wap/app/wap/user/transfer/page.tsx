"use client";

import { useState } from "react";
import { apiPost } from "@/lib/api/client";
import { ENDPOINTS } from "@/lib/api/endpoints";
import { offlineQueue } from "@/lib/db/offline-queue";
import Card from "@/components/ui/Card";
import Input from "@/components/ui/Input";
import Button from "@/components/ui/Button";
import BottomNav from "@/components/layout/BottomNav";

export default function TransferPage() {
  const [phone, setPhone] = useState("");
  const [amount, setAmount] = useState("");
  const [note, setNote] = useState("");
  const [status, setStatus] = useState<"idle" | "loading" | "done" | "queued" | "error">("idle");
  const [message, setMessage] = useState("");

  const handleTransfer = async (e: React.FormEvent) => {
    e.preventDefault();
    setStatus("loading");

    const idempotencyKey = crypto.randomUUID();
    const body = {
      receiver_phone: phone,
      amount: parseInt(amount) * 100,
      currency: "SYP",
      idempotency_key: idempotencyKey,
      note,
    };

    try {
      const res = await apiPost(ENDPOINTS.WALLET_TRANSFER, body);
      if (res.success) {
        setStatus("done");
        setMessage("✅ تم التحويل بنجاح");
      } else {
        setStatus("error");
        setMessage(res.error?.message || "فشل التحويل");
      }
    } catch {
      await offlineQueue.add("POST", ENDPOINTS.WALLET_TRANSFER, body, idempotencyKey);
      setStatus("queued");
      setMessage("📤 تم حفظ التحويل — سيُرسل تلقائياً عند توفر الاتصال");
    }
  };

  return (
    <>
      <Card title="تحويل سريع" className="mb-4">
        <form onSubmit={handleTransfer} className="space-y-4">
          <Input
            label="رقم المستلم"
            value={phone}
            onChange={(e) => setPhone(e.target.value)}
            placeholder="09xxxxxxxx"
            required
          />
          <Input
            label="المبلغ (ل.س)"
            type="number"
            value={amount}
            onChange={(e) => setAmount(e.target.value)}
            placeholder="500"
            required
            min="1"
          />
          <Input
            label="ملاحظة (اختياري)"
            value={note}
            onChange={(e) => setNote(e.target.value)}
            placeholder="سبب التحويل"
          />

          {message && (
            <p className={`text-sm ${status === "error" ? "text-red-500" : "text-green-500"}`}>
              {message}
            </p>
          )}

          <Button type="submit" disabled={status === "loading"}>
            {status === "loading" ? "جاري..." : "تحويل"}
          </Button>
        </form>
      </Card>

      <BottomNav role="user" />
    </>
  );
}
