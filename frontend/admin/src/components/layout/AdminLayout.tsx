"use client";

import { useEffect, useState } from "react";
import { useRouter } from "next/navigation";
import { getMe, logoutAdmin } from "@/lib/api/auth";
import Sidebar from "./Sidebar";
import type { AdminUser } from "@/lib/api/auth";

export default function AdminLayout({ children }: { children: React.ReactNode }) {
  const router = useRouter();
  const [user, setUser] = useState<AdminUser | null>(null);

  useEffect(() => {
    getMe()
      .then((d) => setUser(d.user))
      .catch(() => router.push("/login"));
  }, [router]);

  const handleLogout = async () => {
    await logoutAdmin();
    router.push("/login");
  };

  return (
    <div className="flex h-dvh">
      <Sidebar />
      <div className="flex flex-1 flex-col">
        <header className="flex items-center justify-between border-b border-gray-200 bg-white px-6 py-3 dark:border-gray-700 dark:bg-gray-900">
          <div />
          <div className="flex items-center gap-4">
            {user && (
              <span className="text-sm text-gray-600 dark:text-gray-400">
                {user.name}
              </span>
            )}
            <button
              onClick={handleLogout}
              className="text-sm text-red-500 hover:text-red-700"
            >
              خروج
            </button>
          </div>
        </header>
        <main className="flex-1 overflow-y-auto bg-gray-50 p-6 dark:bg-gray-950">
          {children}
        </main>
      </div>
    </div>
  );
}
