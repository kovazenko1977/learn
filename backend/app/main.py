import os
from fastapi import FastAPI, Depends, HTTPException, Query
from fastapi.middleware.cors import CORSMiddleware
from fastapi.staticfiles import StaticFiles
from sqlmodel import SQLModel, Session, create_engine, select
from typing import List

from jose import JWTError, jwt
from fastapi.security import OAuth2PasswordBearer, OAuth2PasswordRequestForm
from .models.models import Product, Template, PrintHistory, User
from .core.auth import verify_password, get_password_hash, create_access_token, SECRET_KEY, ALGORITHM
from .services import barcode_service, import_service, pdf_service

DATABASE_URL = "sqlite:///./backend/data/labelpro.db"
engine = create_engine(DATABASE_URL, connect_args={"check_same_thread": False})

def create_db_and_tables():
    SQLModel.metadata.create_all(engine)

def get_session():
    with Session(engine) as session:
        yield session

app = FastAPI(title="LabelPro API", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.on_event("startup")
def on_startup():
    os.makedirs("./backend/data", exist_ok=True)
    create_db_and_tables()

oauth2_scheme = OAuth2PasswordBearer(tokenUrl="token")

async def get_current_user(token: str = Depends(oauth2_scheme), session: Session = Depends(get_session)):
    credentials_exception = HTTPException(
        status_code=401,
        detail="Could not validate credentials",
        headers={"WWW-Authenticate": "Bearer"},
    )
    try:
        payload = jwt.decode(token, SECRET_KEY, algorithms=[ALGORITHM])
        username: str = payload.get("sub")
        if username is None:
            raise credentials_exception
    except JWTError:
        raise credentials_exception
    user = session.exec(select(User).where(User.username == username)).first()
    if user is None:
        raise credentials_exception
    return user

@app.post("/token")
async def login(form_data: OAuth2PasswordRequestForm = Depends(), session: Session = Depends(get_session)):
    user = session.exec(select(User).where(User.username == form_data.username)).first()
    if not user or not verify_password(form_data.password, user.hashed_password):
        raise HTTPException(status_code=400, detail="Incorrect username or password")
    access_token = create_access_token(data={"sub": user.username})
    return {"access_token": access_token, "token_type": "bearer"}

@app.post("/api/users", response_model=User)
def create_user(user_in: User, session: Session = Depends(get_session)):
    user_in.hashed_password = get_password_hash(user_in.hashed_password)
    session.add(user_in)
    session.commit()
    session.refresh(user_in)
    return user_in

# Product Endpoints
@app.get("/api/products", response_model=List[Product])
def get_products(session: Session = Depends(get_session), current_user: User = Depends(get_current_user)):
    return session.exec(select(Product)).all()

@app.post("/api/products", response_model=Product)
def create_product(product: Product, session: Session = Depends(get_session), current_user: User = Depends(get_current_user)):
    session.add(product)
    session.commit()
    session.refresh(product)
    return product

@app.get("/api/products/{product_id}", response_model=Product)
def get_product(product_id: int, session: Session = Depends(get_session), current_user: User = Depends(get_current_user)):
    product = session.get(Product, product_id)
    if not product:
        raise HTTPException(status_code=404, detail="Product not found")
    return product

@app.put("/api/products/{product_id}", response_model=Product)
def update_product(product_id: int, product_update: Product, session: Session = Depends(get_session), current_user: User = Depends(get_current_user)):
    db_product = session.get(Product, product_id)
    if not db_product:
        raise HTTPException(status_code=404, detail="Product not found")
    product_data = product_update.dict(exclude_unset=True)
    for key, value in product_data.items():
        if key != "id":
            setattr(db_product, key, value)
    session.add(db_product)
    session.commit()
    session.refresh(db_product)
    return db_product

@app.delete("/api/products/{product_id}")
def delete_product(product_id: int, session: Session = Depends(get_session), current_user: User = Depends(get_current_user)):
    product = session.get(Product, product_id)
    if not product:
        raise HTTPException(status_code=404, detail="Product not found")
    session.delete(product)
    session.commit()
    return {"ok": True}

# Template Endpoints
@app.get("/api/templates", response_model=List[Template])
def get_templates(category: str = None, session: Session = Depends(get_session), current_user: User = Depends(get_current_user)):
    statement = select(Template)
    if category:
        statement = statement.where(Template.category == category)
    return session.exec(statement).all()

@app.post("/api/templates", response_model=Template)
def create_template(template: Template, session: Session = Depends(get_session), current_user: User = Depends(get_current_user)):
    session.add(template)
    session.commit()
    session.refresh(template)
    return template

@app.get("/api/templates/{template_id}", response_model=Template)
def get_template(template_id: int, session: Session = Depends(get_session), current_user: User = Depends(get_current_user)):
    template = session.get(Template, template_id)
    if not template:
        raise HTTPException(status_code=404, detail="Template not found")
    return template

@app.put("/api/templates/{template_id}", response_model=Template)
def update_template(template_id: int, template_update: Template, session: Session = Depends(get_session), current_user: User = Depends(get_current_user)):
    db_template = session.get(Template, template_id)
    if not db_template:
        raise HTTPException(status_code=404, detail="Template not found")
    template_data = template_update.dict(exclude_unset=True)
    for key, value in template_data.items():
        if key != "id":
            setattr(db_template, key, value)
    db_template.updated_at = datetime.utcnow()
    session.add(db_template)
    session.commit()
    session.refresh(db_template)
    return db_template

@app.delete("/api/templates/{template_id}")
def delete_template(template_id: int, session: Session = Depends(get_session), current_user: User = Depends(get_current_user)):
    template = session.get(Template, template_id)
    if not template:
        raise HTTPException(status_code=404, detail="Template not found")
    session.delete(template)
    session.commit()
    return {"ok": True}

# Sign Library (Mock for now)
@app.get("/api/signs")
def get_signs():
    return [
        {"id": "fragile", "name": "Хрупкое", "icon": "/assets/signs/fragile.svg"},
        {"id": "keep_dry", "name": "Беречь от влаги", "icon": "/assets/signs/keep_dry.svg"},
        {"id": "this_way_up", "name": "Верх", "icon": "/assets/signs/up.svg"},
        {"id": "center_gravity", "name": "Центр тяжести", "icon": "/assets/signs/center.svg"},
        {"id": "do_not_roll", "name": "Не кантовать", "icon": "/assets/signs/no_roll.svg"},
    ]

# Barcode Generation API
@app.get("/api/generate/barcode")
def generate_barcode(type: str, data: str, current_user: User = Depends(get_current_user)):
    if type.upper() == 'EAN13':
        return {"image": barcode_service.generate_ean13(data)}
    elif type.upper() == 'CODE128':
        return {"image": barcode_service.generate_code128(data)}
    elif type.upper() == 'QR':
        return {"image": barcode_service.generate_qr(data)}
    elif type.upper() == 'DATAMATRIX':
        return {"image": barcode_service.generate_datamatrix(data)}
    else:
        raise HTTPException(status_code=400, detail="Unsupported barcode type")

from fastapi import UploadFile, File

@app.post("/api/import/products")
async def import_products(file: UploadFile = File(...), current_user: User = Depends(get_current_user), session: Session = Depends(get_session)):
    content = await file.read()
    if file.filename.endswith('.csv'):
        products_data = import_service.parse_csv_products(content)
    elif file.filename.endswith(('.xls', '.xlsx')):
        products_data = import_service.parse_excel_products(content)
    else:
        raise HTTPException(status_code=400, detail="Invalid file format")

    for item in products_data:
        # Map fields if necessary and create Product objects
        product = Product(**item)
        session.add(product)
    session.commit()
    return {"count": len(products_data)}

@app.post("/api/print")
async def print_label(template_id: int, product_id: int, quantity: int = 1, current_user: User = Depends(get_current_user), session: Session = Depends(get_session)):
    template = session.get(Template, template_id)
    product = session.get(Product, product_id)
    if not template or not product:
        raise HTTPException(status_code=404, detail="Template or Product not found")

    gen = pdf_service.LabelGenerator(template.width, template.height)
    product_dict = product.dict()

    for el in template.elements:
        if el['type'] == 'text':
            gen.add_text(el['text'], el['x'], el['y'], size=el.get('fontSize', 10), context=product_dict)
        # Add logic for signs and barcodes...

    pdf_bytes = gen.save()
    from fastapi.responses import Response
    return Response(content=pdf_bytes, media_type="application/pdf")

if __name__ == "__main__":
    import uvicorn
    uvicorn.run(app, host="0.0.0.0", port=8000)
