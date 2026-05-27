from fastapi import FastAPI
from fastapi.middleware.cors import CORSMiddleware
from app.api.routers import devices, system, scenarios
from app.infrastructure.database import init_db
from contextlib import asynccontextmanager

@asynccontextmanager
async def lifespan(app: FastAPI):
    await init_db()
    yield

app = FastAPI(title="Orion Config Pro API", lifespan=lifespan)

app.add_middleware(
    CORSMiddleware,
    allow_origins=["*"],
    allow_credentials=True,
    allow_methods=["*"],
    allow_headers=["*"],
)

app.include_router(devices.router)
app.include_router(system.router)
app.include_router(scenarios.router)

@app.get("/health")
async def health():
    return {"status": "ok"}
