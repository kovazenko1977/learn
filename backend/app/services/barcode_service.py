import io
import base64
import barcode
from barcode.writer import SVGWriter, ImageWriter
import qrcode
import treepoem
from PIL import Image

def generate_ean13(data: str):
    EAN = barcode.get_barcode_class('ean13')
    ean = EAN(data, writer=ImageWriter())
    buffer = io.BytesIO()
    ean.write(buffer)
    return base64.b64encode(buffer.getvalue()).decode()

def generate_code128(data: str):
    CODE128 = barcode.get_barcode_class('code128')
    c128 = CODE128(data, writer=ImageWriter())
    buffer = io.BytesIO()
    c128.write(buffer)
    return base64.b64encode(buffer.getvalue()).decode()

def generate_qr(data: str):
    qr = qrcode.QRCode(version=1, box_size=10, border=5)
    qr.add_data(data)
    qr.make(fit=True)
    img = qr.make_image(fill_color="black", back_color="white")
    buffer = io.BytesIO()
    img.save(buffer)
    return base64.b64encode(buffer.getvalue()).decode()

def generate_datamatrix(data: str):
    # treepoem requires Ghostscript
    try:
        img = treepoem.generate_barcode(barcode_type='datamatrix', data=data)
        buffer = io.BytesIO()
        img.save(buffer, format="PNG")
        return base64.b64encode(buffer.getvalue()).decode()
    except Exception as e:
        # Fallback or error handling
        return str(e)
