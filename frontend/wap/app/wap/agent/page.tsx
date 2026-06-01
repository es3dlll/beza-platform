"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { getMe } from "@/lib/api/auth";
import Card from "@/components/ui/Card";
import BottomNav from "@/components/layout/BottomNav";

export default function AgentPage() {
  const router = useRouter();
  const [name, setName] = useState("");

  useEffect(() => {
    getMe().then((d) => setName(d.user.name)).catch(() => router.push("/wap/login"));
  }, [router]);

  return (
    <>
      <Card className="mb-4">
        <p className="text-lg font-semibold">🏧 الوكيل: {name}</p>
      </Card>

      <div className="grid grid-cols-2 gap-3 mb-4">
        <Card title="حد الإيداع">
          <p className="text-xl font-bold text-green-600">٥٬٠٠٠٬٠٠٠</p>
          <p className="text-xs text-gray-500">ل.س اليوم</p>
        </Card>
        <Card title="حد السحب">
          <p className="text-xl font-bold text-red-600">٣٬٠٠٠٬٠٠٠</p>
          <p className="text-xs text-gray-500">ل.س اليوم</p>
        </Card>
      </div>

      <Card title="عمولة اليوم">
        <p className="text-center text-2xl font-bold text-blue-600">٠ ل.س</p>
      </Card>

      <BottomNav role="agent" />
    </>
  );
}
