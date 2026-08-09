import React, { useState, useEffect } from 'react';
import { useLanguage } from '../LanguageContext';
import { User, Mail, CheckCircle, Bell, Smartphone, Send, Clock, MessageSquare, AlertTriangle, ShieldAlert } from 'lucide-react';

export const Profile: React.FC = () => {
  const { t } = useLanguage();
  const [fullName, setFullName] = useState('');
  const [email, setEmail] = useState('');
  const [password, setPassword] = useState('');
  const [success, setSuccess] = useState('');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  // Notification states
  const [notifTaskAssignmentEmail, setNotifTaskAssignmentEmail] = useState(false);
  const [notifTaskAssignmentPush, setNotifTaskAssignmentPush] = useState(false);
  const [notifTaskAssignmentTelegram, setNotifTaskAssignmentTelegram] = useState(false);

  const [notifStatusUpdatesEmail, setNotifStatusUpdatesEmail] = useState(false);
  const [notifStatusUpdatesPush, setNotifStatusUpdatesPush] = useState(false);
  const [notifStatusUpdatesTelegram, setNotifStatusUpdatesTelegram] = useState(false);

  const [notifNewCommentsEmail, setNotifNewCommentsEmail] = useState(false);
  const [notifNewCommentsPush, setNotifNewCommentsPush] = useState(false);
  const [notifNewCommentsTelegram, setNotifNewCommentsTelegram] = useState(false);

  const [notifSlaAlertsEmail, setNotifSlaAlertsEmail] = useState(false);
  const [notifSlaAlertsPush, setNotifSlaAlertsPush] = useState(false);
  const [notifSlaAlertsTelegram, setNotifSlaAlertsTelegram] = useState(false);

  const [notifFrequency, setNotifFrequency] = useState('instantly');

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
    setNotifTaskAssignmentEmail(localStorage.getItem('crm_notif_task_assignment_email') === 'true');
    setNotifTaskAssignmentPush(localStorage.getItem('crm_notif_task_assignment_push') === 'true');
    setNotifTaskAssignmentTelegram(localStorage.getItem('crm_notif_task_assignment_telegram') === 'true');

    setNotifStatusUpdatesEmail(localStorage.getItem('crm_notif_status_updates_email') === 'true');
    setNotifStatusUpdatesPush(localStorage.getItem('crm_notif_status_updates_push') === 'true');
    setNotifStatusUpdatesTelegram(localStorage.getItem('crm_notif_status_updates_telegram') === 'true');

    setNotifNewCommentsEmail(localStorage.getItem('crm_notif_new_comments_email') === 'true');
    setNotifNewCommentsPush(localStorage.getItem('crm_notif_new_comments_push') === 'true');
    setNotifNewCommentsTelegram(localStorage.getItem('crm_notif_new_comments_telegram') === 'true');

    setNotifSlaAlertsEmail(localStorage.getItem('crm_notif_sla_alerts_email') === 'true');
    setNotifSlaAlertsPush(localStorage.getItem('crm_notif_sla_alerts_push') === 'true');
    setNotifSlaAlertsTelegram(localStorage.getItem('crm_notif_sla_alerts_telegram') === 'true');

    setNotifFrequency(localStorage.getItem('crm_notif_frequency') || 'instantly');
  };

  const saveNotificationSettings = () => {
    localStorage.setItem('crm_notif_task_assignment_email', String(notifTaskAssignmentEmail));
    localStorage.setItem('crm_notif_task_assignment_push', String(notifTaskAssignmentPush));
    localStorage.setItem('crm_notif_task_assignment_telegram', String(notifTaskAssignmentTelegram));

    localStorage.setItem('crm_notif_status_updates_email', String(notifStatusUpdatesEmail));
    localStorage.setItem('crm_notif_status_updates_push', String(notifStatusUpdatesPush));
    localStorage.setItem('crm_notif_status_updates_telegram', String(notifStatusUpdatesTelegram));

    localStorage.setItem('crm_notif_new_comments_email', String(notifNewCommentsEmail));
    localStorage.setItem('crm_notif_new_comments_push', String(notifNewCommentsPush));
    localStorage.setItem('crm_notif_new_comments_telegram', String(notifNewCommentsTelegram));

    localStorage.setItem('crm_notif_sla_alerts_email', String(notifSlaAlertsEmail));
    localStorage.setItem('crm_notif_sla_alerts_push', String(notifSlaAlertsPush));
    localStorage.setItem('crm_notif_sla_alerts_telegram', String(notifSlaAlertsTelegram));

    localStorage.setItem('crm_notif_frequency', notifFrequency);
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

      // Save the notification settings as well
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
    <div className="max-w-3xl mx-auto bg-white dark:bg-slate-900 rounded-2xl p-6 border border-slate-200/50 dark:border-slate-800/50 shadow-sm space-y-6">
      <div className="flex items-center gap-3 border-b border-slate-100 dark:border-slate-800 pb-4">
        <div className="w-12 h-12 bg-primary-100 dark:bg-primary-950/40 text-primary-600 dark:text-primary-400 rounded-xl flex items-center justify-center">
          <User className="w-6 h-6" />
        </div>
        <div>
          <h3 className="font-bold text-slate-900 dark:text-slate-50 text-lg">Настройки профиля</h3>
          <p className="text-xs text-slate-400 dark:text-slate-500">Управление личными данными, паролем и уведомлениями в CRM</p>
        </div>
      </div>

      {success && (
        <div className="p-4 bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 rounded-xl text-sm font-medium border border-green-200/30 flex items-center gap-2 animate-in fade-in">
          <CheckCircle className="w-5 h-5 text-green-500" />
          <span>{success}</span>
        </div>
      )}

      {error && (
        <div className="p-4 bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 rounded-xl text-sm font-medium border border-red-200/30 animate-in fade-in">
          {error}
        </div>
      )}

      <form onSubmit={handleSubmit} className="space-y-6">
        {/* Account Settings */}
        <div className="space-y-4">
          <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Основная информация</h4>
          <div className="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
              <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2">
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
              <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2">
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
            <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-1">
              Новый пароль
            </label>
            <p className="text-[11px] text-slate-400 dark:text-slate-500 mb-2">Оставьте пустым, если не хотите менять пароль</p>
            <input
              type="password"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              className="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
              placeholder="••••••••"
            />
          </div>
        </div>

        {/* Notification Settings */}
        <div className="space-y-4 pt-4 border-t border-slate-100 dark:border-slate-800">
          <div className="flex items-center gap-2">
            <Bell className="w-5 h-5 text-primary-500" />
            <h4 className="text-xs font-bold text-slate-900 dark:text-slate-50 uppercase tracking-wider">Настройка уведомлений</h4>
          </div>

          <div className="overflow-x-auto rounded-xl border border-slate-100 dark:border-slate-800">
            <table className="w-full text-left text-xs">
              <thead>
                <tr className="bg-slate-50 dark:bg-slate-950/40 border-b border-slate-100 dark:border-slate-800 text-slate-400 font-bold">
                  <th className="p-3">Событие для отправки</th>
                  <th className="p-3 text-center w-24">
                    <div className="flex flex-col items-center gap-1">
                      <Mail className="w-4 h-4 text-slate-500" />
                      <span>Email</span>
                    </div>
                  </th>
                  <th className="p-3 text-center w-24">
                    <div className="flex flex-col items-center gap-1">
                      <Smartphone className="w-4 h-4 text-slate-500" />
                      <span>Push</span>
                    </div>
                  </th>
                  <th className="p-3 text-center w-24">
                    <div className="flex flex-col items-center gap-1">
                      <Send className="w-4 h-4 text-slate-500" />
                      <span>Telegram</span>
                    </div>
                  </th>
                </tr>
              </thead>
              <tbody className="divide-y divide-slate-100 dark:divide-slate-800/60 text-slate-700 dark:text-slate-300">
                {/* Task Assignment */}
                <tr>
                  <td className="p-3">
                    <div className="flex items-center gap-2">
                      <Clock className="w-4 h-4 text-primary-500" />
                      <div>
                        <p className="font-semibold text-slate-900 dark:text-slate-100">Назначение заявки</p>
                        <p className="text-[10px] text-slate-400">Когда вам назначают новую задачу</p>
                      </div>
                    </div>
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifTaskAssignmentEmail}
                      onChange={(e) => setNotifTaskAssignmentEmail(e.target.checked)}
                      className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500 border-slate-300"
                    />
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifTaskAssignmentPush}
                      onChange={(e) => setNotifTaskAssignmentPush(e.target.checked)}
                      className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500 border-slate-300"
                    />
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifTaskAssignmentTelegram}
                      onChange={(e) => setNotifTaskAssignmentTelegram(e.target.checked)}
                      className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500 border-slate-300"
                    />
                  </td>
                </tr>

                {/* Status Updates */}
                <tr>
                  <td className="p-3">
                    <div className="flex items-center gap-2">
                      <MessageSquare className="w-4 h-4 text-purple-500" />
                      <div>
                        <p className="font-semibold text-slate-900 dark:text-slate-100">Обновление статуса</p>
                        <p className="text-[10px] text-slate-400">Когда статус заявки изменяется</p>
                      </div>
                    </div>
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifStatusUpdatesEmail}
                      onChange={(e) => setNotifStatusUpdatesEmail(e.target.checked)}
                      className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500 border-slate-300"
                    />
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifStatusUpdatesPush}
                      onChange={(e) => setNotifStatusUpdatesPush(e.target.checked)}
                      className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500 border-slate-300"
                    />
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifStatusUpdatesTelegram}
                      onChange={(e) => setNotifStatusUpdatesTelegram(e.target.checked)}
                      className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500 border-slate-300"
                    />
                  </td>
                </tr>

                {/* New Comments */}
                <tr>
                  <td className="p-3">
                    <div className="flex items-center gap-2">
                      <Bell className="w-4 h-4 text-amber-500" />
                      <div>
                        <p className="font-semibold text-slate-900 dark:text-slate-100">Новые комментарии</p>
                        <p className="text-[10px] text-slate-400">При добавлении нового комментария в заявку</p>
                      </div>
                    </div>
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifNewCommentsEmail}
                      onChange={(e) => setNotifNewCommentsEmail(e.target.checked)}
                      className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500 border-slate-300"
                    />
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifNewCommentsPush}
                      onChange={(e) => setNotifNewCommentsPush(e.target.checked)}
                      className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500 border-slate-300"
                    />
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifNewCommentsTelegram}
                      onChange={(e) => setNotifNewCommentsTelegram(e.target.checked)}
                      className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500 border-slate-300"
                    />
                  </td>
                </tr>

                {/* SLA Alerts */}
                <tr>
                  <td className="p-3">
                    <div className="flex items-center gap-2">
                      <AlertTriangle className="w-4 h-4 text-red-500" />
                      <div>
                        <p className="font-semibold text-slate-900 dark:text-slate-100">SLA предупреждения</p>
                        <p className="text-[10px] text-slate-400">Когда срок выполнения заявки подходит к концу</p>
                      </div>
                    </div>
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifSlaAlertsEmail}
                      onChange={(e) => setNotifSlaAlertsEmail(e.target.checked)}
                      className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500 border-slate-300"
                    />
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifSlaAlertsPush}
                      onChange={(e) => setNotifSlaAlertsPush(e.target.checked)}
                      className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500 border-slate-300"
                    />
                  </td>
                  <td className="p-3 text-center">
                    <input
                      type="checkbox"
                      checked={notifSlaAlertsTelegram}
                      onChange={(e) => setNotifSlaAlertsTelegram(e.target.checked)}
                      className="w-4 h-4 rounded text-primary-600 focus:ring-primary-500 border-slate-300"
                    />
                  </td>
                </tr>
              </tbody>
            </table>
          </div>

          {/* Notification Frequency */}
          <div>
            <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 mb-2">
              Частота отправки сводок и уведомлений
            </label>
            <select
              value={notifFrequency}
              onChange={(e) => setNotifFrequency(e.target.value)}
              className="px-3 py-2 text-xs rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-800 dark:text-slate-200 focus:outline-none"
            >
              <option value="instantly">Мгновенно (Instantly)</option>
              <option value="hourly">Раз в час (Hourly)</option>
              <option value="daily">Раз в день (Daily)</option>
            </select>
          </div>
        </div>

        <div className="pt-4 flex justify-end border-t border-slate-100 dark:border-slate-800">
          <button
            type="submit"
            disabled={loading}
            className="px-6 py-2.5 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white rounded-xl font-bold shadow-lg shadow-primary-500/20 transition"
          >
            {loading ? '...' : t('save')}
          </button>
        </div>
      </form>
    </div>
  );
};
