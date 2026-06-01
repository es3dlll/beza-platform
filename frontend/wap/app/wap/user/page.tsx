"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { getMe, Wallet, logout } from "@/lib/api/auth";
import { apiGet } from "@/lib/api/client";
import { ENDPOINTS } from "@/lib/api/endpoints";
import Card from "@/components/ui/Card";
import Button from "@/components/ui/Button";
import BottomNav from "@/components/layout/BottomNav";

export default function UserPage() {
  const router = useRouter();
  const [name, setName] = useState("");
  const [wallets, setWallets] = useState<Wallet[]>([]);

  useEffect(() => {
    getMe()
      .then((data) => {
        setName(data.user.name);
        setWallets(data.wallets);
      })
      .catch(() => router.push("/wap/login"));
  }, [router]);

  return (
    <>
      <Card className="mb-4">
        <div className="flex items-center justify-between">
          <p className="text-lg font-semibold">مرحباً، {name}</p>
          <button onClick={async () => { await logout(); router.push("/wap/login"); }} className="text-xs text-red-500">
            خروج
          </button>
        </div>
      </Card>

      <Card title="الرصيد" className="mb-4">
        {wallets.map((w) => (
          <div key={w.id} className="flex justify-between py-2">
            <span className="text-gray-600 dark:text-gray-300">
              {w.currency === "SYP" ? "🟢 ليرة سورية" : "🔵 دولار أمريكي"}
            </span>
            <span className="text-lg font-bold">
              {w.currency === "SYP" ? (w.balance / 100).toFixed(2) : (w.balance / 100).toFixed(2)}
            </span>
          </div>
        ))}
      </Card>

      <div className="grid grid-cols-2 gap-3">
        <Card className="text-center">
          <p className="text-2xl">💸</p>
          <p className="text-sm font-medium">تحويل</p>
        </Card>
        <Card className="text-center">
          <p className="text-2xl">📷</p>
          <p className="text-sm font-medium">مسح QR</p>
        </Card>
      </div>

      <BottomNav role="user" />
    </>
  );
}
