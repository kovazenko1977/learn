import React, { useState, useEffect } from 'react';
import { LanguageProvider } from './LanguageContext';
import { ThemeProvider } from './ThemeContext';
import { Login } from './pages/Login';
import { Layout } from './components/Layout';
import { Dashboard } from './pages/Dashboard';
import { Tickets } from './pages/Tickets';
import { FormBuilder } from './pages/FormBuilder';
import { Settings } from './pages/Settings';
import { Profile } from './pages/Profile';

export const App: React.FC = () => {
  const [token, setToken] = useState<string | null>(localStorage.getItem('crm_token'));
  const [user, setUser] = useState<any | null>(null);
  const [activeTab, setActiveTab] = useState('dashboard');

  useEffect(() => {
    const savedUser = localStorage.getItem('crm_user');
    if (token && savedUser) {
      setUser(JSON.parse(savedUser));
    } else {
      handleLogout();
    }
  }, [token]);

  // Support local storage update sync (for profile modifications)
  useEffect(() => {
    const handleStorageChange = () => {
      const savedUser = localStorage.getItem('crm_user');
      if (savedUser) {
        setUser(JSON.parse(savedUser));
      }
    };
    window.addEventListener('storage', handleStorageChange);
    return () => window.removeEventListener('storage', handleStorageChange);
  }, []);

  const handleLoginSuccess = (newToken: string, loggedUser: any) => {
    localStorage.setItem('crm_token', newToken);
    localStorage.setItem('crm_user', JSON.stringify(loggedUser));
    setUser(loggedUser);
    setToken(newToken);

    // Default tab depending on role
    if (['admin', 'head'].includes(loggedUser.role)) {
      setActiveTab('dashboard');
    } else {
      setActiveTab('tickets');
    }
  };

  const handleLogout = () => {
    localStorage.removeItem('crm_token');
    localStorage.removeItem('crm_user');
    setUser(null);
    setToken(null);
  };

  if (!token || !user) {
    return (
      <LanguageProvider>
        <ThemeProvider>
          <Login onLoginSuccess={handleLoginSuccess} />
        </ThemeProvider>
      </LanguageProvider>
    );
  }

  const renderContent = () => {
    switch (activeTab) {
      case 'dashboard':
        return <Dashboard />;
      case 'tickets':
        return <Tickets currentUser={user} />;
      case 'form-builder':
        return <FormBuilder />;
      case 'settings':
        return <Settings />;
      case 'profile':
        return <Profile />;
      default:
        return <Dashboard />;
    }
  };

  return (
    <LanguageProvider>
      <ThemeProvider>
        <Layout
          user={user}
          activeTab={activeTab}
          setActiveTab={setActiveTab}
          onLogout={handleLogout}
        >
          {renderContent()}
        </Layout>
      </ThemeProvider>
    </LanguageProvider>
  );
};

export default App;
