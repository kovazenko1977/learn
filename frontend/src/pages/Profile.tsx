import React, { useState, useEffect } from 'react';
import { useLanguage } from '../LanguageContext';
import { User, Mail, Lock, CheckCircle, Bell } from 'lucide-react';

export const Profile: React.FC = () => {
  const { t } = useLanguage();
  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [success, setSuccess] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  // Notifications State
  const [notifEmailAssignment, setNotifEmailAssignment] = useState(() => localStorage.getItem('crm_notif_email_assignment') !== 'false');
  const [notifPushAssignment, setNotifPushAssignment] = useState(() => localStorage.getItem('crm_notif_push_assignment') !== 'false');
  const [notifTelegramAssignment, setNotifTelegramAssignment] = useState(() => localStorage.getItem('crm_notif_telegram_assignment') !== 'false');

  const [notifEmailStatus, setNotifEmailStatus] = useState(() => localStorage.getItem('crm_notif_email_status') !== 'false');
  const [notifPushStatus, setNotifPushStatus] = useState(() => localStorage.getItem('crm_notif_push_status') !== 'false');
  const [notifTelegramStatus, setNotifTelegramStatus] = useState(() => localStorage.getItem('crm_notif_telegram_status') !== 'false');

  const [notifEmailComment, setNotifEmailComment] = useState(() => localStorage.getItem('crm_notif_email_comment') !== 'false');
  const [notifPushComment, setNotifPushComment] = useState(() => localStorage.getItem('crm_notif_push_comment') !== 'false');
  const [notifTelegramComment, setNotifTelegramComment] = useState(() => localStorage.getItem('crm_notif_telegram_comment') !== 'false');

  const [notifEmailSla, setNotifEmailSla] = useState(() => localStorage.getItem('crm_notif_email_sla') !== 'false');
  const [notifPushSla, setNotifPushSla] = useState(() => localStorage.getItem('crm_notif_push_sla') !== 'false');
  const [notifTelegramSla, setNotifTelegramSla] = useState(() => localStorage.getItem('crm_notif_telegram_sla') !== 'false');

  const [notifFrequency, setNotifFrequency] = useState(() => localStorage.getItem('crm_notif_frequency') || 'instantly');

  useEffect(() => {
    fetchProfile();
  }, []);

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

      // Save notification settings to localStorage
      localStorage.setItem('crm_notif_email_assignment', String(notifEmailAssignment));
      localStorage.setItem('crm_notif_push_assignment', String(notifPushAssignment));
      localStorage.setItem('crm_notif_telegram_assignment', String(notifTelegramAssignment));

      localStorage.setItem('crm_notif_email_status', String(notifEmailStatus));
      localStorage.setItem('crm_notif_push_status', String(notifPushStatus));
      localStorage.setItem('crm_notif_telegram_status', String(notifTelegramStatus));

      localStorage.setItem('crm_notif_email_comment', String(notifEmailComment));
      localStorage.setItem('crm_notif_push_comment', String(notifPushComment));
      localStorage.setItem('crm_notif_telegram_comment', String(notifTelegramComment));

      localStorage.setItem('crm_notif_email_sla', String(notifEmailSla));
      localStorage.setItem('crm_notif_push_sla', String(notifPushSla));
      localStorage.setItem('crm_notif_telegram_sla', String(notifTelegramSla));

      localStorage.setItem('crm_notif_frequency', notifFrequency);

      setSuccess('Профиль и настройки уведомлений успешно обновлены!');
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
          <p className="text-xs text-slate-400 dark:text-slate-500 font-medium">Управление личными данными, паролем и уведомлениями в CRM</p>
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

      <form onSubmit={handleSubmit} className="space-y-6">
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
          <p className="text-xs text-slate-400 dark:text-slate-500 mb-2 font-medium">Оставьте пустым, если не хотите менять пароль</p>
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

        {/* --- NOTIFICATIONS SECTION --- */}
        <div className="border-t border-slate-100 dark:border-slate-800 pt-6">
          <div className="flex items-center gap-2 mb-4">
            <Bell className="w-5 h-5 text-primary-500" />
            <h4 className="text-xs font-bold text-slate-500 dark:text-slate-400 uppercase tracking-wider">
              {t('notificationSettings')}
            </h4>
          </div>

          <div className="space-y-4">
            <div className="overflow-x-auto">
              <table className="w-full text-left text-xs">
                <thead>
                  <tr className="border-b border-slate-100 dark:border-slate-800 text-slate-400 font-semibold">
                    <th className="pb-2">{t('notifEvent') || 'Событие'}</th>
                    <th className="pb-2 text-center">Email</th>
                    <th className="pb-2 text-center">Push</th>
                    <th className="pb-2 text-center">Telegram</th>
                  </tr>
                </thead>
                <tbody className="divide-y divide-slate-100 dark:divide-slate-800/60">
                  <tr className="text-slate-700 dark:text-slate-300">
                    <td className="py-3 font-medium">{t('notifAssignment')}</td>
                    <td className="py-3 text-center">
                      <input
                        type="checkbox"
                        checked={notifEmailAssignment}
                        onChange={(e) => setNotifEmailAssignment(e.target.checked)}
                        className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                    </td>
                    <td className="py-3 text-center">
                      <input
                        type="checkbox"
                        checked={notifPushAssignment}
                        onChange={(e) => setNotifPushAssignment(e.target.checked)}
                        className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                    </td>
                    <td className="py-3 text-center">
                      <input
                        type="checkbox"
                        checked={notifTelegramAssignment}
                        onChange={(e) => setNotifTelegramAssignment(e.target.checked)}
                        className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                    </td>
                  </tr>
                  <tr className="text-slate-700 dark:text-slate-300">
                    <td className="py-3 font-medium">{t('notifStatus')}</td>
                    <td className="py-3 text-center">
                      <input
                        type="checkbox"
                        checked={notifEmailStatus}
                        onChange={(e) => setNotifEmailStatus(e.target.checked)}
                        className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                    </td>
                    <td className="py-3 text-center">
                      <input
                        type="checkbox"
                        checked={notifPushStatus}
                        onChange={(e) => setNotifPushStatus(e.target.checked)}
                        className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                    </td>
                    <td className="py-3 text-center">
                      <input
                        type="checkbox"
                        checked={notifTelegramStatus}
                        onChange={(e) => setNotifTelegramStatus(e.target.checked)}
                        className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                    </td>
                  </tr>
                  <tr className="text-slate-700 dark:text-slate-300">
                    <td className="py-3 font-medium">{t('notifComment')}</td>
                    <td className="py-3 text-center">
                      <input
                        type="checkbox"
                        checked={notifEmailComment}
                        onChange={(e) => setNotifEmailComment(e.target.checked)}
                        className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                    </td>
                    <td className="py-3 text-center">
                      <input
                        type="checkbox"
                        checked={notifPushComment}
                        onChange={(e) => setNotifPushComment(e.target.checked)}
                        className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                    </td>
                    <td className="py-3 text-center">
                      <input
                        type="checkbox"
                        checked={notifTelegramComment}
                        onChange={(e) => setNotifTelegramComment(e.target.checked)}
                        className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                    </td>
                  </tr>
                  <tr className="text-slate-700 dark:text-slate-300">
                    <td className="py-3 font-medium">{t('notifSla')}</td>
                    <td className="py-3 text-center">
                      <input
                        type="checkbox"
                        checked={notifEmailSla}
                        onChange={(e) => setNotifEmailSla(e.target.checked)}
                        className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                    </td>
                    <td className="py-3 text-center">
                      <input
                        type="checkbox"
                        checked={notifPushSla}
                        onChange={(e) => setNotifPushSla(e.target.checked)}
                        className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                    </td>
                    <td className="py-3 text-center">
                      <input
                        type="checkbox"
                        checked={notifTelegramSla}
                        onChange={(e) => setNotifTelegramSla(e.target.checked)}
                        className="w-4 h-4 rounded border-slate-300 text-primary-600 focus:ring-primary-500"
                      />
                    </td>
                  </tr>
                </tbody>
              </table>
            </div>

            <div className="pt-2">
              <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
                {t('notifFrequency')}
              </label>
              <select
                value={notifFrequency}
                onChange={(e) => setNotifFrequency(e.target.value)}
                className="w-full px-4 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20"
              >
                <option value="instantly">{t('notifInstantly')}</option>
                <option value="daily">{t('notifDaily')}</option>
                <option value="weekly">{t('notifWeekly')}</option>
              </select>
            </div>
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
    </div>
  );
};
