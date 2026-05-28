import React, { useState, useEffect } from 'react';
import axios from 'axios';
import { Printer, FileText, Package, Download } from 'lucide-react';

const PrintModule = () => {
  const [products, setProducts] = useState<any[]>([]);
  const [templates, setTemplates] = useState<any[]>([]);
  const [selectedProduct, setSelectedProduct] = useState<string>('');
  const [selectedTemplate, setSelectedTemplate] = useState<string>('');
  const [quantity, setQuantity] = useState(1);
  const [loading, setLoading] = useState(false);

  useEffect(() => {
    const fetchData = async () => {
      try {
        const token = localStorage.getItem('token');
        const [prodResp, tempResp] = await Promise.all([
          axios.get('/api/products', { headers: { Authorization: 'Bearer ' + token } }),
          axios.get('/api/templates', { headers: { Authorization: 'Bearer ' + token } })
        ]);
        setProducts(prodResp.data);
        setTemplates(tempResp.data);
        if (prodResp.data.length > 0) setSelectedProduct(prodResp.data[0].id);
        if (tempResp.data.length > 0) setSelectedTemplate(tempResp.data[0].id);
      } catch (e) {
        console.error(e);
      }
    };
    fetchData();
  }, []);

  const handlePrint = async () => {
    setLoading(true);
    try {
      const product = products.find(p => p.id == selectedProduct);
      const template = templates.find(t => t.id == selectedTemplate);

      if (!product || !template) return;

      const response = await axios.post('/api/print', {
        template: {
          ...template,
          width: template.width,
          height: template.height,
          elements: JSON.parse(template.data)
        },
        product: product
      }, {
        responseType: 'blob',
        headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
      });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', `labels_${product.name}.pdf`);
      document.body.appendChild(link);
      link.click();
    } catch (e) {
      console.error(e);
      alert('Ошибка печати');
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="p-8 h-full flex flex-col items-center">
      <div className="w-full max-w-4xl">
        <h1 className="text-2xl font-bold mb-8 text-white flex items-center gap-3">
          <Printer className="text-industrial-primary" /> Печать этикеток
        </h1>

        <div className="grid grid-cols-1 md:grid-cols-2 gap-8">
           <div className="bg-industrial-800 p-6 rounded-xl border border-industrial-700 space-y-6">
              <h3 className="text-sm font-bold uppercase text-gray-500 tracking-wider">Выбор данных</h3>

              <div>
                <label className="block text-xs text-gray-400 mb-2">Выберите товар</label>
                <div className="relative">
                  <Package className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" size={16} />
                  <select
                    className="w-full bg-industrial-900 border border-industrial-700 rounded-lg pl-10 pr-4 py-2.5 text-sm outline-none focus:border-industrial-primary"
                    value={selectedProduct}
                    onChange={(e) => setSelectedProduct(e.target.value)}
                  >
                    {products.map(p => <option key={p.id} value={p.id}>{p.name}</option>)}
                    {products.length === 0 && <option>Нет товаров</option>}
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-xs text-gray-400 mb-2">Выберите шаблон</label>
                <div className="relative">
                  <FileText className="absolute left-3 top-1/2 -translate-y-1/2 text-gray-500" size={16} />
                  <select
                    className="w-full bg-industrial-900 border border-industrial-700 rounded-lg pl-10 pr-4 py-2.5 text-sm outline-none focus:border-industrial-primary"
                    value={selectedTemplate}
                    onChange={(e) => setSelectedTemplate(e.target.value)}
                  >
                    {templates.map(t => <option key={t.id} value={t.id}>{t.name} ({t.width}x{t.height}мм)</option>)}
                    {templates.length === 0 && <option>Нет шаблонов</option>}
                  </select>
                </div>
              </div>

              <div>
                <label className="block text-xs text-gray-400 mb-2">Количество этикеток</label>
                <input
                  type="number"
                  min="1"
                  className="w-full bg-industrial-900 border border-industrial-700 rounded-lg px-4 py-2.5 text-sm outline-none focus:border-industrial-primary"
                  value={quantity}
                  onChange={(e) => setQuantity(parseInt(e.target.value))}
                />
              </div>

              <button
                onClick={handlePrint}
                disabled={loading || products.length === 0 || templates.length === 0}
                className="w-full bg-industrial-primary text-black font-bold py-3 rounded-lg hover:bg-yellow-500 transition-colors disabled:opacity-50 flex items-center justify-center gap-2"
              >
                <Download size={20}/> {loading ? 'Генерация...' : 'Сформировать PDF'}
              </button>
           </div>

           <div className="bg-industrial-800 p-6 rounded-xl border border-industrial-700 flex flex-col justify-center items-center text-center">
              <div className="w-20 h-20 bg-industrial-700 rounded-full flex items-center justify-center mb-4">
                 <Printer size={40} className="text-gray-500" />
              </div>
              <h3 className="text-lg font-bold text-white mb-2">Готовность к печати</h3>
              <p className="text-gray-400 text-sm">
                Выберите товар и шаблон для генерации файла печати.
                Система автоматически подставит данные товара в выбранный макет.
              </p>
           </div>
        </div>
      </div>
    </div>
  );
};

export default PrintModule;
