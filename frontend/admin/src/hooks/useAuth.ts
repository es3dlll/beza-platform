"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { getMe, type AdminUser } from "@/lib/api/auth";

export function useAuth() {
  const router = useRouter();
  const [user, setUser] = useState<AdminUser | null>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    getMe()
      .then((d) => setUser(d.user))
      .catch(() => router.push("/login"))
      .finally(() => setLoading(false));
  }, [router]);

  return { user, loading };
}
