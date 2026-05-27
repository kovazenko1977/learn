import React, { useState, useRef } from 'react';
import { Stage, Layer, Text, Rect, Image, Transformer } from 'react-konva';

const LabelEditor = ({ width = 400, height = 300 }) => {
  const [elements, setElements] = useState([]);
  const [selectedId, setSelectedId] = useState(null);
  const transformerRef = useRef(null);

  const handleAddText = () => {
    const newElement = {
      id: Date.now().toString(),
      type: 'text',
      x: 50,
      y: 50,
      text: 'Новый текст',
      fontSize: 20,
      draggable: true,
    };
    setElements([...elements, newElement]);
  };

  const handleAddBarcode = () => {
    const newElement = {
      id: Date.now().toString(),
      type: 'barcode',
      x: 50,
      y: 100,
      barcodeType: 'EAN13',
      data: '4811173002809',
      width: 100,
      height: 50,
      draggable: true,
    };
    setElements([...elements, newElement]);
  };

  const handleAddSign = (signName) => {
    const newElement = {
      id: Date.now().toString(),
      type: 'sign',
      x: 50,
      y: 150,
      signName: signName,
      width: 50,
      height: 50,
      draggable: true,
    };
    setElements([...elements, newElement]);
  };

  const onSelect = (id) => {
    setSelectedId(id);
  };

  return (
    <div className="flex flex-col h-full bg-gray-100">
      <div className="p-4 bg-white border-b flex flex-wrap gap-2">
        <button onClick={handleAddText} className="px-3 py-1 bg-blue-600 text-white rounded text-sm">Текст</button>
        <button onClick={handleAddBarcode} className="px-3 py-1 bg-green-600 text-white rounded text-sm">Штрихкод</button>
        <button onClick={() => handleAddSign('fragile')} className="px-3 py-1 bg-orange-600 text-white rounded text-sm">Хрупкое</button>
        <button onClick={() => handleAddSign('keep_dry')} className="px-3 py-1 bg-orange-600 text-white rounded text-sm">Беречь от влаги</button>
        <button onClick={() => setElements([])} className="px-3 py-1 bg-red-600 text-white rounded text-sm">Очистить</button>
      </div>

      <div className="flex-1 flex flex-row">
        <div className="flex-1 flex items-center justify-center p-8 overflow-auto">
          <div className="bg-white shadow-2xl" style={{ width, height }}>
            <Stage width={width} height={height} onMouseDown={(e) => {
              if (e.target === e.target.getStage()) {
                setSelectedId(null);
              }
            }}>
              <Layer>
                {elements.map((el) => {
                  if (el.type === 'text') {
                    return (
                      <Text
                        key={el.id}
                        {...el}
                        onClick={() => onSelect(el.id)}
                        onTap={() => onSelect(el.id)}
                      />
                    );
                  }
                  if (el.type === 'barcode' || el.type === 'sign') {
                    return (
                      <Rect
                        key={el.id}
                        {...el}
                        fill="lightgray"
                        stroke={selectedId === el.id ? "blue" : "black"}
                        onClick={() => onSelect(el.id)}
                        onTap={() => onSelect(el.id)}
                      />
                    );
                  }
                  return null;
                })}
                {selectedId && (
                   <Transformer
                     ref={transformerRef}
                     boundBoxFunc={(oldBox, newBox) => {
                       if (newBox.width < 5 || newBox.height < 5) {
                         return oldBox;
                       }
                       return newBox;
                     }}
                   />
                )}
              </Layer>
            </Stage>
          </div>
        </div>

        <div className="w-64 bg-white border-l p-4">
          <h2 className="font-bold mb-4">Свойства</h2>
          {selectedId ? (
            <div>
              <p className="text-sm">ID: {selectedId}</p>
              <button
                onClick={() => setElements(elements.filter(e => e.id !== selectedId))}
                className="mt-4 px-3 py-1 bg-red-100 text-red-600 rounded text-sm w-full"
              >
                Удалить элемент
              </button>
            </div>
          ) : (
            <p className="text-gray-400 italic">Выберите элемент</p>
          )}
        </div>
      </div>
    </div>
  );
};

export default LabelEditor;
