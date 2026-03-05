'use client';
import { useEffect, useState } from 'react';
import { api } from '@/lib/api';

export default function ClientsPage() {
  const [clients, setClients] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/clients').then(data => {
      setClients(data);
      setLoading(false);
    }).catch(err => {
      console.error(err);
      setLoading(false);
    });
  }, []);

  return (
    <div className="space-y-6">
      <div className="flex justify-between items-center">
        <h1 className="text-3xl font-bold text-gray-900">Клиенты</h1>
        <button className="bg-blue-600 text-white px-4 py-2 rounded-md hover:bg-blue-700 transition-colors">Регистрация клиента</button>
      </div>
      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-6">
        {loading ? (
          <p>Загрузка клиентов...</p>
        ) : clients.length === 0 ? (
          <p>Клиенты не зарегистрированы.</p>
        ) : clients.map((client: any) => (
          <div key={client.id} className="bg-white p-6 rounded-lg shadow-sm border border-gray-100">
            <h3 className="text-xl font-semibold text-gray-800">{client.name}</h3>
            <p className="text-sm text-gray-500 mt-1">{client.email || 'Email не указан'}</p>
            <div className="mt-4 flex justify-between items-center">
              <span className="bg-blue-100 text-blue-800 text-xs px-2 py-1 rounded-full uppercase font-bold">Стандарт</span>
              <button className="text-blue-600 text-sm font-medium hover:underline">Подробнее</button>
            </div>
          </div>
        ))}
      </div>
    </div>
  );
}
