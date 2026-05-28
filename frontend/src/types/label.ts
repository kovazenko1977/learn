export interface LabelElement {
  id: string;
  type: 'text' | 'rect' | 'line' | 'barcode' | 'image' | 'sign';
  x: number;
  y: number;
  width: number;
  height: number;
  rotation: number;
  content: string;
  fill?: string;
  stroke?: string;
  strokeWidth?: number;
  fontSize?: number;
  fontFamily?: string;
  barcodeType?: 'EAN13' | 'CODE128' | 'DATAMATRIX' | 'QR';
}

export interface LabelTemplate {
  id?: string;
  name: string;
  category: string;
  width: number;
  height: number;
  elements: LabelElement[];
}
