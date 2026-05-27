from reportlab.pdfgen import canvas
from reportlab.lib.pagesizes import mm
from reportlab.graphics.barcode import code128, eanbc, qr
from reportlab.graphics.shapes import Drawing, Rect
from reportlab.graphics import renderPDF
from reportlab.lib.units import mm
import io

class LabelGenerator:
    def __init__(self, width_mm, height_mm):
        self.width = width_mm * mm
        self.height = height_mm * mm
        self.buffer = io.BytesIO()
        self.c = canvas.Canvas(self.buffer, pagesize=(self.width, self.height))

    def substitute_variables(self, text, context):
        for key, value in context.items():
            placeholder = f"{{{key.upper()}}}"
            text = text.replace(placeholder, str(value or ""))
        return text

    def add_text(self, text, x, y, font="Helvetica", size=10, context=None):
        if context:
            text = self.substitute_variables(text, context)
        self.c.setFont(font, size)
        self.c.drawString(x * mm, y * mm, text)

    def add_barcode(self, type, data, x, y, w, h):
        if type == 'EAN13':
            barcode = eanbc.Ean13BarcodeWidget(data)
            d = Drawing(w*mm, h*mm)
            d.add(barcode)
            renderPDF.draw(d, self.c, x*mm, y*mm)
        elif type == 'Code128':
            barcode = code128.Code128(data, barWidth=0.5, barHeight=h*mm)
            barcode.drawOn(self.c, x*mm, y*mm)

    def add_qr(self, data, x, y, size):
        barcode = qr.QrCodeWidget(data)
        d = Drawing(size*mm, size*mm)
        d.add(barcode)
        renderPDF.draw(d, self.c, x*mm, y*mm)

    def save(self):
        self.c.showPage()
        self.c.save()
        self.buffer.seek(0)
        return self.buffer.getvalue()
