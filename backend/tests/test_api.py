from fastapi.testclient import TestClient
from app.main import app, create_db_and_tables
from datetime import datetime

# Ensure tables are created for tests
create_db_and_tables()

client = TestClient(app)

def get_token():
    username = f"user_{datetime.now().timestamp()}"
    client.post("/api/users", json={
        "username": username,
        "hashed_password": "password"
    })

    response = client.post("/token", data={
        "username": username,
        "password": "password"
    })
    return response.json()["access_token"]

def test_read_products():
    token = get_token()
    response = client.get("/api/products", headers={"Authorization": f"Bearer {token}"})
    assert response.status_code == 200
    assert isinstance(response.json(), list)

def test_read_templates():
    token = get_token()
    response = client.get("/api/templates", headers={"Authorization": f"Bearer {token}"})
    assert response.status_code == 200
    assert isinstance(response.json(), list)

def test_get_signs():
    response = client.get("/api/signs")
    assert response.status_code == 200
    assert len(response.json()) > 0
