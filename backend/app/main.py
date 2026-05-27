from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from .database import create_db_and_tables
from .routes import products, templates, printing

app = FastAPI(title="LabelPro API", version="1.0.0")

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

@app.on_event("startup")
def on_startup():
    create_db_and_tables()

app.include_router(products.router)
app.include_router(templates.router)
app.include_router(printing.router)

@app.get("/")
def read_root():
    return {"message": "Welcome to LabelPro API"}
