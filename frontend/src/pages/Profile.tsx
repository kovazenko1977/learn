import React, { useState, useEffect } from 'react';
import { useLanguage } from '../LanguageContext';
import { User, Mail, Lock, CheckCircle, Bell, Smartphone, Send } from 'lucide-react';

export const Profile: React.FC = () => {
  const { t } = useLanguage();
  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [success, setSuccess] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  // Notification configuration state loaded from localStorage
  const [notifSettings, setNotifSettings] = useState(() => {
    return {
      email_assignment: localStorage.getItem('crm_notif_email_assignment') !== 'false', // default to true
      push_assignment: localStorage.getItem('crm_notif_push_assignment') !== 'false',
      telegram_assignment: localStorage.getItem('crm_notif_telegram_assignment') === 'true', // default to false
      email_status: localStorage.getItem('crm_notif_email_status') !== 'false',
      push_status: localStorage.getItem('crm_notif_push_status') !== 'false',
      telegram_status: localStorage.getItem('crm_notif_telegram_status') === 'true',
      email_comments: localStorage.getItem('crm_notif_email_comments') !== 'false',
      push_comments: localStorage.getItem('crm_notif_push_comments') !== 'false',
      telegram_comments: localStorage.getItem('crm_notif_telegram_comments') === 'true',
      email_sla: localStorage.getItem('crm_notif_email_sla') !== 'false',
      push_sla: localStorage.getItem('crm_notif_push_sla') !== 'false',
      telegram_sla: localStorage.getItem('crm_notif_telegram_sla') === 'true',
    };
  });

  useEffect(() => {
    fetchProfile();
  }, []);

  const handleNotifToggle = (key: keyof typeof notifSettings) => {
    const newValue = !notifSettings[key];
    setNotifSettings(prev => ({ ...prev, [key]: newValue }));
    localStorage.setItem(`crm_notif_${key}`, String(newValue));
  };

  const fetchProfile = async () => {
    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/auth/profile', {
        headers: {
          'Authorization': `Bearer ${token}`
        }
      });
      if (res.ok) {
        const data = await res.json();
        setFullName(data.fullName || '');
        setEmail(data.email || '');
      }
    } catch (e) {
      console.error('Failed to load profile:', e);
    }
  };

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setSuccess('');
    setError('');
    setLoading(true);

    try {
      const token = localStorage.getItem('crm_token');
      const res = await fetch('/api/auth/profile', {
        method: 'PUT',
        headers: {
          'Content-Type': 'application/json',
          'Authorization': `Bearer ${token}`
        },
        body: JSON.stringify({
          fullName,
          email,
          ...(password ? { password } : {})
        })
      });

      const data = await res.json();
      if (!res.ok) {
        throw new Error(data.error || 'Failed to update profile');
      }

      setSuccess('Профиль успешно обновлен!');
      setPassword('');

      // Update saved user info in localStorage
      const savedUser = JSON.parse(localStorage.getItem('crm_user') || '{}');
      savedUser.fullName = data.fullName;
      savedUser.email = data.email;
      localStorage.setItem('crm_user', JSON.stringify(savedUser));

      // We can trigger profile reload or header sync
      window.dispatchEvent(new Event('storage'));
    } catch (err: any) {
      setError(err.message || 'Error updating profile');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="max-w-2xl mx-auto bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/50 dark:border-slate-800/50 shadow-sm">
      <div className="flex items-center gap-3 mb-6 border-b border-slate-100 dark:border-slate-800 pb-4">
        <div className="w-12 h-12 bg-primary-100 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 rounded-xl flex items-center justify-center">
          <User className="w-6 h-6" />
        </div>
        <div>
          <h3 className="font-bold text-slate-900 dark:text-slate-50 text-lg">Настройки профиля</h3>
          <p className="text-xs text-slate-400 dark:text-slate-500">Управление личными данными и паролем в CRM</p>
        </div>
      </div>

      {success && (
        <div className="mb-6 p-4 bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 rounded-xl text-sm font-medium border border-green-200/30 flex items-center gap-2">
          <CheckCircle className="w-5 h-5 text-green-500" />
          <span>{success}</span>
        </div>
      )}

      {error && (
        <div className="mb-6 p-4 bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 rounded-xl text-sm font-medium border border-red-200/30">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-5">
        <div>
          <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
            {t('fullName')}
          </label>
          <div className="relative">
            <input
              type="text"
              required
              value={fullName}
              onChange={(e) => setFullName(e.target.value)}
              className="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
              placeholder="ФИО сотрудника"
            />
          </div>
        </div>

        <div>
          <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
            {t('email')}
          </label>
          <div className="relative">
            <input
              type="email"
              required
              value={email}
              onChange={(e) => setEmail(e.target.value)}
              className="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
              placeholder="email@crm.local"
            />
          </div>
        </div>

        <div>
          <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
            Новый пароль
          </label>
          <p className="text-xs text-slate-400 dark:text-slate-500 mb-2">Оставьте пустым, если не хотите менять пароль</p>
          <div className="relative">
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
              placeholder="••••••••"
            />
          </div>
        </div>

        <div className="pt-4 flex justify-end">
          <button
            type="submit"
            disabled={loading}
            className="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white rounded-xl font-semibold shadow-lg shadow-primary-500/20 transition"
          >
            {loading ? '...' : t('save')}
          </button>
        </div>
      </form>

      {/* Interactive Notifications Preferences Section */}
      <div className="mt-8 pt-8 border-t border-slate-100 dark:border-slate-800 space-y-6">
        <div className="flex items-center gap-3">
          <div className="w-10 h-10 bg-primary-50 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 rounded-xl flex items-center justify-center">
            <Bell className="w-5 h-5" />
          </div>
          <div>
            <h4 className="font-bold text-slate-900 dark:text-slate-50 text-md">Настройка уведомлений</h4>
            <p className="text-xs text-slate-400 dark:text-slate-500">Управляйте триггерами и каналами доставки уведомлений в реальном времени</p>
          </div>
        </div>

        <div className="overflow-x-auto border border-slate-100 dark:border-slate-800/80 rounded-xl">
          <table className="w-full text-left text-xs">
            <thead>
              <tr className="border-b bg-slate-50/50 dark:bg-slate-900/40 text-slate-400 dark:text-slate-500 font-medium">
                <th className="p-3">Событие (Event)</th>
                <th className="p-3 text-center">
                  <div className="flex items-center justify-center gap-1">
                    <Mail className="w-3.5 h-3.5" />
                    <span>Email</span>
                  </div>
                </th>
                <th className="p-3 text-center">
                  <div className="flex items-center justify-center gap-1">
                    <Smartphone className="w-3.5 h-3.5" />
                    <span>Push</span>
                  </div>
                </th>
                <th className="p-3 text-center">
                  <div className="flex items-center justify-center gap-1">
                    <Send className="w-3.5 h-3.5" />
                    <span>Telegram</span>
                  </div>
                </th>
              </tr>
            </thead>
            <tbody className="divide-y divide-slate-100 dark:divide-slate-800/50 text-slate-700 dark:text-slate-300">
              {[
                { label: 'Назначение задачи', sub: 'Когда вам назначают новую заявку', keyPart: 'assignment' },
                { label: 'Изменение статуса', sub: 'Когда статус вашей заявки меняется', keyPart: 'status' },
                { label: 'Новые комментарии', sub: 'Когда добавляют комментарий к заявке', keyPart: 'comments' },
                { label: 'SLA-предупреждения', sub: 'При приближении дедлайна SLA', keyPart: 'sla' }
              ].map(row => (
                <tr key={row.keyPart} className="hover:bg-slate-50/20 dark:hover:bg-slate-800/10">
                  <td className="p-3">
                    <p className="font-semibold text-slate-900 dark:text-slate-100">{row.label}</p>
                    <p className="text-[10px] text-slate-400 mt-0.5">{row.sub}</p>
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifSettings[`email_${row.keyPart}` as keyof typeof notifSettings]}
                      onChange={() => handleNotifToggle(`email_${row.keyPart}` as keyof typeof notifSettings)}
                      className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                    />
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifSettings[`push_${row.keyPart}` as keyof typeof notifSettings]}
                      onChange={() => handleNotifToggle(`push_${row.keyPart}` as keyof typeof notifSettings)}
                      className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                    />
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifSettings[`telegram_${row.keyPart}` as keyof typeof notifSettings]}
                      onChange={() => handleNotifToggle(`telegram_${row.keyPart}` as keyof typeof notifSettings)}
                      className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500 cursor-pointer"
                    />
                  </td>
                </tr>
              ))}
            </tbody>
          </table>
        </div>
      </div>
    </div>
  );
};
