import React, { useState, useEffect } from 'react';
import { useLanguage } from '../LanguageContext';
import { Database, Users, Trash2, Plus, Edit, ShieldAlert, Key, Settings as SettingsIcon } from 'lucide-react';

export const Settings: React.FC = () => {
  const { t } = useLanguage();
  const [categories, setCategories] = useState<string[]>([]);
  const [priorities, setPriorities] = useState<string[]>([]);
  const [sla, setSla] = useState<Record<string, number>>({});

  // Storage config
  const [storageMode, setStorageMode] = useState('JSON');
  const [mysqlHost, setMysqlHost] = useState('localhost');
  const [mysqlPort, setMysqlPort] = useState('3306');
  const [mysqlUser, setMysqlUser] = useState('');
  const [mysqlPassword, setMysqlPassword] = useState('');
  const [mysqlDatabase, setMysqlDatabase] = useState('');
  const [dbError, setDbError] = useState('');
  const [dbSuccess, setDbSuccess] = useState('');

  // Users Management
  const [users, setUsers] = useState<any[]>([]);
  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [role, setRole] = useState('executor');
  const [editUserId, setEditUserId] = useState<string | null>(null);

  // New item helpers
  const [newCategory, setNewCategory] = useState('');

  useEffect(() => {
    fetchSettings();
    fetchUsers();
  }, []);

  const fetchSettings = async () => {
    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/admin/settings', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      if (res.ok) {
        const data = await res.json();
        setCategories(data.categories || []);
        setPriorities(data.priorities || []);
        setSla(data.sla || {});
        setStorageMode(data.storageMode || 'JSON');
        if (data.mysqlConfig) {
          setMysqlHost(data.mysqlConfig.host || 'localhost');
          setMysqlPort(data.mysqlConfig.port || '3306');
          setMysqlUser(data.mysqlConfig.user || '');
          setMysqlDatabase(data.mysqlConfig.database || '');
        }
      }
    } catch (e) {
      console.error(e);
    }
  };

  const fetchUsers = async () => {
    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/admin/users', {
        headers: { 'Authorization': `Bearer ${token}` }
      });
      if (res.ok) {
        const data = await res.json();
        setUsers(data);
      }
    } catch (e) {
      console.error(e);
    }
  };

  const handleSaveSettings = async (updatedCategories?: string[], updatedSla?: Record<string, number>) => {
    const payload = {
      categories: updatedCategories || categories,
      priorities,
      sla: updatedSla || sla
    };

    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/admin/settings', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(payload)
      });
      if (res.ok) {
        fetchSettings();
      }
    } catch (e) {
      console.error(e);
    }
  };

  const handleAddCategory = () => {
    if (!newCategory.trim()) return;
    const updated = [...categories, newCategory.trim()];
    setCategories(updated);
    setNewCategory('');
    handleSaveSettings(updated);
  };

  const handleDeleteCategory = (cat: string) => {
    const updated = categories.filter(c => c !== cat);
    setCategories(updated);
    handleSaveSettings(updated);
  };

  const handleSlaChange = (priority: string, hours: number) => {
    const updatedSla = { ...sla, [priority]: hours };
    setSla(updatedSla);
    handleSaveSettings(undefined, updatedSla);
  };

  const handleSwitchStorage = async (e: React.FormEvent) => {
    e.preventDefault();
    setDbError('');
    setDbSuccess('');

    const payload = {
      mode: storageMode,
      mysqlConfig: storageMode === 'MySQL' ? {
        host: mysqlHost,
        port: Number(mysqlPort),
        user: mysqlUser,
        password: mysqlPassword,
        database: mysqlDatabase
      } : null
    };

    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/admin/settings/storage-mode', {
        method: 'POST',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(payload)
      });

      const data = await res.json();
      if (!res.ok) {
        throw new Error(data.error || 'Failed to switch storage mode');
      }

      setDbSuccess(data.message);
      fetchSettings();
    } catch (err: any) {
      setDbError(err.message || 'Ошибка подключения');
    }
  };

  // User Actions
  const handleCreateOrUpdateUser = async (e: React.FormEvent) => {
    e.preventDefault();
    const token = localStorage.getItem('crm_token');
    const url = editUserId ? `/api/admin/users/${editUserId}` : '/api/admin/users';
    const method = editUserId ? 'PUT' : 'POST';

    const payload = {
      fullName,
      email,
      role,
      ...(username ? { username } : {}),
      ...(password ? { password } : {})
    };

    try {
      const res = await fetch(url, {
        method,
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify(payload)
      });

      if (res.ok) {
        // Clear
        setUsername('');
        setPassword('');
        setFullName('');
        setEmail('');
        setRole('executor');
        setEditUserId(null);
        fetchUsers();
      } else {
        const errData = await res.json();
        alert(errData.error || 'Error saving user');
      }
    } catch (e) {
      console.error(e);
    }
  };

  const handleEditUser = (user: any) => {
    setEditUserId(user.id);
    setUsername(user.username);
    setFullName(user.fullName);
    setEmail(user.email);
    setRole(user.role);
    setPassword('');
  };

  const handleDeleteUser = async (id: string) => {
    if (confirm('Вы уверены, что хотите удалить этого пользователя?')) {
      try {
        const token = localStorage.getItem('crm_token');
        const res = await fetch(`/api/admin/users/${id}`, {
          method: 'DELETE',
          headers: { 'Authorization': `Bearer ${token}` }
        });
        if (res.ok) {
          fetchUsers();
        }
      } catch (e) {
        console.error(e);
      }
    }
  };

  return (
    <div className="space-y-8">
      {/* 1. Storage & Database Mode Switcher */}
      <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
        <div className="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/50 dark:border-slate-800/50 shadow-sm lg:col-span-1 h-fit">
          <div className="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800 mb-4">
            <Database className="w-5 h-5 text-primary-500" />
            <h4 className="font-bold text-slate-900 dark:text-slate-50 text-sm">{t('storageMode')}</h4>
          </div>

          <form onSubmit={handleSwitchStorage} className="space-y-4 text-xs">
            <div className="flex bg-slate-100 dark:bg-slate-850 p-1 rounded-xl">
              <button
                type="button"
                onClick={() => setStorageMode('JSON')}
                className={`flex-1 py-2 font-bold rounded-lg ${storageMode === 'JSON' ? 'bg-white dark:bg-slate-900 shadow-sm text-slate-950 dark:text-slate-100' : 'text-slate-500'}`}
              >
                JSON File
              </button>
              <button
                type="button"
                onClick={() => setStorageMode('MySQL')}
                className={`flex-1 py-2 font-bold rounded-lg ${storageMode === 'MySQL' ? 'bg-white dark:bg-slate-900 shadow-sm text-slate-950 dark:text-slate-100' : 'text-slate-500'}`}
              >
                MySQL DB
              </button>
            </div>

            {storageMode === 'MySQL' && (
              <div className="space-y-3 p-3 bg-slate-50 dark:bg-slate-950/20 rounded-xl border border-slate-200/40">
                <p className="font-bold text-slate-500 uppercase text-[10px] tracking-wide">{t('mysqlConfig')}</p>
                <div>
                  <label className="block mb-1 text-slate-500">Host</label>
                  <input
                    type="text"
                    value={mysqlHost}
                    onChange={(e) => setMysqlHost(e.target.value)}
                    className="w-full px-2.5 py-1.5 rounded-lg border dark:bg-slate-900"
                  />
                </div>
                <div>
                  <label className="block mb-1 text-slate-500">Port</label>
                  <input
                    type="text"
                    value={mysqlPort}
                    onChange={(e) => setMysqlPort(e.target.value)}
                    className="w-full px-2.5 py-1.5 rounded-lg border dark:bg-slate-900"
                  />
                </div>
                <div>
                  <label className="block mb-1 text-slate-500">Database</label>
                  <input
                    type="text"
                    required
                    value={mysqlDatabase}
                    placeholder="crm_db"
                    onChange={(e) => setMysqlDatabase(e.target.value)}
                    className="w-full px-2.5 py-1.5 rounded-lg border dark:bg-slate-900"
                  />
                </div>
                <div>
                  <label className="block mb-1 text-slate-500">User</label>
                  <input
                    type="text"
                    required
                    value={mysqlUser}
                    placeholder="root"
                    onChange={(e) => setMysqlUser(e.target.value)}
                    className="w-full px-2.5 py-1.5 rounded-lg border dark:bg-slate-900"
                  />
                </div>
                <div>
                  <label className="block mb-1 text-slate-500">Password</label>
                  <input
                    type="password"
                    value={mysqlPassword}
                    onChange={(e) => setMysqlPassword(e.target.value)}
                    className="w-full px-2.5 py-1.5 rounded-lg border dark:bg-slate-900"
                  />
                </div>
              </div>
            )}

            {dbError && (
              <div className="p-3 bg-red-50 dark:bg-red-950/20 text-red-600 dark:text-red-400 rounded-lg text-xs font-semibold">
                {dbError}
              </div>
            )}

            {dbSuccess && (
              <div className="p-3 bg-green-50 dark:bg-green-950/20 text-green-600 dark:text-green-400 rounded-lg text-xs font-semibold">
                {dbSuccess}
              </div>
            )}

            <button
              type="submit"
              className="w-full py-2 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl transition"
            >
              Применить режим хранения
            </button>
          </form>
        </div>

        {/* Categories & SLA target controls */}
        <div className="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/50 dark:border-slate-800/50 shadow-sm lg:col-span-2 space-y-6">
          <div className="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800">
            <SettingsIcon className="w-5 h-5 text-primary-500" />
            <h4 className="font-bold text-slate-900 dark:text-slate-50 text-sm">Параметры и сроки (SLA)</h4>
          </div>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-6">
            {/* Categories list */}
            <div className="space-y-3">
              <h5 className="font-bold text-slate-900 dark:text-slate-50 text-xs uppercase tracking-wider text-slate-400">Категории заявок</h5>
              <div className="flex gap-2">
                <input
                  type="text"
                  placeholder="Новая категория"
                  value={newCategory}
                  onChange={(e) => setNewCategory(e.target.value)}
                  className="px-3 py-1.5 text-xs rounded-xl border flex-1 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-100"
                />
                <button
                  onClick={handleAddCategory}
                  className="p-1.5 bg-primary-600 hover:bg-primary-700 text-white rounded-xl"
                >
                  <Plus className="w-4 h-4" />
                </button>
              </div>

              <div className="space-y-2 max-h-[160px] overflow-y-auto">
                {categories.map(cat => (
                  <div key={cat} className="flex justify-between items-center p-2.5 bg-slate-50 dark:bg-slate-950/20 rounded-lg text-xs">
                    <span className="font-semibold text-slate-800 dark:text-slate-200">{cat}</span>
                    <button
                      onClick={() => handleDeleteCategory(cat)}
                      className="text-red-500 hover:text-red-700"
                    >
                      <Trash2 className="w-3.5 h-3.5" />
                    </button>
                  </div>
                ))}
              </div>
            </div>

            {/* SLA control */}
            <div className="space-y-3">
              <h5 className="font-bold text-slate-900 dark:text-slate-50 text-xs uppercase tracking-wider text-slate-400">{t('slaSettings')}</h5>
              <div className="space-y-3">
                {['Low', 'Medium', 'High', 'Critical'].map(priority => (
                  <div key={priority} className="flex items-center justify-between text-xs">
                    <span className="font-semibold text-slate-600 dark:text-slate-300">{priority}</span>
                    <div className="flex items-center gap-2">
                      <input
                        type="number"
                        value={sla[priority] || 24}
                        onChange={(e) => handleSlaChange(priority, Number(e.target.value))}
                        className="w-16 px-2.5 py-1 rounded-lg border text-center text-slate-900"
                      />
                      <span className="text-slate-400">ч (hours)</span>
                    </div>
                  </div>
                ))}
              </div>
            </div>
          </div>
        </div>
      </div>

      {/* 2. User Accounts Management Section */}
      <div className="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/50 dark:border-slate-800/50 shadow-sm">
        <div className="flex items-center gap-2 pb-3 border-b border-slate-100 dark:border-slate-800 mb-6">
          <Users className="w-5 h-5 text-primary-500" />
          <h4 className="font-bold text-slate-900 dark:text-slate-50 text-sm">{t('usersManagement')}</h4>
        </div>

        <div className="grid grid-cols-1 lg:grid-cols-3 gap-6">
          {/* User Form */}
          <div className="bg-slate-50 dark:bg-slate-950/30 p-5 rounded-2xl border border-slate-200/40 h-fit space-y-4 text-xs">
            <h5 className="font-bold text-slate-900 dark:text-slate-50 text-xs">
              {editUserId ? 'Редактировать пользователя' : t('addNewUser')}
            </h5>

            <form onSubmit={handleCreateOrUpdateUser} className="space-y-3">
              {!editUserId && (
                <div>
                  <label className="block mb-1 text-slate-500">{t('username')}</label>
                  <input
                    type="text"
                    required
                    value={username}
                    onChange={(e) => setUsername(e.target.value)}
                    className="w-full px-3 py-2 rounded-xl border bg-white dark:bg-slate-900 text-slate-900"
                  />
                </div>
              )}

              <div>
                <label className="block mb-1 text-slate-500">{t('fullName')}</label>
                <input
                  type="text"
                  required
                  value={fullName}
                  onChange={(e) => setFullName(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border bg-white dark:bg-slate-900 text-slate-900"
                />
              </div>

              <div>
                <label className="block mb-1 text-slate-500">{t('email')}</label>
                <input
                  type="email"
                  required
                  value={email}
                  onChange={(e) => setEmail(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border bg-white dark:bg-slate-900 text-slate-900"
                />
              </div>

              <div>
                <label className="block mb-1 text-slate-500">{t('password')}</label>
                <input
                  type="password"
                  required={!editUserId}
                  placeholder={editUserId ? 'Оставьте пустым...' : '••••••'}
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border bg-white dark:bg-slate-900 text-slate-900"
                />
              </div>

              <div>
                <label className="block mb-1 text-slate-500">{t('role')}</label>
                <select
                  value={role}
                  onChange={(e) => setRole(e.target.value)}
                  className="w-full px-3 py-2 rounded-xl border bg-white dark:bg-slate-900 text-slate-900"
                >
                  <option value="admin">{t('admin')}</option>
                  <option value="head">{t('head')}</option>
                  <option value="responsible">{t('responsible')}</option>
                  <option value="executor">{t('executor')}</option>
                </select>
              </div>

              <div className="pt-2 flex gap-2">
                {editUserId && (
                  <button
                    type="button"
                    onClick={() => {
                      setEditUserId(null);
                      setUsername('');
                      setPassword('');
                      setFullName('');
                      setEmail('');
                      setRole('executor');
                    }}
                    className="px-3 py-2 border rounded-xl text-slate-600 dark:text-slate-300 font-bold flex-1"
                  >
                    {t('cancel')}
                  </button>
                )}
                <button
                  type="submit"
                  className="px-4 py-2 bg-primary-600 hover:bg-primary-700 text-white font-bold rounded-xl flex-1 transition"
                >
                  {t('save')}
                </button>
              </div>
            </form>
          </div>

          {/* Users List */}
          <div className="lg:col-span-2 overflow-x-auto border border-slate-100 dark:border-slate-800 rounded-xl">
            <table className="w-full text-left text-xs">
              <thead>
                <tr className="border-b bg-slate-50 dark:bg-slate-900/40 text-slate-400 font-medium">
                  <th className="p-3">{t('fullName')}</th>
                  <th className="p-3">{t('username')}</th>
                  <th className="p-3">{t('role')}</th>
                  <th className="p-3">Email</th>
                  <th className="p-3 text-right">Управление</th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800/50">
                {users.map(u => (
                  <tr key={u.id} className="text-slate-700 dark:text-slate-300">
                    <td className="p-3 font-semibold text-slate-900 dark:text-slate-100">{u.fullName}</td>
                    <td className="p-3"><code className="bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded font-bold">{u.username}</code></td>
                    <td className="p-3 font-semibold uppercase text-[10px] text-primary-600">{u.role}</td>
                    <td className="p-3">{u.email}</td>
                    <td className="p-3 text-right flex justify-end gap-1">
                      <button
                        onClick={() => handleEditUser(u)}
                        className="p-1.5 rounded-lg border bg-white dark:bg-slate-800 hover:text-primary-600 transition"
                      >
                        <Edit className="w-3.5 h-3.5" />
                      </button>
                      <button
                        onClick={() => handleDeleteUser(u.id)}
                        className="p-1.5 rounded-lg border bg-red-50 text-red-600 hover:bg-red-100 hover:text-red-700 dark:bg-red-950/20 dark:text-red-400 transition"
                      >
                        <Trash2 className="w-3.5 h-3.5" />
                      </button>
                    </td>
                  </tr>
                ))}
              </tbody>
            </table>
          </div>
        </div>
      </div>
    </div>
  );
};
