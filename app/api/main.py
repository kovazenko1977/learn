from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.api.routers import devices
from app.infrastructure.database import init_db
from contextlib import asynccontextmanager

@asynccontextmanager
async def lifespan(app: FastAPI):
    # Initialize DB on startup
    await init_db()
    yield

app = FastAPI(title="Orion Config Pro API", lifespan=lifespan)

# Allow CORS for PHP frontend (usually on port 80 or 8080)
app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

# Routes
app.include_router(devices.router)

@app.get("/health")
async def health():
    return {"status": "ok"}
