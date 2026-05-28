import React, { useState } from 'react';
import { Type, Square, Minus, Barcode, Image as ImageIcon, ShieldAlert, ZoomIn, ZoomOut, Save, FilePlus, Download, Grid } from 'lucide-react';
import axios from 'axios';
import Canvas from './Canvas';
import PropertiesPanel from './PropertiesPanel';
import { LabelElement } from '../types/label';

const Editor = () => {
  const [elements, setElements] = useState<LabelElement[]>([]);
  const [selectedId, setSelectedId] = useState<string | null>(null);
  const [zoom, setZoom] = useState(1);
  const [canvasSize, setCanvasSize] = useState({ width: 400, height: 600 });
  const [isPrinting, setIsPrinting] = useState(false);
  const [showGrid, setShowGrid] = useState(true);

  const addElement = (type: LabelElement['type']) => {
    const newEl: LabelElement = {
      id: Math.random().toString(36).substr(2, 9),
      type,
      x: 50,
      y: 50,
      width: type === 'text' ? 150 : (type === 'barcode' ? 150 : 50),
      height: type === 'text' ? 30 : (type === 'barcode' ? 60 : 50),
      rotation: 0,
      content: type === 'text' ? 'Новый текст' : (type === 'barcode' ? '12345678' : (type === 'sign' ? 'fragile' : '')),
      fill: type === 'text' ? '#000000' : 'transparent',
      stroke: '#000000',
      strokeWidth: 1,
      barcodeType: type === 'barcode' ? 'CODE128' : undefined,
    };
    setElements([...elements, newEl]);
    setSelectedId(newEl.id);
  };

  const updateElement = (id: string, attrs: Partial<LabelElement>) => {
    setElements(elements.map(el => el.id === id ? { ...el, ...attrs } : el));
  };

  const saveTemplate = async () => {
     try {
       await axios.post('/api/templates', {
         name: 'Новый шаблон ' + new Date().toLocaleTimeString(),
         category: 'generic',
         width: canvasSize.width / 4,
         height: canvasSize.height / 4,
         data: elements
       }, {
         headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
       });
       alert('Шаблон сохранен');
     } catch (e) {
       console.error(e);
       alert('Ошибка сохранения');
     }
  };

  const downloadPDF = async () => {
    setIsPrinting(true);
    try {
      const response = await axios.post('/api/print', {
        template: {
          width: canvasSize.width / 4,
          height: canvasSize.height / 4,
          elements: elements.map(el => ({
             ...el,
             x: el.x / 4,
             y: el.y / 4,
             width: el.width / 4,
             height: el.height / 4,
             fontSize: el.fontSize ? el.fontSize / 4 : 10
          }))
        },
        product: { name: 'VSPRINT Тест', volume: '1.0', alcohol: '5.0' }
      }, {
        responseType: 'blob',
        headers: { Authorization: 'Bearer ' + localStorage.getItem('token') }
      });

      const url = window.URL.createObjectURL(new Blob([response.data]));
      const link = document.createElement('a');
      link.href = url;
      link.setAttribute('download', 'label.pdf');
      document.body.appendChild(link);
      link.click();
    } catch (e) {
      console.error(e);
      alert('Ошибка при генерации PDF');
    } finally {
      setIsPrinting(false);
    }
  };

  const selectedElement = elements.find(el => el.id === selectedId);

  return (
    <div className="flex-1 flex flex-col h-full overflow-hidden">
      <div className="h-12 bg-industrial-800 border-b border-industrial-700 flex items-center px-4 justify-between">
        <div className="flex items-center space-x-4">
          <div className="flex bg-industrial-900 rounded p-1">
             <button className="p-1 hover:bg-industrial-700 rounded text-gray-400" title="Новый"><FilePlus size={18}/></button>
             <button onClick={saveTemplate} className="p-1 hover:bg-industrial-700 rounded text-gray-400" title="Сохранить"><Save size={18}/></button>
          </div>
          <div className="h-4 w-[1px] bg-industrial-600"></div>
          <span className="text-sm font-medium">Новая этикетка</span>
          <div className="h-4 w-[1px] bg-industrial-600"></div>
          <div className="flex items-center space-x-2">
             <button onClick={() => setZoom(prev => Math.max(0.1, prev - 0.1))} className="p-1 hover:bg-industrial-700 rounded"><ZoomOut size={16}/></button>
             <span className="text-xs w-10 text-center">{Math.round(zoom * 100)}%</span>
             <button onClick={() => setZoom(prev => Math.min(5, prev + 0.1))} className="p-1 hover:bg-industrial-700 rounded"><ZoomIn size={16}/></button>
          </div>
          <div className="h-4 w-[1px] bg-industrial-600"></div>
          <button
            onClick={() => setShowGrid(!showGrid)}
            className={`p-1 rounded ${showGrid ? 'text-industrial-primary bg-industrial-900' : 'text-gray-500 hover:bg-industrial-700'}`}
            title="Сетка"
          >
            <Grid size={18}/>
          </button>
        </div>

        <button
          onClick={downloadPDF}
          disabled={isPrinting}
          className="flex items-center gap-2 bg-industrial-primary text-black px-4 py-1.5 rounded text-sm font-bold hover:bg-yellow-500 transition-colors disabled:opacity-50"
        >
          <Download size={16}/> {isPrinting ? 'Печать...' : 'Скачать PDF'}
        </button>
      </div>

      <div className="flex-1 flex overflow-hidden">
        <div className="w-64 bg-industrial-800 border-r border-industrial-700 p-4 flex flex-col">
           <h3 className="text-xs font-bold uppercase text-gray-500 mb-4 tracking-wider">Элементы</h3>
           <div className="grid grid-cols-2 gap-2">
              <ElementButton icon={<Type size={18}/>} label="Текст" onClick={() => addElement('text')} />
              <ElementButton icon={<Barcode size={18}/>} label="Штрихкод" onClick={() => addElement('barcode')} />
              <ElementButton icon={<ShieldAlert size={18}/>} label="Знак ГОСТ" onClick={() => addElement('sign')} />
              <ElementButton icon={<Minus size={18}/>} label="Линия" onClick={() => addElement('line')} />
              <ElementButton icon={<Square size={18}/>} label="Рамка" onClick={() => addElement('rect')} />
              <ElementButton icon={<ImageIcon size={18}/>} label="Картинка" onClick={() => addElement('image')} />
           </div>
        </div>

        <div className={`flex-1 bg-industrial-900 relative ${showGrid ? 'canvas-container' : ''} overflow-auto flex items-center justify-center p-10`}>
           <Canvas
              elements={elements}
              width={canvasSize.width}
              height={canvasSize.height}
              zoom={zoom}
              selectedId={selectedId}
              onSelect={setSelectedId}
              onUpdate={updateElement}
           />
        </div>

        <PropertiesPanel element={selectedElement} onUpdate={updateElement} />
      </div>
    </div>
  );
};

const ElementButton = ({ label, icon, onClick }: { label: string, icon: React.ReactNode, onClick: () => void }) => (
  <button
    onClick={onClick}
    className="flex flex-col items-center justify-center p-3 bg-industrial-700 rounded border border-transparent hover:border-industrial-primary transition-all text-[10px] gap-1 group"
  >
    <div className="w-8 h-8 bg-industrial-600 rounded flex items-center justify-center mb-1 group-hover:text-industrial-primary transition-colors">
       {icon}
    </div>
    {label}
  </button>
);

export default Editor;
