import barcode
from barcode.writer import ImageWriter
import qrcode
import treepoem
from io import BytesIO
import base64

def generate_ean13(data: str):
    EAN = barcode.get_barcode_class('ean13')
    ean = EAN(data, writer=ImageWriter())
    fp = BytesIO()
    ean.write(fp)
    return fp.getvalue()

def generate_code128(data: str):
    CODE128 = barcode.get_barcode_class('code128')
    c128 = CODE128(data, writer=ImageWriter())
    fp = BytesIO()
    c128.write(fp)
    return fp.getvalue()

def generate_qr(data: str):
    qr = qrcode.QRCode(version=1, box_size=10, border=5)
    qr.add_data(data)
    qr.make(fit=True)
    img = qr.make_image(fill_color="black", back_color="white")
    fp = BytesIO()
    img.save(fp)
    return fp.getvalue()

def generate_datamatrix(data: str):
    img = treepoem.generate_barcode(
        barcode_type='datamatrix',
        data=data,
    )
    fp = BytesIO()
    img.save(fp, format='PNG')
    return fp.getvalue()

def get_barcode_image_base64(type: str, data: str):
    if type == 'ean13':
        img_bytes = generate_ean13(data)
    elif type == 'code128':
        img_bytes = generate_code128(data)
    elif type == 'qr':
        img_bytes = generate_qr(data)
    elif type == 'datamatrix':
        img_bytes = generate_datamatrix(data)
    else:
        raise ValueError(f"Unsupported barcode type: {type}")

    return base64.b64encode(img_bytes).decode('utf-8')
