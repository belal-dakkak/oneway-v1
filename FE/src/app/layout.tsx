import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";
import { Toaster } from "@/components/ui/toaster";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "Oneway - Premium Fashion Store",
  description: "Discover elegant fashion and premium quality clothing at Oneway. Shop our curated collection of dresses, tops, jackets, and accessories.",
  keywords: ["Oneway", "Fashion", "Clothing", "E-commerce", "Style", "Premium", "Luxury"],
  authors: [{ name: "Oneway Team" }],
  icons: {
    icon: "/logo.svg",
  },
  openGraph: {
    title: "Oneway - Premium Fashion Store",
    description: "Discover elegant fashion and premium quality clothing at Oneway",
    url: "https://oneway.com",
    siteName: "Oneway",
    type: "website",
  },
  twitter: {
    card: "summary_large_image",
    title: "Oneway - Premium Fashion Store",
    description: "Discover elegant fashion and premium quality clothing at Oneway",
  },
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="en" suppressHydrationWarning>
      <body
        className={`${geistSans.variable} ${geistMono.variable} antialiased bg-background text-foreground`}
      >
        {children}
        <Toaster />
      </body>
    </html>
  );
}
