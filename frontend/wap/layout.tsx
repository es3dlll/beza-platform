import type { Metadata, Viewport } from "next";
import "./globals.css";

export const metadata: Metadata = {
  title: "بيزا — محفظة رقمية",
  description: "تطبيق ويب خفيف وسريع لإدارة محفظتك الرقمية",
  manifest: "/manifest.json",
  icons: { icon: "/icons/icon-192.png" },
};

export const viewport: Viewport = {
  themeColor: "#3b82f6",
  width: "device-width",
  initialScale: 1,
  maximumScale: 1,
};

export default function RootLayout({
  children,
}: Readonly<{ children: React.ReactNode }>) {
  return (
    <html lang="ar" dir="rtl">
      <head>
        <link rel="preconnect" href={process.env.NEXT_PUBLIC_API_URL} />
      </head>
      <body>{children}</body>
    </html>
  );
}
