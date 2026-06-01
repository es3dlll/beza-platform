import type { Metadata } from "next";
import StatusBar from "@/components/pwa/StatusBar";

export const metadata: Metadata = {
  title: "بيزا — المنصة",
};

export default function WapLayout({ children }: { children: React.ReactNode }) {
  return (
    <>
      <StatusBar />
      <main className="mx-auto max-w-md px-4 pb-24 pt-4">{children}</main>
    </>
  );
}
