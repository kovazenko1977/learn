'use client';
import { useEffect, useState } from 'react';
import { api } from '@/lib/api';

export default function OrdersPage() {
  const [orders, setOrders] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    api.get('/orders').then(data => {
      setOrders(data);
      setLoading(false);
    }).catch(err => {
      console.error(err);
      setLoading(false);
    });
  }, []);

  return (
    <div className="space-y-6">
      <h1 className="text-3xl font-bold text-gray-900">Управление заказами</h1>
      <div className="bg-white shadow overflow-hidden sm:rounded-md">
        <ul className="divide-y divide-gray-200">
          {loading ? (
            <li className="px-6 py-4">Загрузка заказов...</li>
          ) : orders.length === 0 ? (
            <li className="px-6 py-4">Заказы не найдены.</li>
          ) : orders.map((order: any) => (
            <li key={order.id} className="px-6 py-4 hover:bg-gray-50 flex items-center justify-between">
              <div>
                <p className="text-sm font-medium text-blue-600">Заказ #{order.id}</p>
                <p className="text-sm text-gray-500">Создан: {new Date(order.createdAt).toLocaleDateString('ru-RU')}</p>
              </div>
              <div className="flex items-center space-x-4">
                <span className={`px-2 py-1 text-xs font-semibold rounded-full ${
                  order.status === 'new' ? 'bg-yellow-100 text-yellow-800' : 'bg-green-100 text-green-800'
                }`}>
                  {order.status === 'new' ? 'Новый' : order.status}
                </span>
                <button className="text-gray-400 hover:text-gray-600">
                  <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                    <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M9 5l7 7-7 7" />
                  </svg>
                </button>
              </div>
            </li>
          ))}
        </ul>
      </div>
    </div>
  );
}
