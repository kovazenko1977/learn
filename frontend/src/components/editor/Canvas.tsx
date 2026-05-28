import React from 'react';
import { Stage, Layer, Rect, Text, Group, Transformer, Image as KonvaImage } from 'react-konva';
import type { LabelElement } from '../../types';
import { useVariables } from '../../hooks/useVariables';
import useImage from 'use-image';

interface CanvasProps {
  width: number;
  height: number;
  elements: LabelElement[];
  selectedId: string | null;
  onSelect: (id: string | null) => void;
  onChange: (elements: LabelElement[]) => void;
  scale: number;
  previewData?: Record<string, string>;
}

export const Canvas: React.FC<CanvasProps> = ({
  width, height, elements, selectedId, onSelect, onChange, scale, previewData
}) => {
  const mmToPx = 3.78;
  const { substitute } = useVariables();

  const handleSelect = (e: any) => {
    if (e.target === e.target.getStage()) {
      onSelect(null);
      return;
    }
  };

  return (
    <div className="canvas-container flex-1 overflow-auto flex items-center justify-center p-8 bg-[#1e1e1e]">
      <Stage
        width={width * mmToPx * scale}
        height={height * mmToPx * scale}
        onClick={handleSelect}
        scaleX={scale}
        scaleY={scale}
        className="bg-white shadow-[0_0_50px_rgba(0,0,0,0.5)]"
      >
        <Layer>
          <Rect
            width={width * mmToPx}
            height={height * mmToPx}
            fill="white"
          />
          {elements.map((el) => (
            <Element
              key={el.id}
              data={el}
              isSelected={el.id === selectedId}
              onSelect={() => onSelect(el.id)}
              onChange={(newData: LabelElement) => {
                const newElements = elements.map(item => item.id === el.id ? newData : item);
                onChange(newElements);
              }}
              mmToPx={mmToPx}
              substitute={(content: string) => substitute(content, previewData)}
            />
          ))}
        </Layer>
      </Stage>
    </div>
  );
};

const Element = ({ data, isSelected, onSelect, onChange, mmToPx, substitute }: {
    data: LabelElement,
    isSelected: boolean,
    onSelect: () => void,
    onChange: (data: LabelElement) => void,
    mmToPx: number,
    substitute: (content: string) => string
}) => {
  const shapeRef = React.useRef<any>(null);
  const trRef = React.useRef<any>(null);
  const [image] = useImage(data.src || '');

  React.useEffect(() => {
    if (isSelected && trRef.current && shapeRef.current) {
      trRef.current.nodes([shapeRef.current]);
      trRef.current.getLayer().batchDraw();
    }
  }, [isSelected]);

  const handleDragEnd = (e: any) => {
    onChange({
      ...data,
      x: e.target.x() / mmToPx,
      y: e.target.y() / mmToPx,
    });
  };

  const handleTransformEnd = () => {
    const node = shapeRef.current;
    if (!node) return;

    const scaleX = node.scaleX();
    const scaleY = node.scaleY();

    node.setAttrs({
        scaleX: 1,
        scaleY: 1
    });

    onChange({
      ...data,
      x: node.x() / mmToPx,
      y: node.y() / mmToPx,
      width: Math.max(5, (node.width() * scaleX) / mmToPx),
      height: Math.max(5, (node.height() * scaleY) / mmToPx),
      rotation: node.rotation(),
    });
  };

  const commonProps = {
    ref: shapeRef,
    x: data.x * mmToPx,
    y: data.y * mmToPx,
    rotation: data.rotation || 0,
    draggable: true,
    onClick: onSelect,
    onDragEnd: handleDragEnd,
    onTransformEnd: handleTransformEnd,
  };

  const substitutedContent = substitute(data.content);

  if (data.type === 'text') {
    return (
      <>
        <Text
          {...commonProps}
          text={substitutedContent}
          width={data.width * mmToPx}
          height={data.height * mmToPx}
          fontSize={data.fontSize}
          fontFamily={data.fontFamily || 'Arial'}
          fontStyle={data.fontStyle || ''}
          align={data.align || 'left'}
          verticalAlign="middle"
        />
        {isSelected && <Transformer ref={trRef} rotateEnabled keepRatio={false} />}
      </>
    );
  }

  if (data.type === 'barcode' || data.type === 'sign' || data.type === 'image') {
      return (
          <>
            <Group {...commonProps}>
                {data.type === 'sign' || data.type === 'image' ? (
                   image ? (
                     <KonvaImage
                        image={image}
                        width={data.width * mmToPx}
                        height={data.height * mmToPx}
                     />
                   ) : (
                     <Rect
                        width={data.width * mmToPx}
                        height={data.height * mmToPx}
                        fill="#eee"
                     />
                   )
                ) : (
                  <Rect
                      width={data.width * mmToPx}
                      height={data.height * mmToPx}
                      stroke={isSelected ? "#007acc" : "black"}
                      strokeWidth={1 / mmToPx}
                      dash={data.type === 'barcode' ? [5, 5] : []}
                  />
                )}
                {data.type === 'barcode' && (
                    <Text
                        text={`[${data.barcodeType}: ${substitutedContent}]`}
                        fontSize={8}
                        width={data.width * mmToPx}
                        align="center"
                        y={(data.height * mmToPx) / 2 - 4}
                    />
                )}
            </Group>
            {isSelected && <Transformer ref={trRef} rotateEnabled keepRatio={data.type !== 'barcode'} />}
          </>
      )
  }

  return null;
};
