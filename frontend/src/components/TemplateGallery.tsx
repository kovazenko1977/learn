import { useState, useEffect } from 'react';
import axios from 'axios';
import { Layout, FileJson, Download, Plus } from 'lucide-react';

export const TemplateGallery = () => {
  const [templates, setTemplates] = useState<any[]>([]);
  const [loading, setLoading] = useState(true);

  useEffect(() => {
    fetchTemplates();
  }, []);

  const fetchTemplates = async () => {
    try {
      const res = await axios.get('/api.php?action=templates');
      setTemplates(res.data);
    } catch (e) {
      console.error(e);
    } finally {
      setLoading(false);
    }
  };

  return (
    <div className="flex-1 p-6 overflow-auto">
      <div className="flex justify-between items-center mb-6">
        <h2 className="text-2xl font-bold">Галерея шаблонов</h2>
        <div className="flex gap-2">
            <button className="bg-zebra-sidebar border border-white/10 px-4 py-2 rounded flex items-center gap-2 hover:bg-zebra-active">
                <FileJson className="w-4 h-4" /> Импорт JSON
            </button>
            <button className="bg-zebra-accent px-4 py-2 rounded flex items-center gap-2">
                <Plus className="w-4 h-4" /> Новый шаблон
            </button>
        </div>
      </div>

      <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4 gap-6">
        {templates.map(t => (
          <div key={t.id} className="bg-zebra-sidebar rounded-lg border border-white/10 overflow-hidden hover:border-zebra-accent transition-colors group cursor-pointer">
            <div className="aspect-[3/2] bg-white/5 flex items-center justify-center border-b border-white/10">
              <Layout className="w-12 h-12 text-gray-700 group-hover:text-zebra-accent transition-colors" />
            </div>
            <div className="p-4">
              <h3 className="font-bold mb-1">{t.name}</h3>
              <div className="text-xs text-gray-500 mb-4 uppercase">{t.width}x{t.height} {t.unit} • {t.category_name || 'Общее'}</div>
              <div className="flex gap-2">
                <button className="flex-1 bg-zebra-active py-2 rounded text-xs hover:bg-white/10 transition-colors">Открыть</button>
                <button className="p-2 hover:text-zebra-accent transition-colors"><Download className="w-4 h-4" /></button>
              </div>
            </div>
          </div>
        ))}

        {/* Placeholder for "Add New" */}
        <div className="border-2 border-dashed border-white/5 rounded-lg flex flex-col items-center justify-center p-8 hover:bg-white/5 transition-colors cursor-pointer text-gray-500 hover:text-white">
            <Plus className="w-8 h-8 mb-2" />
            <span className="text-sm font-medium">Создать шаблон</span>
        </div>
      </div>

      {loading && <div className="p-12 text-center text-gray-500">Загрузка...</div>}
    </div>
  );
};
