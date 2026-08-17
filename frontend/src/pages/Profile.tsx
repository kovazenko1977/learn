import React, { useState, useEffect } from 'react';
import { useLanguage } from '../LanguageContext';
import { User, Mail, Lock, CheckCircle, Bell, Send } from 'lucide-react';

export const Profile: React.FC = () => {
  const { t } = useLanguage();
  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [success, setSuccess] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  // Notifications State (Section 1.5 Requirements: Email / Push / Telegram)
  const [notifEmail, setNotifEmail] = useState(true);
  const [notifPush, setNotifPush] = useState(true);
  const [notifTelegram, setNotifTelegram] = useState(false);
  const [telegramChatId, setTelegramChatId] = useState('');
  const [notifFrequency, setNotifFrequency] = useState('instant'); // instant, hourly, daily
  const [notifOnAssign, setNotifOnAssign] = useState(true);
  const [notifOnStatus, setNotifOnStatus] = useState(true);
  const [notifOnComment, setNotifOnComment] = useState(true);
  const [notifOnSla, setNotifOnSla] = useState(true);

  useEffect(() => {
    fetchProfile();
    loadNotificationSettings();
  }, []);

  const loadNotificationSettings = () => {
    try {
      setNotifEmail(localStorage.getItem('crm_notif_email') !== 'false');
      setNotifPush(localStorage.getItem('crm_notif_push') !== 'false');
      setNotifTelegram(localStorage.getItem('crm_notif_tg') === 'true');
      setTelegramChatId(localStorage.getItem('crm_tg_chat_id') || '');
      setNotifFrequency(localStorage.getItem('crm_notif_freq') || 'instant');
      setNotifOnAssign(localStorage.getItem('crm_notif_assign') !== 'false');
      setNotifOnStatus(localStorage.getItem('crm_notif_status') !== 'false');
      setNotifOnComment(localStorage.getItem('crm_notif_comment') !== 'false');
      setNotifOnSla(localStorage.getItem('crm_notif_sla') !== 'false');
    } catch (e) {
      console.error(e);
    }
  };

  const saveNotificationSettings = () => {
    localStorage.setItem('crm_notif_email', String(notifEmail));
    localStorage.setItem('crm_notif_push', String(notifPush));
    localStorage.setItem('crm_notif_tg', String(notifTelegram));
    localStorage.setItem('crm_tg_chat_id', telegramChatId);
    localStorage.setItem('crm_notif_freq', notifFrequency);
    localStorage.setItem('crm_notif_assign', String(notifOnAssign));
    localStorage.setItem('crm_notif_status', String(notifOnStatus));
    localStorage.setItem('crm_notif_comment', String(notifOnComment));
    localStorage.setItem('crm_notif_sla', String(notifOnSla));
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

      {/* Notifications Configuration Card (Section 1.5 requirement) */}
      <div className="mt-8 pt-6 border-t border-slate-100 dark:border-slate-800 space-y-4">
        <div className="flex items-center gap-2">
          <Bell className="w-5 h-5 text-primary-500" />
          <h4 className="font-bold text-slate-900 dark:text-slate-50 text-base">Настройка уведомлений (Email / Push / Telegram)</h4>
        </div>

        <div className="grid grid-cols-1 md:grid-cols-3 gap-3 text-xs">
          <label className="flex items-center gap-2 p-3 bg-slate-50 dark:bg-slate-950/40 rounded-xl border border-slate-200/50 dark:border-slate-800/50 cursor-pointer">
            <input
              type="checkbox"
              checked={notifEmail}
              onChange={(e) => { setNotifEmail(e.target.checked); saveNotificationSettings(); }}
              className="w-4 h-4 rounded text-primary-600"
            />
            <span className="font-bold text-slate-800 dark:text-slate-200">Email Уведомления</span>
          </label>

          <label className="flex items-center gap-2 p-3 bg-slate-50 dark:bg-slate-950/40 rounded-xl border border-slate-200/50 dark:border-slate-800/50 cursor-pointer">
            <input
              type="checkbox"
              checked={notifPush}
              onChange={(e) => { setNotifPush(e.target.checked); saveNotificationSettings(); }}
              className="w-4 h-4 rounded text-primary-600"
            />
            <span className="font-bold text-slate-800 dark:text-slate-200">Browser Push Уведомления</span>
          </label>

          <label className="flex items-center gap-2 p-3 bg-slate-50 dark:bg-slate-950/40 rounded-xl border border-slate-200/50 dark:border-slate-800/50 cursor-pointer">
            <input
              type="checkbox"
              checked={notifTelegram}
              onChange={(e) => { setNotifTelegram(e.target.checked); saveNotificationSettings(); }}
              className="w-4 h-4 rounded text-primary-600"
            />
            <span className="font-bold text-slate-800 dark:text-slate-200">Telegram Bot</span>
          </label>
        </div>

        {notifTelegram && (
          <div className="p-3 bg-primary-50/50 dark:bg-primary-950/20 rounded-xl border border-primary-200/30 text-xs space-y-1">
            <label className="block font-semibold text-primary-900 dark:text-primary-300">Telegram ID / Username</label>
            <input
              type="text"
              placeholder="@my_telegram_user или ID"
              value={telegramChatId}
              onChange={(e) => { setTelegramChatId(e.target.value); saveNotificationSettings(); }}
              className="w-full px-3 py-1.5 rounded-lg border bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100"
            />
          </div>
        )}

        <div className="grid grid-cols-1 md:grid-cols-2 gap-4 text-xs pt-2">
          <div>
            <label className="block font-bold text-slate-500 uppercase tracking-wider mb-2">Частота отправки</label>
            <select
              value={notifFrequency}
              onChange={(e) => { setNotifFrequency(e.target.value); saveNotificationSettings(); }}
              className="w-full px-3 py-2 rounded-xl border bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-100"
            >
              <option value="instant">Мгновенно при событии</option>
              <option value="hourly">Раз в час (Сводка)</option>
              <option value="daily">Раз в день (Дайджест)</option>
            </select>
          </div>

          <div>
            <label className="block font-bold text-slate-500 uppercase tracking-wider mb-2">Условия и события</label>
            <div className="space-y-1.5">
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={notifOnAssign}
                  onChange={(e) => { setNotifOnAssign(e.target.checked); saveNotificationSettings(); }}
                  className="w-3.5 h-3.5 rounded text-primary-600"
                />
                <span className="text-slate-700 dark:text-slate-300">Назначение новой заявки</span>
              </label>
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={notifOnStatus}
                  onChange={(e) => { setNotifOnStatus(e.target.checked); saveNotificationSettings(); }}
                  className="w-3.5 h-3.5 rounded text-primary-600"
                />
                <span className="text-slate-700 dark:text-slate-300">Изменение статуса заявки</span>
              </label>
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={notifOnComment}
                  onChange={(e) => { setNotifOnComment(e.target.checked); saveNotificationSettings(); }}
                  className="w-3.5 h-3.5 rounded text-primary-600"
                />
                <span className="text-slate-700 dark:text-slate-300">Новые комментарии</span>
              </label>
              <label className="flex items-center gap-2 cursor-pointer">
                <input
                  type="checkbox"
                  checked={notifOnSla}
                  onChange={(e) => { setNotifOnSla(e.target.checked); saveNotificationSettings(); }}
                  className="w-3.5 h-3.5 rounded text-primary-600"
                />
                <span className="text-slate-700 dark:text-slate-300">Предупреждения просрочки SLA</span>
              </label>
            </div>
          </div>
        </div>
      </div>
    </div>
  );
};
