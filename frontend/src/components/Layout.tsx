import React, { useState } from 'react';
import { useLanguage } from '../LanguageContext';
import { useTheme } from '../ThemeContext';
import {
  LayoutDashboard,
  Wrench,
  Settings,
  User,
  LogOut,
  Menu,
  X,
  Sun,
  Moon,
  Globe,
  SquarePlay
} from 'lucide-react';

interface LayoutProps {
  user: {
    username: string;
    fullName: string;
    role: 'admin' | 'responsible' | 'head' | 'executor';
  };
  activeTab: string;
  setActiveTab: (tab: string) => void;
  onLogout: () => void;
  children: React.ReactNode;
}

export const Layout: React.FC<LayoutProps> = ({ user, activeTab, setActiveTab, onLogout, children }) => {
  const { t, language, setLanguage } = useLanguage();
  const { theme, toggleTheme } = useTheme();
  const [mobileMenuOpen, setMobileMenuOpen] = useState(false);

  const menuItems = [
    { id: 'dashboard', label: t('dashboard'), icon: LayoutDashboard, roles: ['admin', 'head'] },
    { id: 'tickets', label: t('tickets'), icon: Wrench, roles: ['admin', 'head', 'responsible', 'executor'] },
    { id: 'form-builder', label: t('formBuilder'), icon: SquarePlay, roles: ['admin'] },
    { id: 'settings', label: t('settings'), icon: Settings, roles: ['admin'] },
    { id: 'profile', label: t('profile'), icon: User, roles: ['admin', 'head', 'responsible', 'executor'] }
  ];

  const allowedMenuItems = menuItems.filter(item => item.roles.includes(user.role));

  const getRoleBadgeColor = (role: string) => {
    switch(role) {
      case 'admin': return 'bg-red-100 text-red-800 dark:bg-red-950/40 dark:text-red-400';
      case 'head': return 'bg-purple-100 text-purple-800 dark:bg-purple-950/40 dark:text-purple-400';
      case 'responsible': return 'bg-blue-100 text-blue-800 dark:bg-blue-950/40 dark:text-blue-400';
      default: return 'bg-green-100 text-green-800 dark:bg-green-950/40 dark:text-green-400';
    }
  };

  const getRoleLabel = (role: string) => {
    switch(role) {
      case 'admin': return t('admin');
      case 'head': return t('head');
      case 'responsible': return t('responsible');
      default: return t('executor');
    }
  };

  return (
    <div className="min-h-screen flex bg-slate-50 dark:bg-slate-950 text-slate-800 dark:text-slate-100 transition-colors duration-200">
      {/* Sidebar for Desktop */}
      <aside className="hidden lg:flex flex-col w-64 bg-white dark:bg-slate-900 border-r border-slate-200/60 dark:border-slate-800/60 shadow-sm">
        <div className="h-16 flex items-center px-6 border-b border-slate-200/60 dark:border-slate-800/60">
          <div className="flex items-center gap-2 font-bold text-lg text-primary-600 dark:text-primary-400">
            <Wrench className="w-6 h-6" />
            <span>{t('appName')}</span>
          </div>
        </div>

        {/* User Card */}
        <div className="p-4 border-b border-slate-200/40 dark:border-slate-800/40">
          <div className="flex items-center gap-3">
            <div className="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-950 flex items-center justify-center font-bold text-primary-700 dark:text-primary-300">
              {user.fullName.charAt(0)}
            </div>
            <div className="flex-1 min-w-0">
              <p className="text-sm font-semibold truncate text-slate-900 dark:text-slate-50">{user.fullName}</p>
              <span className={`inline-block px-2 py-0.5 mt-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${getRoleBadgeColor(user.role)}`}>
                {getRoleLabel(user.role)}
              </span>
            </div>
          </div>
        </div>

        <nav className="flex-1 p-4 space-y-1">
          {allowedMenuItems.map(item => {
            const IconComponent = item.icon;
            const isActive = activeTab === item.id;
            return (
              <button
                key={item.id}
                onClick={() => setActiveTab(item.id)}
                className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition duration-150 ${
                  isActive
                    ? 'bg-primary-50 dark:bg-primary-950/30 text-primary-600 dark:text-primary-400 font-semibold shadow-sm border border-primary-100/50 dark:border-primary-900/30'
                    : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50 hover:text-slate-900 dark:hover:text-slate-200'
                }`}
              >
                <IconComponent className="w-5 h-5" />
                <span>{item.label}</span>
              </button>
            );
          })}
        </nav>

        <div className="p-4 border-t border-slate-200/60 dark:border-slate-800/60">
          <button
            onClick={onLogout}
            className="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 transition duration-150"
          >
            <LogOut className="w-5 h-5" />
            <span>{t('logout')}</span>
          </button>
        </div>
      </aside>

      {/* Main Panel */}
      <div className="flex-1 flex flex-col min-w-0">
        {/* Header */}
        <header className="h-16 flex items-center justify-between px-4 lg:px-8 bg-white dark:bg-slate-900 border-b border-slate-200/60 dark:border-slate-800/60 shadow-sm sticky top-0 z-40">
          <div className="flex items-center gap-2">
            <button
              onClick={() => setMobileMenuOpen(true)}
              className="lg:hidden p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
            >
              <Menu className="w-6 h-6" />
            </button>
            <h1 className="text-lg font-bold text-slate-900 dark:text-slate-50 lg:block hidden">
              {menuItems.find(i => i.id === activeTab)?.label || t('dashboard')}
            </h1>
          </div>

          <div className="flex items-center gap-3">
            {/* Language Toggle */}
            <button
              onClick={() => setLanguage(language === 'RU' ? 'EN' : 'RU')}
              className="flex items-center gap-1.5 px-3 py-1.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 text-sm font-semibold transition"
            >
              <Globe className="w-4 h-4 text-slate-500" />
              <span>{language}</span>
            </button>

            {/* Theme Toggle */}
            <button
              onClick={toggleTheme}
              className="p-1.5 rounded-lg border border-slate-200 dark:border-slate-800 hover:bg-slate-50 dark:hover:bg-slate-800 transition"
            >
              {theme === 'light' ? (
                <Moon className="w-5 h-5 text-slate-600" />
              ) : (
                <Sun className="w-5 h-5 text-amber-400" />
              )}
            </button>
          </div>
        </header>

        {/* Content Container */}
        <main className="flex-1 p-4 lg:p-8 overflow-y-auto">
          {children}
        </main>
      </div>

      {/* Mobile Drawer Menu */}
      {mobileMenuOpen && (
        <div className="fixed inset-0 z-50 flex lg:hidden bg-slate-950/60 backdrop-blur-sm">
          <div className="w-72 bg-white dark:bg-slate-900 p-6 flex flex-col shadow-2xl relative animate-in slide-in-from-left duration-200">
            <button
              onClick={() => setMobileMenuOpen(false)}
              className="absolute top-4 right-4 p-1.5 rounded-lg text-slate-500 hover:bg-slate-100 dark:hover:bg-slate-800"
            >
              <X className="w-5 h-5" />
            </button>

            <div className="flex items-center gap-2 font-bold text-lg text-primary-600 dark:text-primary-400 mb-8 mt-2">
              <Wrench className="w-6 h-6" />
              <span>{t('appName')}</span>
            </div>

            <div className="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-950/40 rounded-xl mb-6 border border-slate-200/50 dark:border-slate-800/50">
              <div className="w-10 h-10 rounded-full bg-primary-100 dark:bg-primary-950 flex items-center justify-center font-bold text-primary-700 dark:text-primary-300">
                {user.fullName.charAt(0)}
              </div>
              <div className="flex-1 min-w-0">
                <p className="text-sm font-semibold truncate text-slate-900 dark:text-slate-50">{user.fullName}</p>
                <span className={`inline-block px-2 py-0.5 mt-0.5 rounded text-[10px] font-bold uppercase tracking-wider ${getRoleBadgeColor(user.role)}`}>
                  {getRoleLabel(user.role)}
                </span>
              </div>
            </div>

            <nav className="flex-1 space-y-1">
              {allowedMenuItems.map(item => {
                const IconComponent = item.icon;
                const isActive = activeTab === item.id;
                return (
                  <button
                    key={item.id}
                    onClick={() => {
                      setActiveTab(item.id);
                      setMobileMenuOpen(false);
                    }}
                    className={`w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium transition ${
                      isActive
                        ? 'bg-primary-50 dark:bg-primary-950/30 text-primary-600 dark:text-primary-400 font-semibold'
                        : 'text-slate-600 dark:text-slate-400 hover:bg-slate-100 dark:hover:bg-slate-800/50'
                    }`}
                  >
                    <IconComponent className="w-5 h-5" />
                    <span>{item.label}</span>
                  </button>
                );
              })}
            </nav>

            <div className="pt-4 border-t border-slate-200/60 dark:border-slate-800/60">
              <button
                onClick={() => {
                  setMobileMenuOpen(false);
                  onLogout();
                }}
                className="w-full flex items-center gap-3 px-4 py-2.5 rounded-xl text-sm font-medium text-red-600 hover:bg-red-50 dark:hover:bg-red-950/30 transition"
              >
                <LogOut className="w-5 h-5" />
                <span>{t('logout')}</span>
              </button>
            </div>
          </div>
        </div>
      )}
    </div>
  );
};
