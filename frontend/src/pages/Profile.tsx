import React, { useState, useEffect } from 'react';
import { useLanguage } from '../LanguageContext';
import { User, Mail, Lock, CheckCircle, Bell, Shield, MessageSquare, Clock } from 'lucide-react';

export const Profile: React.FC = () => {
  const { t } = useLanguage();
  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [success, setSuccess] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  // Notification States
  const [notifEmail, setNotifEmail] = useState(true);
  const [notifPush, setNotifPush] = useState(true);
  const [notifTelegram, setNotifTelegram] = useState(false);

  const [triggerAssign, setTriggerAssign] = useState(true);
  const [triggerStatus, setTriggerStatus] = useState(true);
  const [triggerComment, setTriggerComment] = useState(true);
  const [triggerSla, setTriggerSla] = useState(true);

  const [frequency, setFrequency] = useState('instantly');

  useEffect(() => {
    fetchProfile();
    loadNotificationSettings();
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

  const loadNotificationSettings = () => {
    setNotifEmail(localStorage.getItem('crm_notif_email') !== 'false');
    setNotifPush(localStorage.getItem('crm_notif_push') !== 'false');
    setNotifTelegram(localStorage.getItem('crm_notif_telegram') === 'true');

    setTriggerAssign(localStorage.getItem('crm_notif_on_assign') !== 'false');
    setTriggerStatus(localStorage.getItem('crm_notif_on_status') !== 'false');
    setTriggerComment(localStorage.getItem('crm_notif_on_comment') !== 'false');
    setTriggerSla(localStorage.getItem('crm_notif_on_sla') !== 'false');

    setFrequency(localStorage.getItem('crm_notif_frequency') || 'instantly');
  };

  const saveNotificationSettings = () => {
    localStorage.setItem('crm_notif_email', String(notifEmail));
    localStorage.setItem('crm_notif_push', String(notifPush));
    localStorage.setItem('crm_notif_telegram', String(notifTelegram));

    localStorage.setItem('crm_notif_on_assign', String(triggerAssign));
    localStorage.setItem('crm_notif_on_status', String(triggerStatus));
    localStorage.setItem('crm_notif_on_comment', String(triggerComment));
    localStorage.setItem('crm_notif_on_sla', String(triggerSla));

    localStorage.setItem('crm_notif_frequency', frequency);
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

      // Save notification settings as well
      saveNotificationSettings();

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
    <div className="max-w-2xl mx-auto space-y-6">
      <div className="bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/50 dark:border-slate-800/50 shadow-sm">
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

        <form onSubmit={handleSubmit} className="space-y-6">
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
                {t('fullName')}
              </label>
              <input
                type="text"
                required
                value={fullName}
                onChange={(e) => setFullName(e.target.value)}
                className="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
                placeholder="ФИО сотрудника"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-2">
                {t('email')}
              </label>
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
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
              placeholder="••••••••"
            />
          </div>

          {/* 1.5. Notification Settings Subsection */}
          <div className="border-t border-slate-100 dark:border-slate-800 pt-6 space-y-4">
            <h4 className="font-bold text-slate-900 dark:text-slate-50 text-sm flex items-center gap-2">
              <Bell className="w-5 h-5 text-primary-500" />
              <span>Настройка уведомлений (Channels & Triggers)</span>
            </h4>

            {/* Channels */}
            <div className="space-y-2">
              <p className="text-xs font-bold text-slate-400 uppercase tracking-wide">Каналы получения</p>
              <div className="grid grid-cols-1 sm:grid-cols-3 gap-3">
                <label className="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-950/40 border dark:border-slate-800 rounded-xl cursor-pointer hover:bg-slate-100 transition">
                  <input
                    type="checkbox"
                    checked={notifEmail}
                    onChange={(e) => setNotifEmail(e.target.checked)}
                    className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                  />
                  <div>
                    <span className="block text-xs font-bold text-slate-900 dark:text-slate-50">Email-письма</span>
                    <span className="text-[10px] text-slate-400">На указанный email</span>
                  </div>
                </label>

                <label className="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-950/40 border dark:border-slate-800 rounded-xl cursor-pointer hover:bg-slate-100 transition">
                  <input
                    type="checkbox"
                    checked={notifPush}
                    onChange={(e) => setNotifPush(e.target.checked)}
                    className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                  />
                  <div>
                    <span className="block text-xs font-bold text-slate-900 dark:text-slate-50">Push-уведомления</span>
                    <span className="text-[10px] text-slate-400">В браузере</span>
                  </div>
                </label>

                <label className="flex items-center gap-3 p-3 bg-slate-50 dark:bg-slate-950/40 border dark:border-slate-800 rounded-xl cursor-pointer hover:bg-slate-100 transition">
                  <input
                    type="checkbox"
                    checked={notifTelegram}
                    onChange={(e) => setNotifTelegram(e.target.checked)}
                    className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                  />
                  <div>
                    <span className="block text-xs font-bold text-slate-900 dark:text-slate-50">Telegram-бот</span>
                    <span className="text-[10px] text-slate-400">Через мессенджер</span>
                  </div>
                </label>
              </div>
            </div>

            {/* Triggers */}
            <div className="space-y-2">
              <p className="text-xs font-bold text-slate-400 uppercase tracking-wide">Условия отправки уведомлений</p>
              <div className="grid grid-cols-1 sm:grid-cols-2 gap-3 text-xs">
                <label className="flex items-center gap-3 p-3 border dark:border-slate-800 rounded-xl cursor-pointer">
                  <input
                    type="checkbox"
                    checked={triggerAssign}
                    onChange={(e) => setTriggerAssign(e.target.checked)}
                    className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                  />
                  <span className="font-semibold text-slate-700 dark:text-slate-300">При назначении новой задачи</span>
                </label>

                <label className="flex items-center gap-3 p-3 border dark:border-slate-800 rounded-xl cursor-pointer">
                  <input
                    type="checkbox"
                    checked={triggerStatus}
                    onChange={(e) => setTriggerStatus(e.target.checked)}
                    className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                  />
                  <span className="font-semibold text-slate-700 dark:text-slate-300">При изменении статуса заявки</span>
                </label>

                <label className="flex items-center gap-3 p-3 border dark:border-slate-800 rounded-xl cursor-pointer">
                  <input
                    type="checkbox"
                    checked={triggerComment}
                    onChange={(e) => setTriggerComment(e.target.checked)}
                    className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                  />
                  <span className="font-semibold text-slate-700 dark:text-slate-300">При добавлении нового комментария</span>
                </label>

                <label className="flex items-center gap-3 p-3 border dark:border-slate-800 rounded-xl cursor-pointer">
                  <input
                    type="checkbox"
                    checked={triggerSla}
                    onChange={(e) => setTriggerSla(e.target.checked)}
                    className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                  />
                  <span className="font-semibold text-slate-700 dark:text-slate-300">При предупреждениях и нарушениях SLA</span>
                </label>
              </div>
            </div>

            {/* Frequency */}
            <div>
              <label className="block text-xs font-bold text-slate-400 uppercase tracking-wide mb-2">
                Частота отправки сводок
              </label>
              <select
                value={frequency}
                onChange={(e) => setFrequency(e.target.value)}
                className="w-full sm:w-64 px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-800 dark:text-slate-100"
              >
                <option value="instantly">Мгновенно (Instantly)</option>
                <option value="hourly">Раз в час (Hourly Summary)</option>
                <option value="daily">Раз в день (Daily Summary)</option>
              </select>
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
    </div>
  );
};
