"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { getMe, User, Wallet, logout } from "@/lib/api/auth";
import Card from "@/components/ui/Card";
import Button from "@/components/ui/Button";

export default function DashboardPage() {
  const router = useRouter();
  const [user, setUser] = useState<User | null>(null);
  const [wallets, setWallets] = useState<Wallet[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getMe()
      .then((data) => {
        setUser(data.user);
        setWallets(data.wallets);
        const role = data.user.role;
        if (role === "merchant") router.replace("/wap/merchant");
        else if (role === "agent") router.replace("/wap/agent");
        else router.replace("/wap/user");
      })
      .catch(() => router.replace("/wap/login"))
      .finally(() => setLoading(false));
  }, [router]);

  const handleLogout = async () => {
    await logout();
    router.push("/wap/login");
  };

  if (loading) {
    return (
      <div className="flex min-h-dvh items-center justify-center">
        <p className="text-gray-500">جاري التحميل...</p>
      </div>
    );
  }

  return (
    <div className="space-y-4 pt-8">
      <Card>
        <p className="text-lg font-semibold">مرحباً، {user?.name}</p>
      </Card>
      <Card title="الرصيد">
        <div className="space-y-2">
          {wallets.map((w) => (
            <div key={w.id} className="flex justify-between">
              <span>{w.currency === "SYP" ? "ليرة سورية" : "دولار أمريكي"}</span>
              <span className="font-bold">{w.balance}</span>
            </div>
          ))}
        </div>
      </Card>
      <Button variant="secondary" onClick={handleLogout}>
        تسجيل خروج
      </Button>
    </div>
  );
}
