import React, { useState, useRef, useEffect } from 'react';
import { Stage, Layer, Rect, Text, Line, Transformer, Image as KonvaImage } from 'react-konva';
import { LabelElement } from '../types/label';
import BarcodeImage from './BarcodeImage';
import SignImage from './SignImage';

interface CanvasProps {
  elements: LabelElement[];
  width: number;
  height: number;
  zoom: number;
  selectedId: string | null;
  onSelect: (id: string | null) => void;
  onUpdate: (id: string, attrs: Partial<LabelElement>) => void;
}

const Canvas: React.FC<CanvasProps> = ({ elements, width, height, zoom, selectedId, onSelect, onUpdate }) => {
  const stageRef = useRef<any>(null);
  const trRef = useRef<any>(null);

  useEffect(() => {
    if (selectedId && trRef.current) {
      const selectedNode = stageRef.current.findOne('#' + selectedId);
      if (selectedNode) {
        trRef.current.nodes([selectedNode]);
        trRef.current.getLayer()?.batchDraw();
      }
    }
  }, [selectedId]);

  const handleStageMouseDown = (e: any) => {
    if (e.target === e.target.getStage()) {
      onSelect(null);
      return;
    }
    const parent = e.target.getParent();
    const clickedOnTransformer = parent && parent.className === 'Transformer';
    if (clickedOnTransformer) {
      return;
    }
    const id = e.target.id();
    onSelect(id);
  };

  const handleDragEnd = (e: any, id: string) => {
    const node = e.target;
    // Basic snapping to 5px grid
    const snap = 5;
    const x = Math.round(node.x() / snap) * snap;
    const y = Math.round(node.y() / snap) * snap;
    onUpdate(id, { x, y });
  };

  return (
    <Stage
      width={width * zoom}
      height={height * zoom}
      scaleX={zoom}
      scaleY={zoom}
      ref={stageRef}
      onMouseDown={handleStageMouseDown}
      style={{ backgroundColor: 'white', boxShadow: '0 0 20px rgba(0,0,0,0.5)' }}
    >
      <Layer>
        {elements.map((el) => {
          const commonProps = {
            key: el.id,
            id: el.id,
            x: el.x,
            y: el.y,
            width: el.width,
            height: el.height,
            rotation: el.rotation,
            draggable: true,
            onDragEnd: (e: any) => handleDragEnd(e, el.id),
            onTransformEnd: (e: any) => {
              const node = e.target;
              onUpdate(el.id, {
                x: node.x(),
                y: node.y(),
                width: node.width() * node.scaleX(),
                height: node.height() * node.scaleY(),
                rotation: node.rotation(),
              });
              node.scaleX(1);
              node.scaleY(1);
            },
          };

          if (el.type === 'rect') {
            return (
              <Rect
                {...commonProps}
                fill={el.fill || 'transparent'}
                stroke={el.stroke || 'black'}
                strokeWidth={el.strokeWidth || 1}
              />
            );
          }
          if (el.type === 'text') {
             return (
               <Text
                 {...commonProps}
                 text={el.content}
                 fontSize={el.fontSize || 14}
                 fontFamily={el.fontFamily || 'Arial'}
                 fill={el.fill || 'black'}
               />
             );
          }
          if (el.type === 'line') {
             return (
               <Line
                 {...commonProps}
                 points={[0, 0, el.width, 0]}
                 stroke={el.stroke || 'black'}
                 strokeWidth={el.strokeWidth || 1}
               />
             );
          }
          if (el.type === 'barcode') {
             return (
               <BarcodeImage
                 {...commonProps}
                 content={el.content}
                 barcodeType={el.barcodeType}
               />
             );
          }
          if (el.type === 'sign') {
              return (
                <SignImage
                  {...commonProps}
                  content={el.content}
                />
              )
          }
          if (el.type === 'image') {
              return (
                <KonvaImage
                  {...commonProps}
                  image={undefined}
                />
              )
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
  );
};

export default Canvas;
