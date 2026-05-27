from fastapi.testclient import TestClient
from app.main import app
from app.database import create_db_and_tables

client = TestClient(app)

def setup_module(module):
    create_db_and_tables()

def test_read_main():
    response = client.get("/")
    assert response.status_code == 200
    assert response.json() == {"message": "Welcome to LabelPro API"}

def test_create_product():
    response = client.post(
        "/products/",
        json={"name": "Тестовый продукт", "gost_standard": "ГОСТ 12345"}
    )
    assert response.status_code == 200
    assert response.json()["name"] == "Тестовый продукт"

def test_get_barcode():
    response = client.get("/printing/barcode?type=qr&data=test")
    assert response.status_code == 200
    assert "image" in response.json()
