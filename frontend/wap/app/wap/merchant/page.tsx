"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { getMe } from "@/lib/api/auth";
import Card from "@/components/ui/Card";
import BottomNav from "@/components/layout/BottomNav";

export default function MerchantPage() {
  const router = useRouter();
  const [name, setName] = useState("");

  useEffect(() => {
    getMe().then((d) => setName(d.user.name)).catch(() => router.push("/wap/login"));
  }, [router]);

  return (
    <>
      <Card className="mb-4">
        <p className="text-lg font-semibold">🏪 {name}</p>
      </Card>
      <Card title="ملخص المبيعات" className="mb-4">
        <div className="space-y-2 text-sm">
          <div className="flex justify-between"><span>مبيعات اليوم</span><span>0 ل.س</span></div>
          <div className="flex justify-between"><span>مبيعات الأسبوع</span><span>0 ل.س</span></div>
          <div className="flex justify-between"><span>مبيعات الشهر</span><span>0 ل.س</span></div>
        </div>
      </Card>
      <Card title="التسوية المعلقة">
        <p className="text-center text-2xl font-bold text-yellow-600">0 ل.س</p>
      </Card>
      <BottomNav role="merchant" />
    </>
  );
}
