import React, { useState, useEffect } from 'react';
import { Image as KonvaImage } from 'react-konva';

const BarcodeImage = (props: any) => {
  const [image, setImage] = useState<HTMLImageElement | undefined>(undefined);

  useEffect(() => {
    const img = new window.Image();
    img.src = `/api/barcode?text=${encodeURIComponent(props.content)}&type=${props.barcodeType || 'CODE128'}`;
    img.onload = () => {
      setImage(img);
    };
  }, [props.content, props.barcodeType]);

  return <KonvaImage {...props} image={image} />;
};

export default BarcodeImage;
