import { useState, useEffect } from 'react';
import axios from 'axios';
import { Search, Plus, Trash2, Edit } from 'lucide-react';

export const ProductCatalog = () => {
  const [products, setProducts] = useState<any[]>([]);
  const [search, setSearch] = useState('');
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchProducts();
  }, []);

  const fetchProducts = async () => {
    try {
      const res = await axios.get('/api.php?action=products');
      setProducts(res.data);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  const filtered = products.filter(p =>
    p.name.toLowerCase().includes(search.toLowerCase()) ||
    p.barcode?.includes(search)
  );

  return (
    <div className="flex-1 p-6 overflow-auto">
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-2xl font-bold">Каталог продукции</h2>
        <button className="bg-zebra-accent px-4 py-2 rounded flex items-center gap-2">
          <Plus className="w-4 h-4" /> Добавить товар
        </button>
      </div>

      <div className="relative mb-6">
        <Search className="absolute left-3 top-1/2 -translate-y-1/2 w-4 h-4 text-gray-400" />
        <input
          className="w-full bg-zebra-sidebar border border-white/10 rounded-lg py-2 pl-10 pr-4 outline-none focus:border-zebra-accent"
          placeholder="Поиск по названию или штрихкоду..."
          value={search}
          onChange={(e) => setSearch(e.target.value)}
        />
      </div>

      <div className="bg-zebra-sidebar rounded-lg border border-white/10 overflow-hidden">
        <table className="w-full text-left text-sm">
          <thead className="bg-white/5 uppercase text-xs text-gray-400">
            <tr>
              <th className="p-4">Название</th>
              <th className="p-4">Штрихкод</th>
              <th className="p-4">Категория</th>
              <th className="p-4">Объем</th>
              <th className="p-4 text-right">Действия</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-white/5">
            {filtered.map(p => (
              <tr key={p.id} className="hover:bg-white/5 transition-colors">
                <td className="p-4 font-medium">{p.name}</td>
                <td className="p-4 text-gray-400">{p.barcode || '-'}</td>
                <td className="p-4">{p.category_name || 'Без категории'}</td>
                <td className="p-4">{p.volume || '-'}</td>
                <td className="p-4 text-right flex justify-end gap-2">
                  <button className="p-2 hover:text-zebra-accent"><Edit className="w-4 h-4" /></button>
                  <button className="p-2 hover:text-red-500"><Trash2 className="w-4 h-4" /></button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
        {loading && <div className="p-8 text-center text-gray-500">Загрузка...</div>}
        {!loading && filtered.length === 0 && <div className="p-8 text-center text-gray-500">Товары не найдены</div>}
      </div>
    </div>
  );
};
