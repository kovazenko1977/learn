from fastapi import APIRouter, Depends
from sqlalchemy.ext.asyncio import AsyncSession
from app.infrastructure.database import get_db
from app.services.demo_service import demo_service

router = APIRouter(prefix="/system", tags=["system"])

@router.get("/demo/status")
async def get_demo_status():
    return {"active": demo_service.is_active}

@router.post("/demo/toggle")
async def toggle_demo(active: bool, db: AsyncSession = Depends(get_db)):
    if active:
        await demo_service.start(db)
    else:
        await demo_service.stop()
    return {"active": demo_service.is_active}
