import React, { useState, useEffect } from 'react';
import { Image as KonvaImage } from 'react-konva';

const SignImage = (props: any) => {
  const [image, setImage] = useState<HTMLImageElement | undefined>(undefined);

  useEffect(() => {
    if (!props.content) return;
    const img = new window.Image();
    img.src = `/assets/signs/${props.content}.svg`;
    img.onload = () => {
      setImage(img);
    };
  }, [props.content]);

  return <KonvaImage {...props} image={image} />;
};

export default SignImage;
