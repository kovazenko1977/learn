export type ElementType = 'text' | 'barcode' | 'image' | 'gost_sign' | 'rect' | 'line';

export interface LabelElement {
  id: string;
  type: ElementType;
  x: number;
  y: number;
  width: number;
  height: number;
  content: string;
  fontSize?: number;
  barcodeType?: 'ean13' | 'code128' | 'qr' | 'datamatrix';
  rotation?: number;
  fill?: string;
  stroke?: string;
  strokeWidth?: number;
}

export interface LabelTemplate {
  id?: number;
  name: string;
  category: string;
  width_mm: number;
  height_mm: number;
  elements: LabelElement[];
}
