import type { Metadata } from "next";
import { Geist, Geist_Mono } from "next/font/google";
import "./globals.css";
import Link from "next/link";
import { AuthProvider } from "@/context/auth-context";

const geistSans = Geist({
  variable: "--font-geist-sans",
  subsets: ["latin"],
});

const geistMono = Geist_Mono({
  variable: "--font-geist-mono",
  subsets: ["latin"],
});

export const metadata: Metadata = {
  title: "ALCO.BY - Промышленная ERP",
  description: "Система учета готовой продукции и управления ценами",
};

export default function RootLayout({
  children,
}: Readonly<{
  children: React.ReactNode;
}>) {
  return (
    <html lang="ru">
      <body className={`${geistSans.variable} ${geistMono.variable} antialiased bg-gray-50 text-gray-900`}>
        <AuthProvider>
        <div className="min-h-screen flex flex-col">
          <header className="bg-white border-b shadow-sm sticky top-0 z-10">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 h-16 flex items-center justify-between">
              <div className="flex items-center space-x-8">
                <Link href="/" className="text-2xl font-bold text-blue-600">ALCO.BY</Link>
                <nav className="hidden md:flex space-x-4 text-sm font-medium">
                  <Link href="/products" className="hover:text-blue-600 transition-colors">Товары</Link>
                  <Link href="/clients" className="hover:text-blue-600 transition-colors">Клиенты</Link>
                  <Link href="/orders" className="hover:text-blue-600 transition-colors">Заказы</Link>
                  <Link href="/lk" className="hover:text-blue-600 transition-colors">Личный кабинет</Link>
                </nav>
              </div>
              <div className="flex items-center space-x-4">
                <Link href="/login" className="px-4 py-2 text-sm bg-blue-600 text-white rounded-md hover:bg-blue-700 transition-colors">Войти</Link>
              </div>
            </div>
          </header>
          <main className="flex-grow max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 py-8 w-full">
            {children}
          </main>
          <footer className="bg-white border-t py-6">
            <div className="max-w-7xl mx-auto px-4 sm:px-6 lg:px-8 text-center text-sm text-gray-500">
              &copy; 2024 ALCO.BY. Промышленное совершенство.
            </div>
          </footer>
        </div>
        </AuthProvider>
      </body>
    </html>
  );
}
