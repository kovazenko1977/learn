import React from 'react';
import { Stage, Layer, Rect, Text, Transformer, Image as KonvaImage } from 'react-konva';
import type { LabelElement } from '../../types/label';
import useImage from 'use-image';

const ImageElement = ({ el, commonProps }: { el: LabelElement, commonProps: any }) => {
  const [image] = useImage(el.content || '/signs/placeholder.svg');
  return <KonvaImage {...commonProps} image={image} />;
};

const BarcodeElement = ({ commonProps }: { el: LabelElement, commonProps: any }) => {
  const [image] = useImage('https://cdn-icons-png.flaticon.com/512/71/71230.png');
  return <KonvaImage {...commonProps} image={image} />;
};

interface CanvasProps {
  width: number;
  height: number;
  elements: LabelElement[];
  selectedId: string | null;
  onSelect: (id: string | null) => void;
  onUpdateElement: (id: string, attrs: Partial<LabelElement>) => void;
}

const Canvas: React.FC<CanvasProps> = ({
  width,
  height,
  elements,
  selectedId,
  onSelect,
  onUpdateElement,
}) => {
  const stageRef = React.useRef<any>(null);
  const trRef = React.useRef<any>(null);

  React.useEffect(() => {
    if (selectedId && trRef.current) {
      const selectedNode = stageRef.current.findOne('#' + selectedId);
      if (selectedNode) {
        trRef.current.nodes([selectedNode]);
        trRef.current.getLayer().batchDraw();
      }
    }
  }, [selectedId]);

  const handleStageMouseDown = (e: any) => {
    if (e.target === e.target.getStage()) {
      onSelect(null);
      return;
    }
  };

  return (
    <div className="bg-gray-200 p-8 overflow-auto flex justify-center">
      <Stage
        width={width * 3.78}
        height={height * 3.78}
        style={{ backgroundColor: 'white', boxShadow: '0 0 10px rgba(0,0,0,0.1)' }}
        onMouseDown={handleStageMouseDown}
        ref={stageRef}
      >
        <Layer>
          {elements.map((el) => {
            const commonProps = {
              key: el.id,
              id: el.id,
              x: el.x * 3.78,
              y: el.y * 3.78,
              width: el.width * 3.78,
              height: el.height * 3.78,
              rotation: el.rotation || 0,
              draggable: true,
              onClick: () => onSelect(el.id),
              onDragEnd: (e: any) => {
                onUpdateElement(el.id, {
                  x: e.target.x() / 3.78,
                  y: e.target.y() / 3.78,
                });
              },
              onTransformEnd: (e: any) => {
                const node = e.target;
                onUpdateElement(el.id, {
                  x: node.x() / 3.78,
                  y: node.y() / 3.78,
                  width: (node.width() * node.scaleX()) / 3.78,
                  height: (node.height() * node.scaleY()) / 3.78,
                });
                node.scaleX(1);
                node.scaleY(1);
              },
            };

            if (el.type === 'text') {
              return (
                <Text
                  {...commonProps}
                  text={el.content}
                  fontSize={(el.fontSize || 12) * 3.78 / 2.83}
                  fill={el.fill || 'black'}
                />
              );
            }
            if (el.type === 'rect') {
              return (
                <Rect
                  {...commonProps}
                  fill={el.fill}
                  stroke={el.stroke || 'black'}
                  strokeWidth={(el.strokeWidth || 1) * 3.78}
                />
              );
            }
            if (el.type === 'gost_sign') {
              return <ImageElement el={el} commonProps={commonProps} />;
            }
            if (el.type === 'barcode') {
              return <BarcodeElement el={el} commonProps={commonProps} />;
            }
            return null;
          })}
          {selectedId && (
            <Transformer
              ref={trRef}
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
  );
};

export default Canvas;
