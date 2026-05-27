import React, { useState, useEffect } from 'react';
import axios from 'axios';

const ProductCatalog = () => {
  const [products, setProducts] = useState([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchProducts();
  }, []);

  const fetchProducts = async () => {
    try {
      const response = await axios.get('/api/products');
      setProducts(response.data);
    } catch (error) {
      console.error('Error fetching products:', error);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="p-6">
      <div className="flex justify-between items-center mb-6">
        <h1 className="text-2xl font-bold">Каталог продукции</h1>
        <button className="px-4 py-2 bg-green-600 text-white rounded">Добавить товар</button>
      </div>
      <div className="bg-white rounded-lg shadow overflow-hidden">
        <table className="min-w-full divide-y divide-gray-200">
          <thead className="bg-gray-50">
            <tr>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Название</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Штрихкод</th>
              <th className="px-6 py-3 text-left text-xs font-medium text-gray-500 uppercase">Объем</th>
              <th className="px-6 py-3 text-right">Действия</th>
            </tr>
          </thead>
          <tbody className="divide-y divide-gray-200">
            {products.map((product) => (
              <tr key={product.id}>
                <td className="px-6 py-4">{product.name}</td>
                <td className="px-6 py-4">{product.barcode}</td>
                <td className="px-6 py-4">{product.volume}</td>
                <td className="px-6 py-4 text-right">
                  <button className="text-blue-600 hover:text-blue-900 mr-3">Изменить</button>
                  <button className="text-red-600 hover:text-red-900">Удалить</button>
                </td>
              </tr>
            ))}
          </tbody>
        </table>
      </div>
    </div>
  );
};

export default ProductCatalog;
