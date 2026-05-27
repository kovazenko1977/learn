from fastapi import FastAPI
from app.api.routers import devices
from app.infrastructure.database import init_db
from contextlib import asynccontextmanager

@asynccontextmanager
async def lifespan(app: FastAPI):
    # Initialize DB on startup
    await init_db()
    yield

app = FastAPI(title="Orion Config Pro API", lifespan=lifespan)

app.include_router(devices.router)

@app.get("/health")
async def health():
    return {"status": "ok"}
