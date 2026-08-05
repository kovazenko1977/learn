import React, { useState, useEffect } from 'react';
import { useLanguage } from '../LanguageContext';
import { User, Mail, Lock, CheckCircle } from 'lucide-react';

export const Profile: React.FC = () => {
  const { t } = useLanguage();
  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');

  // Notification preference states
  const [emailNotifs, setEmailNotifs] = useState(() => localStorage.getItem('crm_notif_email') === 'true');
  const [pushNotifs, setPushNotifs] = useState(() => localStorage.getItem('crm_notif_push') !== 'false'); // default true
  const [tgNotifs, setTgNotifs] = useState(() => localStorage.getItem('crm_notif_tg') === 'true');
  const [onAssign, setOnAssign] = useState(() => localStorage.getItem('crm_notif_on_assign') !== 'false'); // default true
  const [onStatusChange, setOnStatusChange] = useState(() => localStorage.getItem('crm_notif_on_status') !== 'false'); // default true
  const [onComment, setOnComment] = useState(() => localStorage.getItem('crm_notif_on_comment') !== 'false'); // default true
  const [onSlaWarning, setOnSlaWarning] = useState(() => localStorage.getItem('crm_notif_on_sla') !== 'false'); // default true

  const [success, setSuccess] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

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

      // Save notification settings in localStorage
      localStorage.setItem('crm_notif_email', String(emailNotifs));
      localStorage.setItem('crm_notif_push', String(pushNotifs));
      localStorage.setItem('crm_notif_tg', String(tgNotifs));
      localStorage.setItem('crm_notif_on_assign', String(onAssign));
      localStorage.setItem('crm_notif_on_status', String(onStatusChange));
      localStorage.setItem('crm_notif_on_comment', String(onComment));
      localStorage.setItem('crm_notif_on_sla', String(onSlaWarning));

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

        {/* NOTIFICATIONS SETTINGS SECTION */}
        <div className="pt-4 border-t border-slate-100 dark:border-slate-800 space-y-4">
          <h4 className="font-bold text-slate-900 dark:text-slate-100 text-sm">Настройка уведомлений</h4>
          <p className="text-xs text-slate-400 dark:text-slate-500">Настройте каналы доставки и условия для уведомлений (Email, Push, Telegram, SLA)</p>

          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            {/* Channels */}
            <div className="p-4 bg-slate-50 dark:bg-slate-950/20 rounded-xl border border-slate-200/50 dark:border-slate-800/40 space-y-3">
              <span className="block text-xs font-bold text-slate-400 uppercase tracking-wider">Каналы уведомлений</span>
              <label className="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                <input
                  type="checkbox"
                  checked={emailNotifs}
                  onChange={(e) => setEmailNotifs(e.target.checked)}
                  className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                />
                <span>Email уведомления ({email || 'не указан'})</span>
              </label>

              <label className="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                <input
                  type="checkbox"
                  checked={pushNotifs}
                  onChange={(e) => setPushNotifs(e.target.checked)}
                  className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                />
                <span>Push уведомления (в браузере)</span>
              </label>

              <label className="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                <input
                  type="checkbox"
                  checked={tgNotifs}
                  onChange={(e) => setTgNotifs(e.target.checked)}
                  className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                />
                <span>Telegram‑бот оповещения</span>
              </label>
            </div>

            {/* Triggers */}
            <div className="p-4 bg-slate-50 dark:bg-slate-950/20 rounded-xl border border-slate-200/50 dark:border-slate-800/40 space-y-3">
              <span className="block text-xs font-bold text-slate-400 uppercase tracking-wider">Условия отправки</span>
              <label className="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                <input
                  type="checkbox"
                  checked={onAssign}
                  onChange={(e) => setOnAssign(e.target.checked)}
                  className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                />
                <span>Назначение меня исполнителем</span>
              </label>

              <label className="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                <input
                  type="checkbox"
                  checked={onStatusChange}
                  onChange={(e) => setOnStatusChange(e.target.checked)}
                  className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                />
                <span>Изменение статуса моих заявок</span>
              </label>

              <label className="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                <input
                  type="checkbox"
                  checked={onComment}
                  onChange={(e) => setOnComment(e.target.checked)}
                  className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                />
                <span>Новый комментарий в заявке</span>
              </label>

              <label className="flex items-center gap-2 text-xs font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                <input
                  type="checkbox"
                  checked={onSlaWarning}
                  onChange={(e) => setOnSlaWarning(e.target.checked)}
                  className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500"
                />
                <span>Предупреждение о дедлайне (SLA)</span>
              </label>
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
