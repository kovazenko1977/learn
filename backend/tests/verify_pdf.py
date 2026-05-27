import os
import sys
# Add backend to path
sys.path.append(os.path.join(os.getcwd(), 'backend'))

from app.services.pdf_service import LabelGenerator

def verify_pdf():
    print("Testing PDF Generation...")
    gen = LabelGenerator(100, 150)
    gen.add_text("ТЕСТОВАЯ ЭТИКЕТКА", 10, 140, size=14)
    gen.add_text("Продукт: {PRODUCT}", 10, 130, context={"product": "ЧИНАЗЕС ГРЕЙПФРУТ"})
    gen.add_barcode("Code128", "12345678", 10, 100, 50, 15)

    pdf_data = gen.save()

    output_path = "test_output.pdf"
    with open(output_path, "wb") as f:
        f.write(pdf_data)

    if os.path.exists(output_path) and os.path.getsize(output_path) > 0:
        print(f"SUCCESS: PDF created at {output_path} ({os.path.getsize(output_path)} bytes)")
    else:
        print("FAILED: PDF not created or empty")

if __name__ == "__main__":
    verify_pdf()
