import React, { useState } from 'react';
import Canvas from './Canvas';
import type { LabelElement, LabelTemplate } from '../../types/label';
import {
  Type,
  Square,
  Barcode,
  Image as ImageIcon,
  Save,
  Printer,
  Trash2,
  Layers,
  Settings,
  Grid,
  Plus,
  ArrowLeft,
  ChevronRight,
  Monitor
} from 'lucide-react';

const Editor: React.FC = () => {
  const [template, setTemplate] = useState<LabelTemplate>({
    name: 'Новая этикетка',
    category: 'коробки',
    width_mm: 100,
    height_mm: 150,
    elements: [],
  });
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [showGrid, setShowGrid] = useState(true);

  const handleUpdateElement = (id: string, attrs: Partial<LabelElement>) => {
    setTemplate((prev) => ({
      ...prev,
      elements: prev.elements.map((el) => (el.id === id ? { ...el, ...attrs } : el)),
    }));
  };

  const addElement = (type: LabelElement['type'], content: string = '', barcodeType?: any) => {
    const newElement: LabelElement = {
      id: Math.random().toString(36).substr(2, 9),
      type,
      x: 10,
      y: 10,
      width: type === 'text' ? 60 : (type === 'barcode' ? 50 : 30),
      height: type === 'text' ? 10 : (type === 'barcode' ? 25 : 30),
      content: content || (type === 'text' ? 'Текст' : ''),
      fontSize: 10,
      barcodeType: barcodeType || 'ean13',
      fill: type === 'rect' ? '#ffffff' : '#000000',
      stroke: '#000000',
      strokeWidth: 1,
    };
    setTemplate((prev) => ({
      ...prev,
      elements: [...prev.elements, newElement],
    }));
    setSelectedId(newElement.id);
  };

  return (
    <div className="flex h-screen w-full bg-[#1e1e1e] text-gray-300 font-sans overflow-hidden">
      {/* Top Navigation */}
      <div className="absolute top-0 left-0 right-0 h-12 bg-[#2d2d2d] border-b border-[#3e3e3e] flex items-center px-4 justify-between z-50">
        <div className="flex items-center gap-4">
          <div className="flex items-center gap-2 text-blue-400">
            <Monitor size={18} />
            <span className="font-bold tracking-tight text-white uppercase text-sm">LabelPro Designer</span>
          </div>
          <div className="h-4 w-[1px] bg-[#3e3e3e]"></div>
          <span className="text-sm font-medium">{template.name} • {template.width_mm}x{template.height_mm} мм</span>
        </div>
        <div className="flex items-center gap-2">
          <button className="flex items-center gap-2 px-3 py-1.5 rounded bg-[#3e3e3e] hover:bg-[#4e4e4e] transition text-sm">
            <Save size={14} /> Сохранить
          </button>
          <button className="flex items-center gap-2 px-3 py-1.5 rounded bg-green-600 hover:bg-green-700 text-white transition text-sm shadow-lg">
            <Printer size={14} /> Печать
          </button>
        </div>
      </div>

      {/* Main Sidebar (Tools) */}
      <div className="w-16 mt-12 bg-[#2d2d2d] border-r border-[#3e3e3e] flex flex-col items-center py-4 gap-4 z-40">
        <button
          title="Добавить текст"
          onClick={() => addElement('text')}
          className="p-3 rounded-lg hover:bg-[#3e3e3e] transition text-gray-400 hover:text-white"
        >
          <Type size={22} />
        </button>
        <button
          title="Добавить прямоугольник"
          onClick={() => addElement('rect')}
          className="p-3 rounded-lg hover:bg-[#3e3e3e] transition text-gray-400 hover:text-white"
        >
          <Square size={22} />
        </button>
        <button
          title="Добавить штрихкод"
          onClick={() => addElement('barcode', '123456789012')}
          className="p-3 rounded-lg hover:bg-[#3e3e3e] transition text-gray-400 hover:text-white"
        >
          <Barcode size={22} />
        </button>
        <button
          title="Добавить изображение"
          onClick={() => addElement('image')}
          className="p-3 rounded-lg hover:bg-[#3e3e3e] transition text-gray-400 hover:text-white"
        >
          <ImageIcon size={22} />
        </button>
        <div className="h-[1px] w-8 bg-[#3e3e3e] my-2"></div>
        <button
          title="Сетка"
          onClick={() => setShowGrid(!showGrid)}
          className={`p-3 rounded-lg transition ${showGrid ? 'text-blue-400 bg-[#3e3e3e]' : 'text-gray-400 hover:text-white'}`}
        >
          <Grid size={22} />
        </button>
      </div>

      {/* Editor Content Area */}
      <div className="flex-1 flex flex-col mt-12 bg-[#121212] relative overflow-hidden">
        <div className="absolute inset-0 flex items-center justify-center p-12 overflow-auto custom-scrollbar">
           <div className="relative shadow-2xl">
              <Canvas
                width={template.width_mm}
                height={template.height_mm}
                elements={template.elements}
                selectedId={selectedId}
                onSelect={setSelectedId}
                onUpdateElement={handleUpdateElement}
              />
           </div>
        </div>

        {/* Quick Toolbar (Bottom) */}
        <div className="absolute bottom-4 left-1/2 -translate-x-1/2 bg-[#2d2d2d]/90 backdrop-blur px-4 py-2 rounded-full border border-[#3e3e3e] flex items-center gap-6 shadow-2xl">
           <div className="flex items-center gap-2 text-xs uppercase font-bold text-gray-500">
              <Layers size={14} /> Слои: {template.elements.length}
           </div>
           <div className="w-[1px] h-4 bg-[#3e3e3e]"></div>
           <div className="flex items-center gap-4">
              <button onClick={() => addElement('gost_sign', '/signs/fragile.svg')} className="text-xs hover:text-white transition uppercase font-bold">Хрупкое</button>
              <button onClick={() => addElement('gost_sign', '/signs/keep_dry.svg')} className="text-xs hover:text-white transition uppercase font-bold">Влага</button>
              <button onClick={() => addElement('gost_sign', '/signs/top.svg')} className="text-xs hover:text-white transition uppercase font-bold">Верх</button>
           </div>
        </div>
      </div>

      {/* Right Sidebar (Properties & Layers) */}
      <div className="w-80 mt-12 bg-[#2d2d2d] border-l border-[#3e3e3e] flex flex-col z-40 shadow-2xl">
        <div className="p-4 border-b border-[#3e3e3e] flex items-center justify-between">
           <h2 className="font-bold text-sm uppercase tracking-wider text-gray-400 flex items-center gap-2">
             <Settings size={14} /> Свойства
           </h2>
           {selectedId && (
             <button
              onClick={() => {
                setTemplate(prev => ({ ...prev, elements: prev.elements.filter(el => el.id !== selectedId) }));
                setSelectedId(null);
              }}
              className="text-red-400 hover:text-red-300 transition"
             >
               <Trash2 size={16} />
             </button>
           )}
        </div>

        <div className="flex-1 overflow-y-auto p-4 custom-scrollbar">
          {selectedId ? (
            <div className="flex flex-col gap-6">
              {/* Common properties */}
              <div className="grid grid-cols-2 gap-3">
                 <div>
                    <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">X (мм)</label>
                    <input
                      type="number"
                      className="w-full bg-[#1e1e1e] border border-[#3e3e3e] rounded p-2 text-sm focus:border-blue-500 outline-none"
                      value={Math.round(template.elements.find(el => el.id === selectedId)?.x || 0)}
                      onChange={(e) => handleUpdateElement(selectedId, { x: Number(e.target.value) })}
                    />
                 </div>
                 <div>
                    <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Y (мм)</label>
                    <input
                      type="number"
                      className="w-full bg-[#1e1e1e] border border-[#3e3e3e] rounded p-2 text-sm focus:border-blue-500 outline-none"
                      value={Math.round(template.elements.find(el => el.id === selectedId)?.y || 0)}
                      onChange={(e) => handleUpdateElement(selectedId, { y: Number(e.target.value) })}
                    />
                 </div>
              </div>

              {/* Element specific properties */}
              {template.elements.find(el => el.id === selectedId)?.type === 'text' && (
                <>
                  <div>
                    <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Содержимое</label>
                    <textarea
                      className="w-full bg-[#1e1e1e] border border-[#3e3e3e] rounded p-2 text-sm h-24 focus:border-blue-500 outline-none"
                      value={template.elements.find(el => el.id === selectedId)?.content}
                      onChange={(e) => handleUpdateElement(selectedId, { content: e.target.value })}
                    />
                  </div>
                  <div>
                    <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Шаблоны данных</label>
                    <div className="flex flex-wrap gap-2 mt-1">
                       {['PRODUCT', 'DATE', 'BATCH', 'EXPIRATION', 'VOLUME', 'ALCOHOL'].map(field => (
                         <button
                           key={field}
                           onClick={() => handleUpdateElement(selectedId, { content: `{${field}}` })}
                           className="text-[9px] bg-[#3e3e3e] hover:bg-[#4e4e4e] px-2 py-1 rounded transition uppercase font-bold"
                         >
                           {field}
                         </button>
                       ))}
                    </div>
                  </div>
                  <div>
                    <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1 font-mono">Размер шрифта: {template.elements.find(el => el.id === selectedId)?.fontSize}pt</label>
                    <input
                      type="range"
                      min="4"
                      max="72"
                      className="w-full h-1 bg-[#3e3e3e] rounded-lg appearance-none cursor-pointer accent-blue-500"
                      value={template.elements.find(el => el.id === selectedId)?.fontSize}
                      onChange={(e) => handleUpdateElement(selectedId, { fontSize: Number(e.target.value) })}
                    />
                  </div>
                </>
              )}

              {template.elements.find(el => el.id === selectedId)?.type === 'barcode' && (
                <>
                  <div>
                    <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Стандарт</label>
                    <select
                      className="w-full bg-[#1e1e1e] border border-[#3e3e3e] rounded p-2 text-sm focus:border-blue-500 outline-none"
                      value={template.elements.find(el => el.id === selectedId)?.barcodeType}
                      onChange={(e) => handleUpdateElement(selectedId, { barcodeType: e.target.value as any })}
                    >
                      <option value="ean13">EAN-13</option>
                      <option value="code128">Code 128</option>
                      <option value="qr">QR Code</option>
                      <option value="datamatrix">DataMatrix</option>
                    </select>
                  </div>
                  <div>
                    <label className="block text-[10px] uppercase font-bold text-gray-500 mb-1">Значение</label>
                    <input
                      type="text"
                      className="w-full bg-[#1e1e1e] border border-[#3e3e3e] rounded p-2 text-sm focus:border-blue-500 outline-none font-mono"
                      value={template.elements.find(el => el.id === selectedId)?.content}
                      onChange={(e) => handleUpdateElement(selectedId, { content: e.target.value })}
                    />
                  </div>
                </>
              )}
            </div>
          ) : (
            <div className="h-full flex flex-col items-center justify-center text-center opacity-40 py-12">
               <Plus size={48} strokeWidth={1} />
               <p className="mt-4 text-xs font-bold uppercase tracking-widest">Выберите объект для редактирования</p>
            </div>
          )}
        </div>

        {/* Layer Manager */}
        <div className="h-64 border-t border-[#3e3e3e] flex flex-col overflow-hidden">
           <div className="p-3 bg-[#3e3e3e]/30 flex items-center gap-2 text-[10px] uppercase font-black tracking-widest text-gray-500">
              <Layers size={12} /> Список слоев
           </div>
           <div className="flex-1 overflow-y-auto custom-scrollbar">
              {template.elements.slice().reverse().map((el, i) => (
                <div
                  key={el.id}
                  onClick={() => setSelectedId(el.id)}
                  className={`px-4 py-2 border-b border-[#3e3e3e]/50 text-xs flex items-center justify-between cursor-pointer transition ${selectedId === el.id ? 'bg-blue-600/20 text-white border-l-2 border-l-blue-500' : 'hover:bg-[#3e3e3e]/50'}`}
                >
                   <div className="flex items-center gap-3">
                      <span className="opacity-30 font-mono text-[9px]">{template.elements.length - i}</span>
                      <span className="capitalize">{el.type === 'gost_sign' ? 'Знак ГОСТ' : (el.type === 'barcode' ? 'Штрихкод' : (el.type === 'text' ? 'Текст' : 'Фигура'))}</span>
                   </div>
                   <ChevronRight size={14} className={selectedId === el.id ? 'text-blue-500' : 'opacity-10'} />
                </div>
              ))}
           </div>
        </div>
      </div>
    </div>
  );
};

export default Editor;
