"use client";

import Link from "next/link";
import { usePathname } from "next/navigation";

interface NavItem {
  label: string;
  href: string;
  icon: string;
}

const userNav: NavItem[] = [
  { label: "الرئيسية", href: "/wap/user", icon: "🏠" },
  { label: "تحويل", href: "/wap/user/transfer", icon: "💸" },
  { label: "السجل", href: "/wap/user/history", icon: "📋" },
  { label: "QR", href: "/wap/user/qr", icon: "📷" },
];

const merchantNav: NavItem[] = [
  { label: "الملخص", href: "/wap/merchant", icon: "📊" },
  { label: "QR", href: "/wap/merchant/qr", icon: "🔲" },
  { label: "التسوية", href: "/wap/merchant/settlements", icon: "💰" },
];

const agentNav: NavItem[] = [
  { label: "الرئيسية", href: "/wap/agent", icon: "🏠" },
  { label: "معلق", href: "/wap/agent/pending", icon: "⏳" },
  { label: "الطابور", href: "/wap/agent/queue", icon: "📤" },
];

export default function BottomNav({ role }: { role: string }) {
  const pathname = usePathname();
  const nav = role === "merchant" ? merchantNav : role === "agent" ? agentNav : userNav;

  return (
    <nav className="fixed bottom-0 left-0 right-0 z-50 border-t border-gray-200 bg-white px-2 pb-2 pt-1 dark:border-gray-700 dark:bg-gray-900">
      <div className="mx-auto flex max-w-md justify-around">
        {nav.map((item) => {
          const active = pathname === item.href;
          return (
            <Link
              key={item.href}
              href={item.href}
              className={`flex flex-col items-center gap-0.5 rounded-lg px-3 py-1 text-xs ${
                active
                  ? "text-blue-600 dark:text-blue-400"
                  : "text-gray-500 dark:text-gray-400"
              }`}
            >
              <span className="text-lg">{item.icon}</span>
              <span>{item.label}</span>
            </Link>
          );
        })}
      </div>
    </nav>
  );
}
