import React, { useState } from 'react';
import Canvas from './Canvas';
import type { LabelElement, LabelTemplate } from '../../types/label';

const Editor: React.FC = () => {
  const [template, setTemplate] = useState<LabelTemplate>({
    name: 'Новая этикетка',
    category: 'коробки',
    width_mm: 58,
    height_mm: 40,
    elements: [],
  });
  const [selectedId, setSelectedId] = useState<string | null>(null);

  const handleUpdateElement = (id: string, attrs: Partial<LabelElement>) => {
    setTemplate((prev) => ({
      ...prev,
      elements: prev.elements.map((el) => (el.id === id ? { ...el, ...attrs } : el)),
    }));
  };

  const addElement = (type: LabelElement['type']) => {
    const newElement: LabelElement = {
      id: Math.random().toString(36).substr(2, 9),
      type,
      x: 10,
      y: 10,
      width: type === 'text' ? 100 : 50,
      height: type === 'text' ? 20 : 50,
      content: type === 'text' ? 'Текст' : '',
      fontSize: 12,
    };
    setTemplate((prev) => ({
      ...prev,
      elements: [...prev.elements, newElement],
    }));
    setSelectedId(newElement.id);
  };

  return (
    <div className="flex h-screen w-full bg-gray-100">
      {/* Sidebar Tools */}
      <div className="w-64 bg-white border-r flex flex-col p-4 gap-4">
        <h2 className="font-bold text-lg mb-2 text-black">Инструменты</h2>
        <button
          onClick={() => addElement('text')}
          className="p-2 bg-blue-500 text-white rounded hover:bg-blue-600 transition"
        >
          Добавить Текст
        </button>
        <button
          onClick={() => addElement('rect')}
          className="p-2 bg-gray-500 text-white rounded hover:bg-gray-600 transition"
        >
          Добавить Прямоугольник
        </button>
        <button
          onClick={() => addElement('barcode')}
          className="p-2 bg-purple-500 text-white rounded hover:bg-purple-600 transition"
        >
          Добавить Штрихкод
        </button>

        <div className="mt-4">
          <h3 className="font-semibold text-sm mb-2 text-gray-600">Знаки ГОСТ</h3>
          <div className="grid grid-cols-2 gap-2">
            <button
              onClick={() => addElement('gost_sign')}
              className="p-2 border rounded hover:bg-gray-50 flex flex-col items-center gap-1 text-black"
            >
              <span className="text-xs">Хрупкое</span>
            </button>
            <button
              onClick={() => addElement('gost_sign')}
              className="p-2 border rounded hover:bg-gray-50 flex flex-col items-center gap-1 text-black"
            >
              <span className="text-xs">Влага</span>
            </button>
          </div>
        </div>
      </div>

      {/* Main Canvas Area */}
      <div className="flex-1 flex flex-col">
        <div className="h-12 bg-white border-b flex items-center px-4 justify-between">
          <span className="font-medium text-black">{template.name} ({template.width_mm}x{template.height_mm} мм)</span>
          <div className="flex gap-2">
            <button className="px-3 py-1 border rounded hover:bg-gray-50 text-black">Сохранить</button>
            <button className="px-3 py-1 bg-green-600 text-white rounded hover:bg-green-700">Печать</button>
          </div>
        </div>
        <div className="flex-1 overflow-hidden">
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

      {/* Property Panel */}
      <div className="w-80 bg-white border-l p-4 overflow-y-auto">
        <h2 className="font-bold text-lg mb-4 text-black">Свойства</h2>
        {selectedId ? (
          <div className="flex flex-col gap-4">
            {template.elements.find(el => el.id === selectedId)?.type === 'text' && (
              <>
                <div>
                  <label className="block text-sm font-medium text-gray-700">Текст</label>
                  <textarea
                    className="mt-1 block w-full border rounded-md p-2 text-black"
                    value={template.elements.find(el => el.id === selectedId)?.content}
                    onChange={(e) => handleUpdateElement(selectedId, { content: e.target.value })}
                  />
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700">Переменное поле</label>
                  <select
                    className="mt-1 block w-full border rounded-md p-2 text-black"
                    onChange={(e) => handleUpdateElement(selectedId, { content: `{${e.target.value}}` })}
                  >
                    <option value="">-- Выбрать --</option>
                    <option value="PRODUCT">Название продукта</option>
                    <option value="DATE">Дата розлива</option>
                    <option value="BATCH">Партия</option>
                    <option value="EXPIRATION">Срок годности</option>
                  </select>
                </div>
                <div>
                  <label className="block text-sm font-medium text-gray-700">Размер шрифта</label>
                  <input
                    type="number"
                    className="mt-1 block w-full border rounded-md p-2 text-black"
                    value={template.elements.find(el => el.id === selectedId)?.fontSize}
                    onChange={(e) => handleUpdateElement(selectedId, { fontSize: Number(e.target.value) })}
                  />
                </div>
              </>
            )}

            {template.elements.find(el => el.id === selectedId)?.type === 'barcode' && (
              <>
                <div>
                  <label className="block text-sm font-medium text-gray-700">Тип кода</label>
                  <select
                    className="mt-1 block w-full border rounded-md p-2 text-black"
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
                  <label className="block text-sm font-medium text-gray-700">Данные</label>
                  <input
                    type="text"
                    className="mt-1 block w-full border rounded-md p-2 text-black"
                    value={template.elements.find(el => el.id === selectedId)?.content}
                    onChange={(e) => handleUpdateElement(selectedId, { content: e.target.value })}
                  />
                </div>
              </>
            )}
            <button
              onClick={() => {
                setTemplate(prev => ({ ...prev, elements: prev.elements.filter(el => el.id !== selectedId) }));
                setSelectedId(null);
              }}
              className="mt-4 p-2 bg-red-100 text-red-600 rounded hover:bg-red-200 transition"
            >
              Удалить элемент
            </button>
          </div>
        ) : (
          <p className="text-gray-500 italic">Выберите элемент для редактирования</p>
        )}
      </div>
    </div>
  );
};

export default Editor;
