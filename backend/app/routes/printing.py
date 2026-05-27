from fastapi import APIRouter, Depends, Response
from ..utils.barcodes import get_barcode_image_base64
from ..utils.pdf_gen import create_label_pdf
from ..models import Template, Product
from ..database import get_session
from sqlmodel import Session
from typing import Optional

router = APIRouter(prefix="/printing", tags=["printing"])

@router.get("/barcode")
def get_barcode(type: str, data: str):
    try:
        base64_img = get_barcode_image_base64(type, data)
        return {"image": base64_img}
    except Exception as e:
        return {"error": str(e)}, 400

@router.post("/preview")
def preview_label(template_id: int, product_id: Optional[int] = None, session: Session = Depends(get_session)):
    template = session.get(Template, template_id)
    product = session.get(Product, product_id) if product_id else None

    data = {}
    if product:
        data = {
            "PRODUCT": product.name,
            "COMPOSITION": product.composition or "",
            "BARCODE": product.barcode_ean13 or "",
        }

    elements = template.layout.get('elements', [])
    pdf_bytes = create_label_pdf(template.width_mm, template.height_mm, elements, data)

    return Response(content=pdf_bytes, media_type="application/pdf")
