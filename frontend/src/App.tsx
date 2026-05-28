import React, { useState, useEffect } from 'react';
import { Layout, Printer, Settings as SettingsIcon, Package, LogOut } from 'lucide-react';
import Editor from './components/Editor';
import ProductCatalog from './components/ProductCatalog';
import PrintModule from './components/PrintModule';
import Login from './components/Login';

function App() {
  const [activeTab, setActiveTab] = useState('editor');
  const [user, setUser] = useState<any>(null);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    const savedUser = localStorage.getItem('user');
    if (savedUser) {
      setUser(JSON.parse(savedUser));
    }
    setLoading(false);
  }, []);

  const handleLogin = (userData: any, token: string) => {
    setUser(userData);
    localStorage.setItem('user', JSON.stringify(userData));
    localStorage.setItem('token', token);
  };

  const handleLogout = () => {
    setUser(null);
    localStorage.removeItem('user');
    localStorage.removeItem('token');
  };

  if (loading) return null;
  if (!user) return <Login onLogin={handleLogin} />;

  return (
    <div className="flex h-screen bg-industrial-900 text-gray-200">
      {/* Sidebar */}
      <div className="w-16 bg-black flex flex-col items-center py-6 space-y-8 border-r border-industrial-700">
        <div className="text-industrial-primary font-bold text-xl mb-4">VS</div>

        <SidebarIcon
          icon={<Layout size={24} />}
          active={activeTab === 'editor'}
          onClick={() => setActiveTab('editor')}
          label="Редактор"
        />
        <SidebarIcon
          icon={<Package size={24} />}
          active={activeTab === 'products'}
          onClick={() => setActiveTab('products')}
          label="Товары"
        />
        <SidebarIcon
          icon={<Printer size={24} />}
          active={activeTab === 'print'}
          onClick={() => setActiveTab('print')}
          label="Печать"
        />
        <SidebarIcon
          icon={<SettingsIcon size={24} />}
          active={activeTab === 'settings'}
          onClick={() => setActiveTab('settings')}
          label="Настройки"
        />

        <div className="flex-1"></div>

        <SidebarIcon
          icon={<LogOut size={24} />}
          active={false}
          onClick={handleLogout}
          label="Выход"
        />
      </div>

      {/* Main Content */}
      <div className="flex-1 flex flex-col overflow-hidden">
        {activeTab === 'editor' && <Editor />}
        {activeTab === 'products' && <ProductCatalog />}
        {activeTab === 'print' && <PrintModule />}
        {activeTab === 'settings' && (
           <div className="p-8 text-center text-gray-500 mt-20">Системные настройки...</div>
        )}
      </div>
    </div>
  );
}

function SidebarIcon({ icon, active, onClick, label }: any) {
  return (
    <div
      className={`relative group cursor-pointer p-3 rounded-xl transition-all ${
        active ? 'bg-industrial-primary text-black' : 'text-gray-500 hover:bg-industrial-800'
      }`}
      onClick={onClick}
    >
      {icon}
      <span className="absolute left-16 scale-0 group-hover:scale-100 transition-all origin-left bg-industrial-700 text-white text-xs px-2 py-1 rounded-md whitespace-nowrap z-50">
        {label}
      </span>
    </div>
  );
}

export default App;
