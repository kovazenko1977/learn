import { useState } from 'react';
import axios from 'axios';
import { Canvas } from './components/editor/Canvas';
import { ProductCatalog } from './components/ProductCatalog';
import { TemplateGallery } from './components/TemplateGallery';
import type { LabelElement } from './types';
import { useTheme } from './hooks/useTheme';
import {
  Settings,
  Type,
  Barcode,
  Image as ImageIcon,
  Save,
  Printer,
  Database,
  Layout,
  Plus,
  Sun,
  Moon,
  Trash2,
  ChevronDown,
  Download
} from 'lucide-react';

type View = 'editor' | 'catalog' | 'gallery';

interface PrintProfile {
    name: string;
    width: number;
    height: number;
}

const PRINT_PROFILES: PrintProfile[] = [
    { name: '58 x 40 mm', width: 58, height: 40 },
    { name: '58 x 60 mm', width: 58, height: 60 },
    { name: '100 x 150 mm', width: 100, height: 150 },
    { name: 'A4 (210 x 297 mm)', width: 210, height: 297 },
];

function App() {
  const { theme, setTheme } = useTheme();
  const [view, setView] = useState<View>('editor');
  const [profile, setProfile] = useState<PrintProfile>(PRINT_PROFILES[0]);
  const [elements, setElements] = useState<LabelElement[]>([
    {
      id: '1',
      type: 'text',
      x: 5,
      y: 5,
      width: 48,
      height: 8,
      content: 'ЧИНАЗЕС СО ВКУСОМ ГРЕЙПФРУТА',
      fontSize: 12,
      fontStyle: 'bold',
      align: 'center'
    },
    {
        id: '2',
        type: 'text',
        x: 5,
        y: 15,
        width: 48,
        height: 5,
        content: '{GOST}',
        fontSize: 10,
        align: 'center'
    },
    {
        id: '3',
        type: 'barcode',
        barcodeType: 'EAN13',
        x: 5,
        y: 22,
        width: 48,
        height: 12,
        content: '{BARCODE}'
    }
  ]);
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [scale, setScale] = useState(1.5);
  const [isPrinting, setIsPrinting] = useState(false);

  const addElement = (type: LabelElement['type']) => {
    const newElement: LabelElement = {
      id: Math.random().toString(36).substr(2, 9),
      type,
      x: 5,
      y: 5,
      width: 20,
      height: 10,
      content: type === 'text' ? 'Новый текст' : '12345678',
      fontSize: 12,
      barcodeType: type === 'barcode' ? 'CODE128' : undefined,
      src: type === 'sign' ? '/assets/signs/fragile.svg' : undefined
    };
    setElements([...elements, newElement]);
    setSelectedId(newElement.id);
  };

  const handlePrint = async () => {
    setIsPrinting(true);
    try {
        const response = await axios.post('/api.php?action=print', {
            width: profile.width,
            height: profile.height,
            elements: elements.map(el => ({
                ...el,
                // Ensure paths are correct for backend
                src: el.src ? el.src : undefined
            }))
        }, { responseType: 'blob' });

        const url = window.URL.createObjectURL(new Blob([response.data]));
        const link = document.createElement('a');
        link.href = url;
        link.setAttribute('download', `label_${Date.now()}.pdf`);
        document.body.appendChild(link);
        link.click();
        link.remove();
    } catch (e) {
        console.error('Print failed', e);
        alert('Ошибка при генерации PDF');
    } finally {
        setIsPrinting(false);
    }
  };

  const handleSave = async () => {
      try {
          await axios.post('/api.php?action=templates', {
              name: `Template ${Date.now()}`,
              width: profile.width,
              height: profile.height,
              unit: 'mm',
              json_data: JSON.stringify(elements)
          });
          alert('Шаблон сохранен');
      } catch (e) {
          console.error('Save failed', e);
      }
  };

  const signs = [
      { name: 'Хрупкое', src: '/assets/signs/fragile.svg' },
      { name: 'Верх', src: '/assets/signs/top.svg' },
      { name: 'Беречь от влаги', src: '/assets/signs/keep_dry.svg' },
      { name: 'Пищевой знак', src: '/assets/signs/food_safe.svg' },
      { name: 'Не кантовать', src: '/assets/signs/do_not_tumble.svg' },
      { name: 'Центр тяжести', src: '/assets/signs/center.svg' },
      { name: 'Осторожно', src: '/assets/signs/caution.svg' },
      { name: 'Беречь от солнца', src: '/assets/signs/protect_from_sun.svg' },
  ];

  return (
    <div className={`flex h-screen font-sans select-none overflow-hidden transition-colors duration-300 ${
        theme === 'dark' ? 'bg-zebra-dark text-white' : 'bg-zebra-light-bg text-zebra-light-text'
    }`}>
      {/* Sidebar Navigation */}
      <div className={`w-16 border-r flex flex-col items-center py-4 gap-4 ${
          theme === 'dark' ? 'bg-zebra-sidebar border-white/10' : 'bg-zebra-light-sidebar border-gray-200 shadow-lg'
      }`}>
          <div className="p-2 text-zebra-accent mb-4"><Settings className="w-8 h-8" /></div>
          <button
            onClick={() => setView('editor')}
            className={`p-3 rounded-lg transition-all ${view === 'editor' ? 'bg-zebra-accent text-white shadow-md' : 'text-gray-500 hover:bg-zebra-accent/10'}`}
            title="Редактор"
          >
              <Layout className="w-6 h-6" />
          </button>
          <button
            onClick={() => setView('catalog')}
            className={`p-3 rounded-lg transition-all ${view === 'catalog' ? 'bg-zebra-accent text-white shadow-md' : 'text-gray-500 hover:bg-zebra-accent/10'}`}
            title="Каталог товаров"
          >
              <Database className="w-6 h-6" />
          </button>
          <button
            onClick={() => setView('gallery')}
            className={`p-3 rounded-lg transition-all ${view === 'gallery' ? 'bg-zebra-accent text-white shadow-md' : 'text-gray-500 hover:bg-zebra-accent/10'}`}
            title="Шаблоны"
          >
              <Download className="w-6 h-6" />
          </button>

          <div className="mt-auto flex flex-col gap-2">
            <button
                onClick={() => setTheme(theme === 'dark' ? 'light' : 'dark')}
                className="p-3 text-gray-500 hover:text-zebra-accent transition-colors"
            >
                {theme === 'dark' ? <Sun className="w-6 h-6" /> : <Moon className="w-6 h-6" />}
            </button>
          </div>
      </div>

      {view === 'editor' && (
          <>
            {/* Tool Palette */}
            <div className={`w-56 border-r flex flex-col ${
                theme === 'dark' ? 'bg-zebra-sidebar border-white/10' : 'bg-zebra-light-sidebar border-gray-200'
            }`}>
                <div className="p-4 border-b border-inherit font-bold text-xs uppercase text-gray-500 tracking-wider">Инструменты</div>
                <div className="p-2 flex flex-col gap-1">
                    <button onClick={() => addElement('text')} className={`flex items-center gap-3 p-2 rounded text-sm transition-colors ${
                        theme === 'dark' ? 'hover:bg-zebra-active' : 'hover:bg-zebra-light-active'
                    }`}>
                        <Type className="w-4 h-4 text-zebra-accent" /> Текст
                    </button>
                    <button onClick={() => addElement('barcode')} className={`flex items-center gap-3 p-2 rounded text-sm transition-colors ${
                        theme === 'dark' ? 'hover:bg-zebra-active' : 'hover:bg-zebra-light-active'
                    }`}>
                        <Barcode className="w-4 h-4 text-zebra-accent" /> Штрихкод
                    </button>
                </div>

                <div className="p-4 border-b border-t border-inherit font-bold text-xs uppercase text-gray-500 tracking-wider mt-4">Знаки ГОСТ</div>
                <div className="p-2 grid grid-cols-2 gap-2 overflow-y-auto max-h-[300px]">
                    {signs.map(sign => (
                        <button
                            key={sign.name}
                            onClick={() => {
                                const newEl: LabelElement = {
                                    id: Math.random().toString(36).substr(2, 9),
                                    type: 'sign',
                                    x: 10, y: 10, width: 10, height: 10,
                                    content: sign.name,
                                    src: sign.src
                                };
                                setElements([...elements, newEl]);
                                setSelectedId(newEl.id);
                            }}
                            className={`p-2 rounded flex flex-col items-center gap-1 transition-all ${
                                theme === 'dark' ? 'bg-white/5 hover:bg-zebra-active' : 'bg-gray-100 hover:bg-zebra-light-active shadow-sm'
                            }`}
                        >
                            <img src={sign.src} alt={sign.name} className={`w-8 h-8 ${theme === 'dark' ? 'invert' : ''}`} />
                            <span className="text-[10px] text-gray-400 truncate w-full text-center">{sign.name}</span>
                        </button>
                    ))}
                </div>

                <div className="mt-auto p-4 border-t border-inherit flex flex-col gap-2">
                    <button
                        onClick={handlePrint}
                        disabled={isPrinting}
                        className="flex items-center gap-2 p-2 bg-zebra-accent hover:bg-zebra-accent/80 text-white rounded text-sm justify-center font-bold shadow-lg disabled:opacity-50"
                    >
                        <Printer className="w-4 h-4" /> {isPrinting ? 'ГЕНЕРАЦИЯ...' : 'ПЕЧАТЬ'}
                    </button>
                    <button
                        onClick={handleSave}
                        className="flex items-center gap-2 p-2 bg-zebra-active hover:bg-zebra-active/80 text-white rounded text-sm justify-center"
                    >
                        <Save className="w-4 h-4" /> СОХРАНИТЬ
                    </button>
                </div>
            </div>

            {/* Canvas Area */}
            <div className="flex-1 flex flex-col relative">
                <div className={`h-12 border-b flex items-center px-4 gap-4 justify-between ${
                    theme === 'dark' ? 'bg-zebra-sidebar border-white/10' : 'bg-zebra-light-sidebar border-gray-200 shadow-sm z-10'
                }`}>
                    <div className="flex items-center gap-6 text-sm">
                        <div className="relative group">
                            <button className="flex items-center gap-2 font-bold text-zebra-accent">
                                {profile.name} <ChevronDown className="w-4 h-4" />
                            </button>
                            <div className={`absolute top-full left-0 mt-1 w-48 rounded-lg shadow-xl border overflow-hidden hidden group-hover:block z-50 ${
                                theme === 'dark' ? 'bg-zebra-sidebar border-white/10' : 'bg-white border-gray-200'
                            }`}>
                                {PRINT_PROFILES.map(p => (
                                    <button
                                        key={p.name}
                                        onClick={() => setProfile(p)}
                                        className={`w-full text-left p-3 text-xs hover:bg-zebra-accent hover:text-white transition-colors ${
                                            profile.name === p.name ? 'bg-zebra-accent/20 text-zebra-accent' : ''
                                        }`}
                                    >
                                        {p.name}
                                    </button>
                                ))}
                            </div>
                        </div>

                        <div className="flex items-center gap-3">
                            <span className="text-gray-500 text-xs uppercase font-bold">Масштаб:</span>
                            <input
                                type="range" min="0.5" max="3" step="0.1" value={scale}
                                onChange={(e) => setScale(parseFloat(e.target.value))}
                                className="w-32 h-1 bg-gray-700 rounded-lg appearance-none cursor-pointer accent-zebra-accent"
                            />
                            <span className="text-xs text-gray-400 font-mono">{Math.round(scale * 100)}%</span>
                        </div>
                    </div>
                </div>

                <Canvas
                    width={profile.width}
                    height={profile.height}
                    elements={elements}
                    selectedId={selectedId}
                    onSelect={setSelectedId}
                    onChange={setElements}
                    scale={scale}
                />
            </div>

            {/* Properties Panel */}
            <div className={`w-72 border-l flex flex-col ${
                theme === 'dark' ? 'bg-zebra-sidebar border-white/10' : 'bg-zebra-light-sidebar border-gray-200'
            }`}>
                <div className="p-4 border-b border-inherit font-bold text-xs uppercase text-gray-500 tracking-wider">Свойства</div>
                {selectedId ? (
                    <div className="p-4 flex flex-col gap-6 overflow-auto">
                        <div className="flex flex-col gap-2">
                            <label className="text-xs text-gray-500 uppercase font-bold">Содержимое</label>
                            <textarea
                                className={`border p-2 text-sm rounded focus:border-zebra-accent outline-none min-h-[80px] resize-none transition-colors ${
                                    theme === 'dark' ? 'bg-zebra-dark border-white/10' : 'bg-white border-gray-200'
                                }`}
                                value={elements.find(el => el.id === selectedId)?.content || ''}
                                onChange={(e) => {
                                    setElements(elements.map(el => el.id === selectedId ? {...el, content: e.target.value} : el))
                                }}
                            />
                            <p className="text-[10px] text-gray-500 italic">Поддерживаются переменные: {'{PRODUCT}, {DATE}, {BARCODE}...'}</p>
                        </div>

                        <div className="grid grid-cols-2 gap-4">
                            <div className="flex flex-col gap-1">
                                <label className="text-xs text-gray-500 uppercase font-bold">Размер</label>
                                <input
                                    type="number"
                                    className={`border p-1 text-sm rounded outline-none ${
                                        theme === 'dark' ? 'bg-zebra-dark border-white/10' : 'bg-white border-gray-200'
                                    }`}
                                    value={elements.find(el => el.id === selectedId)?.fontSize || 12}
                                    onChange={(e) => {
                                        setElements(elements.map(el => el.id === selectedId ? {...el, fontSize: parseInt(e.target.value)} : el))
                                    }}
                                />
                            </div>
                            <div className="flex flex-col gap-1">
                                <label className="text-xs text-gray-500 uppercase font-bold">Поворот</label>
                                <input
                                    type="number"
                                    className={`border p-1 text-sm rounded outline-none ${
                                        theme === 'dark' ? 'bg-zebra-dark border-white/10' : 'bg-white border-gray-200'
                                    }`}
                                    value={Math.round(elements.find(el => el.id === selectedId)?.rotation || 0)}
                                    onChange={(e) => {
                                        setElements(elements.map(el => el.id === selectedId ? {...el, rotation: parseInt(e.target.value)} : el))
                                    }}
                                />
                            </div>
                        </div>

                        <div className="flex flex-col gap-2">
                             <label className="text-xs text-gray-500 uppercase font-bold">Выравнивание</label>
                             <div className={`flex rounded overflow-hidden border ${theme === 'dark' ? 'border-white/10' : 'border-gray-200'}`}>
                                {['left', 'center', 'right'].map(a => (
                                    <button
                                        key={a}
                                        onClick={() => setElements(elements.map(el => el.id === selectedId ? {...el, align: a as any} : el))}
                                        className={`flex-1 p-2 text-xs uppercase font-bold ${
                                            elements.find(el => el.id === selectedId)?.align === a ? 'bg-zebra-accent text-white' : ''
                                        }`}
                                    >
                                        {a}
                                    </button>
                                ))}
                             </div>
                        </div>

                        {elements.find(el => el.id === selectedId)?.type === 'barcode' && (
                            <div className="flex flex-col gap-2 border-t border-inherit pt-4">
                                <label className="text-xs text-gray-500 uppercase font-bold">Тип штрихкода</label>
                                <select
                                    className={`border p-2 text-sm rounded outline-none focus:border-zebra-accent ${
                                        theme === 'dark' ? 'bg-zebra-dark border-white/10' : 'bg-white border-gray-200'
                                    }`}
                                    value={elements.find(el => el.id === selectedId)?.barcodeType || 'CODE128'}
                                    onChange={(e) => {
                                        setElements(elements.map(el => el.id === selectedId ? {...el, barcodeType: e.target.value as any} : el))
                                    }}
                                >
                                    <option value="EAN13">EAN-13</option>
                                    <option value="CODE128">Code 128</option>
                                    <option value="QR">QR Code</option>
                                    <option value="DATAMATRIX">DataMatrix</option>
                                </select>
                            </div>
                        )}

                        <div className="mt-auto border-t border-inherit pt-4">
                            <button
                                onClick={() => {
                                    setElements(elements.filter(el => el.id !== selectedId));
                                    setSelectedId(null);
                                }}
                                className="w-full py-2 bg-red-500/10 text-red-500 hover:bg-red-500/20 rounded text-xs transition-all flex items-center justify-center gap-2 font-bold"
                            >
                                <Trash2 className="w-3 h-3" /> УДАЛИТЬ ЭЛЕМЕНТ
                            </button>
                        </div>
                    </div>
                ) : (
                    <div className="flex-1 flex flex-col items-center justify-center p-8 text-center text-gray-400">
                        <Plus className="w-12 h-12 mb-4 opacity-10" />
                        <p className="text-sm font-medium">Выберите элемент для редактирования</p>
                    </div>
                )}
            </div>
          </>
      )}

      {view === 'catalog' && <ProductCatalog />}
      {view === 'gallery' && <TemplateGallery />}
    </div>
  );
}

export default App;
