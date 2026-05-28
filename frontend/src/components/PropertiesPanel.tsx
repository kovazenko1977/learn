import React from 'react';
import { LabelElement } from '../types/label';

interface PropertiesPanelProps {
  element: LabelElement | undefined;
  onUpdate: (id: string, attrs: Partial<LabelElement>) => void;
}

const GOST_SIGNS = [
  { id: 'fragile', label: 'Хрупкое' },
  { id: 'keep_dry', label: 'Беречь от влаги' },
  { id: 'top', label: 'Верх' },
  { id: 'center', label: 'Центр тяжести' },
  { id: 'no_stacking', label: 'Не штабелировать' },
];

const PropertiesPanel: React.FC<PropertiesPanelProps> = ({ element, onUpdate }) => {
  if (!element) {
    return (
      <div className="w-72 bg-industrial-800 border-l border-industrial-700 p-4 overflow-y-auto">
        <h3 className="text-xs font-bold uppercase text-gray-500 mb-4 tracking-wider">Свойства</h3>
        <div className="text-sm text-gray-400 italic">Элемент не выбран</div>
      </div>
    );
  }

  const handleChange = (key: keyof LabelElement, value: any) => {
    onUpdate(element.id, { [key]: value });
  };

  return (
    <div className="w-72 bg-industrial-800 border-l border-industrial-700 p-4 overflow-y-auto">
      <h3 className="text-xs font-bold uppercase text-gray-500 mb-4 tracking-wider">Свойства</h3>
      <div className="space-y-4">
        <Section title="Позиция и размер">
          <div className="grid grid-cols-2 gap-2">
            <PropInput label="X" value={element.x} onChange={(v: string) => handleChange('x', parseFloat(v))} />
            <PropInput label="Y" value={element.y} onChange={(v: string) => handleChange('y', parseFloat(v))} />
            <PropInput label="Ширина" value={element.width} onChange={(v: string) => handleChange('width', parseFloat(v))} />
            <PropInput label="Высота" value={element.height} onChange={(v: string) => handleChange('height', parseFloat(v))} />
          </div>
          <PropInput label="Поворот" value={element.rotation} onChange={(v: string) => handleChange('rotation', parseFloat(v))} />
        </Section>

        {element.type === 'text' && (
          <Section title="Текст">
            <PropTextArea label="Содержимое" value={element.content} onChange={(v: string) => handleChange('content', v)} />
            <div className="flex gap-2 flex-wrap mt-2">
               {['{PRODUCT}', '{DATE}', '{BATCH}', '{ALCOHOL}', '{VOLUME}'].map(tag => (
                 <button
                   key={tag}
                   onClick={() => handleChange('content', element.content + tag)}
                   className="text-[10px] bg-industrial-700 px-1 rounded hover:bg-industrial-600 border border-industrial-600"
                 >
                   {tag}
                 </button>
               ))}
            </div>
            <PropInput label="Размер шрифта" value={element.fontSize || 14} onChange={(v: string) => handleChange('fontSize', parseInt(v))} />
            <PropInput label="Цвет" value={element.fill || '#000000'} type="color" onChange={(v: string) => handleChange('fill', v)} />
          </Section>
        )}

        {element.type === 'barcode' && (
          <Section title="Штрихкод">
            <PropInput label="Данные" value={element.content} onChange={(v: string) => handleChange('content', v)} />
            <div className="flex flex-col gap-1">
              <label className="text-xs text-gray-400">Тип</label>
              <select
                className="w-full bg-industrial-900 border border-industrial-600 rounded px-2 py-1 text-sm"
                value={element.barcodeType}
                onChange={(e) => handleChange('barcodeType', e.target.value)}
              >
                <option value="CODE128">Code 128</option>
                <option value="EAN13">EAN-13</option>
                <option value="QR">QR-код</option>
                <option value="DATAMATRIX">DataMatrix</option>
              </select>
            </div>
          </Section>
        )}

        {element.type === 'sign' && (
          <Section title="Знак ГОСТ">
            <div className="flex flex-col gap-1">
              <label className="text-xs text-gray-400">Тип знака</label>
              <select
                className="w-full bg-industrial-900 border border-industrial-600 rounded px-2 py-1 text-sm"
                value={element.content}
                onChange={(e) => handleChange('content', e.target.value)}
              >
                <option value="">Выберите знак...</option>
                {GOST_SIGNS.map(s => (
                  <option key={s.id} value={s.id}>{s.label}</option>
                ))}
              </select>
            </div>
          </Section>
        )}

        {(element.type === 'rect' || element.type === 'line') && (
           <Section title="Стиль">
              <PropInput label="Цвет линии" value={element.stroke || '#000000'} type="color" onChange={(v: string) => handleChange('stroke', v)} />
              <PropInput label="Толщина" value={element.strokeWidth || 1} onChange={(v: string) => handleChange('strokeWidth', parseInt(v))} />
              {element.type === 'rect' && (
                <PropInput label="Заливка" value={element.fill || 'transparent'} type="color" onChange={(v: string) => handleChange('fill', v)} />
              )}
           </Section>
        )}
      </div>
    </div>
  );
};

const Section = ({ title, children }: { title: string, children: React.ReactNode }) => (
  <div className="border-b border-industrial-700 pb-4 mb-4">
    <div className="text-[10px] font-bold text-industrial-primary uppercase mb-3">{title}</div>
    <div className="space-y-3">{children}</div>
  </div>
);

const PropInput = ({ label, value, onChange, type = "text" }: any) => (
  <div className="flex flex-col gap-1">
    <label className="text-xs text-gray-400">{label}</label>
    <input
      type={type}
      className="w-full bg-industrial-900 border border-industrial-600 rounded px-2 py-1 text-sm focus:border-industrial-primary outline-none"
      value={value}
      onChange={(e) => onChange(e.target.value)}
    />
  </div>
);

const PropTextArea = ({ label, value, onChange }: any) => (
  <div className="flex flex-col gap-1">
    <label className="text-xs text-gray-400">{label}</label>
    <textarea
      className="w-full bg-industrial-900 border border-industrial-600 rounded px-2 py-1 text-sm focus:border-industrial-primary outline-none min-h-[60px]"
      value={value}
      onChange={(e) => onChange(e.target.value)}
    />
  </div>
);

export default PropertiesPanel;
