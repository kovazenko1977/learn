from reportlab.pdfgen import canvas
from reportlab.lib.units import mm
from reportlab.pdfbase import pdfmetrics
from reportlab.pdfbase.ttfonts import TTFont
from io import BytesIO
import os

# Register Russian-supporting font
FONT_PATH = os.path.join(os.path.dirname(__file__), "..", "..", "assets", "fonts", "Roboto-Regular.ttf")
if os.path.exists(FONT_PATH):
    pdfmetrics.registerFont(TTFont('Roboto', FONT_PATH))
    DEFAULT_FONT = 'Roboto'
else:
    DEFAULT_FONT = 'Helvetica'

def create_label_pdf(width_mm: float, height_mm: float, elements: list, data: dict):
    buffer = BytesIO()
    p = canvas.Canvas(buffer, pagesize=(width_mm * mm, height_mm * mm))

    for el in elements:
        el_type = el.get('type')
        x = el.get('x', 0) * mm
        y = (height_mm - el.get('y', 0) - el.get('height', 0)) * mm
        w = el.get('width', 0) * mm
        h = el.get('height', 0) * mm
        content = str(el.get('content', ''))

        if isinstance(content, str):
            for key, val in data.items():
                content = content.replace(f"{{{key}}}", str(val))

        if el_type == 'text':
            font_size = el.get('fontSize', 10)
            p.setFont(DEFAULT_FONT, font_size)
            p.drawString(x, y + h - (font_size * 0.8)) # Adjust for baseline
        elif el_type == 'image' or el_type == 'gost_sign':
            # Handle signs/images - in real app we'd map sign ID to actual file
            pass
        elif el_type == 'line':
            p.line(x, y, x + w, y + h)
        elif el_type == 'rect':
            p.rect(x, y, w, h)
        elif el_type == 'barcode':
            # Logic to generate barcode image and draw it
            pass

    p.showPage()
    p.save()
    return buffer.getvalue()
