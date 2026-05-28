import React, { useState } from 'react';
import axios from 'axios';

interface LoginProps {
  onLogin: (user: any, token: string) => void;
}

const Login: React.FC<LoginProps> = ({ onLogin }) => {
  const [username, setUsername] = useState('admin');
  const [password, setPassword] = useState('admin');
  const [error, setError] = useState('');
  const [loading, setLoading] = useState(false);

  const handleSubmit = async (e: React.FormEvent) => {
    e.preventDefault();
    setLoading(true);
    setError('');
    try {
      const resp = await axios.post('/api/auth/login', { username, password });
      if (resp.data.status === 'success') {
        onLogin(resp.data.user, resp.data.token);
      } else {
        setError(resp.data.message);
      }
    } catch (err) {
      setError('Ошибка подключения к серверу');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="min-h-screen flex items-center justify-center bg-black">
      <div className="w-full max-w-md p-8 bg-industrial-800 rounded-2xl border border-industrial-700 shadow-2xl">
        <div className="text-center mb-10">
          <h1 className="text-4xl font-black text-industrial-primary tracking-tighter mb-2">VSPRINT</h1>
          <p className="text-gray-400 text-sm">Профессиональная промышленная печать</p>
        </div>

        <form onSubmit={handleSubmit} className="space-y-6">
          <div>
            <label className="block text-xs font-bold text-gray-500 uppercase mb-2">Логин</label>
            <input
              type="text"
              className="w-full bg-industrial-900 border border-industrial-700 rounded-lg px-4 py-3 text-white focus:border-industrial-primary outline-none transition-all"
              value={username}
              onChange={(e) => setUsername(e.target.value)}
              required
            />
          </div>
          <div>
            <label className="block text-xs font-bold text-gray-500 uppercase mb-2">Пароль</label>
            <input
              type="password"
              className="w-full bg-industrial-900 border border-industrial-700 rounded-lg px-4 py-3 text-white focus:border-industrial-primary outline-none transition-all"
              value={password}
              onChange={(e) => setPassword(e.target.value)}
              required
            />
          </div>

          {error && <div className="text-red-500 text-sm text-center font-medium bg-red-500/10 py-2 rounded">{error}</div>}

          <button
            type="submit"
            disabled={loading}
            className="w-full bg-industrial-primary text-black font-bold py-3 rounded-lg hover:bg-yellow-500 transition-colors disabled:opacity-50"
          >
            {loading ? 'Вход...' : 'Войти в систему'}
          </button>
        </form>

        <div className="mt-8 text-center text-gray-600 text-xs uppercase tracking-widest font-bold">
           Industrial Labeling Solutions
        </div>
      </div>
    </div>
  );
};

export default Login;
