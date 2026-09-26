import React, { useState, useEffect } from 'react';
import { useLanguage } from '../LanguageContext';
import { useTheme } from '../ThemeContext';
import { Shield, Key, Eye, EyeOff, Globe, Sun, Moon, CheckCircle } from 'lucide-react';

interface LoginProps {
  onLoginSuccess: (token: string, user: any) => void;
}

export const Login: React.FC<LoginProps> = ({ onLoginSuccess }) => {
  const { t, language, setLanguage } = useLanguage();
  const { theme, toggleTheme } = useTheme();

  const [username, setUsername] = useState('');
  const [password, setPassword] = useState('');
  const [showPassword, setShowPassword] = useState(false);
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  // Recovery States
  const [isRecovering, setIsRecovering] = useState(false);
  const [recoverEmail, setRecoverEmail] = useState('');
  const [recoveryMsg, setRecoveryMsg] = useState('');

  // Password Reset States (token-based)
  const [isResetting, setIsResetting] = useState(false);
  const [resetToken, setResetToken] = useState('');
  const [newPassword, setNewPassword] = useState('');
  const [resetSuccess, setResetSuccess] = useState('');

  // Auto-detect token in URL on load
  useEffect(() => {
    const params = new URLSearchParams(window.location.search);
    const tokenFromUrl = params.get('token');
    if (tokenFromUrl) {
      setResetToken(tokenFromUrl);
      setIsResetting(true);
      setIsRecovering(false);
    }
  }, []);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setLoading(true);

    try {
      const res = await fetch('/api/auth/login', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, password })
      });

      const data = await res.json();
      if (!res.ok) {
        throw new Error(data.error || 'Login failed');
      }

      onLoginSuccess(data.token, data.user);
    } catch (err: any) {
      setError(err.message || 'Ошибка подключения к серверу');
    } finally {
      setLoading(false);
    }
  };

  const handleRecovery = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setRecoveryMsg('');
    setLoading(true);

    try {
      const res = await fetch('/api/auth/recover-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ username, email: recoverEmail })
      });

      const data = await res.json();
      if (!res.ok) {
        throw new Error(data.error || 'Recovery failed');
      }

      // Generate the reset link locally for simulation / demo
      const simulatedToken = data.recoveryLink ? data.recoveryLink.split('token=')[1] : 'sample-token';
      const simulatedLink = `${window.location.origin}/?token=${simulatedToken}`;

      setRecoveryMsg(`${t('recoverySuccess')} Ссылка для сброса: ${simulatedLink}`);
    } catch (err: any) {
      setError(err.message || 'Пользователь не найден');
    } finally {
      setLoading(false);
    }
  };

  const handleResetPassword = async (e: React.FormEvent) => {
    e.preventDefault();
    setError('');
    setResetSuccess('');
    setLoading(true);

    try {
      const res = await fetch('/api/auth/reset-password', {
        method: 'POST',
        headers: { 'Content-Type': 'application/json' },
        body: JSON.stringify({ token: resetToken, newPassword })
      });

      const data = await res.json();
      if (!res.ok) {
        throw new Error(data.error || 'Password reset failed');
      }

      setResetSuccess('Пароль успешно изменен! Вы будете перенаправлены на форму входа.');
      setResetToken('');
      setNewPassword('');

      // Auto clear and redirect to login after 3 seconds
      setTimeout(() => {
        setIsResetting(false);
        setIsRecovering(false);
        setResetSuccess('');
        setError('');
        // Clean URL parameter
        window.history.replaceState({}, document.title, window.location.pathname);
      }, 3000);
    } catch (err: any) {
      setError(err.message || 'Ошибка сброса пароля. Недействительный или истекший токен.');
    } finally {
      setLoading(false);
    }
  };

  const getFormTitle = () => {
    if (isResetting) return 'Сброс пароля';
    if (isRecovering) return t('recoveryTitle');
    return t('loginTitle');
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-slate-100 dark:bg-slate-950 p-4 transition-colors duration-200">
      <div className="absolute top-4 right-4 flex gap-2">
        {/* Language Switcher */}
        <button
          onClick={() => setLanguage(language === 'RU' ? 'EN' : 'RU')}
          className="flex items-center gap-1 px-3 py-1.5 rounded-lg bg-white dark:bg-slate-800 shadow text-sm font-semibold hover:bg-slate-50 dark:hover:bg-slate-700 transition"
        >
          <Globe className="w-4 h-4 text-slate-500" />
          <span>{language === 'RU' ? 'EN' : 'RU'}</span>
        </button>

        {/* Theme Toggle */}
        <button
          onClick={toggleTheme}
          className="p-1.5 rounded-lg bg-white dark:bg-slate-800 shadow hover:bg-slate-50 dark:hover:bg-slate-700 transition"
        >
          {theme === 'light' ? (
            <Moon className="w-5 h-5 text-slate-600" />
          ) : (
            <Sun className="w-5 h-5 text-amber-400" />
          )}
        </button>
      </div>

      <div className="w-full max-w-md bg-white dark:bg-slate-900 rounded-2xl shadow-xl p-8 border border-slate-200/50 dark:border-slate-800/50">
        <div className="flex flex-col items-center mb-8">
          <div className="w-16 h-16 bg-primary-100 dark:bg-primary-950/50 rounded-2xl flex items-center justify-center mb-3">
            <Shield className="w-8 h-8 text-primary-600 dark:text-primary-400" />
          </div>
          <h2 className="text-2xl font-bold tracking-tight text-slate-900 dark:text-slate-50">
            {getFormTitle()}
          </h2>
          <p className="text-sm text-slate-500 dark:text-slate-400 mt-1">
            {isResetting ? 'Введите токен и новый пароль' : isRecovering ? 'Введите имя и email' : 'Система управления заявками'}
          </p>
        </div>

        {error && (
          <div className="mb-4 p-3 bg-red-50 dark:bg-red-950/30 text-red-600 dark:text-red-400 rounded-lg text-sm font-medium border border-red-200/30">
            {error}
          </div>
        )}

        {recoveryMsg && (
          <div className="mb-4 p-3 bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 rounded-lg text-xs font-medium border border-green-200/30 break-all">
            {recoveryMsg}
          </div>
        )}

        {resetSuccess && (
          <div className="mb-4 p-3 bg-green-50 dark:bg-green-950/30 text-green-600 dark:text-green-400 rounded-lg text-sm font-medium border border-green-200/30 flex items-center gap-2">
            <CheckCircle className="w-4 h-4 text-green-500" />
            <span>{resetSuccess}</span>
          </div>
        )}

        {/* Render depending on mode */}
        {!isRecovering && !isResetting ? (
          /* Login Form */
          <form onSubmit={handleSubmit} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                {t('username')}
              </label>
              <div className="relative">
                <input
                  type="text"
                  required
                  value={username}
                  onChange={(e) => setUsername(e.target.value)}
                  className="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
                  placeholder="admin"
                />
              </div>
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                {t('password')}
              </label>
              <div className="relative">
                <input
                  type={showPassword ? 'text' : 'password'}
                  required
                  value={password}
                  onChange={(e) => setPassword(e.target.value)}
                  className="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
                  placeholder="••••••••"
                />
                <button
                  type="button"
                  onClick={() => setShowPassword(!showPassword)}
                  className="absolute right-3 top-1/2 -translate-y-1/2 text-slate-400 hover:text-slate-600 dark:hover:text-slate-300 transition"
                >
                  {showPassword ? <EyeOff className="w-5 h-5" /> : <Eye className="w-5 h-5" />}
                </button>
              </div>
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white rounded-xl font-semibold shadow-lg shadow-primary-500/25 transition focus:ring-2 focus:ring-primary-500/20"
            >
              {loading ? '...' : t('loginBtn')}
            </button>

            <div className="flex justify-between items-center mt-4">
              <button
                type="button"
                onClick={() => { setIsRecovering(true); setError(''); setRecoveryMsg(''); }}
                className="text-xs font-medium text-primary-600 hover:text-primary-700 dark:text-primary-400 dark:hover:text-primary-300 transition"
              >
                {t('recoverPassword')}?
              </button>

              <button
                type="button"
                onClick={() => { setIsResetting(true); setError(''); setResetSuccess(''); }}
                className="text-xs font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 transition"
              >
                Ввести токен сброса
              </button>
            </div>
          </form>
        ) : isRecovering ? (
          /* Forgot Password / Recover Form */
          <form onSubmit={handleRecovery} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                {t('username')}
              </label>
              <input
                type="text"
                required
                value={username}
                onChange={(e) => setUsername(e.target.value)}
                className="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
                placeholder="admin"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                Email
              </label>
              <input
                type="email"
                required
                value={recoverEmail}
                onChange={(e) => setRecoverEmail(e.target.value)}
                className="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
                placeholder="admin@crm.local"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white rounded-xl font-semibold shadow-lg shadow-primary-500/25 transition focus:ring-2 focus:ring-primary-500/20"
            >
              {loading ? '...' : t('recoverPassword')}
            </button>

            <div className="text-center mt-4 flex justify-between text-xs">
              <button
                type="button"
                onClick={() => { setIsRecovering(false); setIsResetting(false); setError(''); setRecoveryMsg(''); }}
                className="font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 transition"
              >
                Вернуться к входу
              </button>

              <button
                type="button"
                onClick={() => { setIsResetting(true); setIsRecovering(false); setError(''); setResetSuccess(''); }}
                className="font-medium text-primary-600 hover:text-primary-700 transition"
              >
                Ввести токен сброса
              </button>
            </div>
          </form>
        ) : (
          /* Password Reset Form using Recovery Token */
          <form onSubmit={handleResetPassword} className="space-y-4">
            <div>
              <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                Токен сброса
              </label>
              <input
                type="text"
                required
                value={resetToken}
                onChange={(e) => setResetToken(e.target.value)}
                className="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition text-xs"
                placeholder="Вставьте токен из ссылки"
              />
            </div>

            <div>
              <label className="block text-xs font-semibold text-slate-500 dark:text-slate-400 uppercase tracking-wider mb-1">
                Новый пароль
              </label>
              <input
                type="password"
                required
                value={newPassword}
                onChange={(e) => setNewPassword(e.target.value)}
                className="w-full px-4 py-2.5 rounded-xl border border-slate-200 dark:border-slate-800 bg-slate-50 dark:bg-slate-950/40 text-slate-900 dark:text-slate-50 focus:outline-none focus:ring-2 focus:ring-primary-500/20 focus:border-primary-500 transition"
                placeholder="Новый пароль"
              />
            </div>

            <button
              type="submit"
              disabled={loading}
              className="w-full py-3 bg-primary-600 hover:bg-primary-700 disabled:opacity-50 text-white rounded-xl font-semibold shadow-lg shadow-primary-500/25 transition focus:ring-2 focus:ring-primary-500/20"
            >
              {loading ? '...' : 'Изменить пароль'}
            </button>

            <div className="text-center mt-4">
              <button
                type="button"
                onClick={() => { setIsRecovering(false); setIsResetting(false); setError(''); setResetSuccess(''); }}
                className="text-xs font-medium text-slate-500 hover:text-slate-700 dark:text-slate-400 dark:hover:text-slate-300 transition"
              >
                Вернуться к входу
              </button>
            </div>
          </form>
        )}

        {/* Demo Credentials Helper */}
        <div className="mt-8 pt-6 border-t border-slate-200/50 dark:border-slate-800/50">
          <p className="text-xs font-bold text-slate-500 dark:text-slate-400 mb-2 uppercase tracking-wider">
            Демо-пользователи:
          </p>
          <div className="grid grid-cols-2 gap-2 text-xs text-slate-500 dark:text-slate-400">
            <div>Admin: <code className="bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded">admin</code> / <code className="bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded">admin123</code></div>
            <div>Head: <code className="bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded">head</code> / <code className="bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded">head123</code></div>
            <div>Responsible: <code className="bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded">responsible</code> / <code className="bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded">resp123</code></div>
            <div>Executor: <code className="bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded">executor</code> / <code className="bg-slate-100 dark:bg-slate-800 px-1 py-0.5 rounded">exec123</code></div>
          </div>
        </div>
      </div>
    </div>
  );
};
