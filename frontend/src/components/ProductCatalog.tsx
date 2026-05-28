import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Plus, Search, Edit2, Trash2, FileUp } from 'lucide-react';

const ProductCatalog = () => {
  const [products, setProducts] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchProducts();
  }, []);

  const fetchProducts = async () => {
    try {
      const token = localStorage.getItem('token');
      const resp = await axios.get('/api/products', {
        headers: { Authorization: 'Bearer ' + token }
      });
      setProducts(resp.data);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="p-8 h-full overflow-y-auto">
      <div className="flex justify-between items-center mb-8">
        <h1 className="text-2xl font-bold text-white">Каталог продукции</h1>
        <div className="flex gap-4">
          <button className="flex items-center gap-2 bg-industrial-700 text-white px-4 py-2 rounded font-medium hover:bg-industrial-600 transition-colors">
            <FileUp size={18}/> Импорт
          </button>
          <button className="flex items-center gap-2 bg-industrial-primary text-black px-4 py-2 rounded font-medium hover:bg-yellow-500 transition-colors">
            <Plus size={18}/> Добавить товар
          </button>
        </div>
      </div>

      <div className="bg-industrial-800 rounded-lg border border-industrial-700 overflow-hidden">
        <div className="p-4 border-b border-industrial-700 bg-industrial-900/50 flex items-center gap-4">
           <div className="relative flex-1">
              <Search className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" size={16} />
              <input
                type="text"
                placeholder="Поиск по названию или штрихкоду..."
                className="w-full bg-industrial-900 border border-industrial-700 rounded-md pl-10 pr-4 py-2 text-sm focus:border-industrial-primary outline-none"
              />
           </div>
        </div>

        <table className="w-full text-left text-sm">
          <thead className="bg-industrial-900/50 text-gray-400 uppercase text-[10px] font-bold">
            <tr>
              <th className="px-6 py-3">Название</th>
              <th className="px-6 py-3">Артикул / Штрихкод</th>
              <th className="px-6 py-3">Объем / Крепость</th>
              <th className="px-6 py-3">Производитель</th>
              <th className="px-6 py-3 text-right">Действия</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-industrial-700">
            {products.length === 0 ? (
              <tr>
                <td colSpan={5} className="px-6 py-10 text-center text-gray-500 italic">
                  {loading ? 'Загрузка...' : 'Список товаров пуст'}
                </td>
              </tr>
            ) : (
              products.map(p => (
                <tr key={p.id} className="hover:bg-industrial-700/50 transition-colors">
                  <td className="px-6 py-4 font-medium text-white">{p.name}</td>
                  <td className="px-6 py-4 text-gray-400">{p.barcode || '—'}</td>
                  <td className="px-6 py-4 text-gray-400">{p.volume} / {p.alcohol}%</td>
                  <td className="px-6 py-4 text-gray-400">{p.manufacturer}</td>
                  <td className="px-6 py-4 text-right space-x-2">
                    <button className="p-1 hover:text-industrial-primary transition-colors"><Edit2 size={16}/></button>
                    <button className="p-1 hover:text-red-500 transition-colors"><Trash2 size={16}/></button>
                  </td>
                </tr>
              ))
            )}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default ProductCatalog;
