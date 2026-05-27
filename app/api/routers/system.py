from fastapi import APIRouter, Depends, Body
from sqlalchemy.ext.asyncio import AsyncSession
from app.infrastructure.database import get_db
from app.services.demo_service import demo_service
from app.core.events import event_bus
from typing import Dict, Any

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

@router.post("/fire-event")
async def fire_event(payload: Dict[str, Any] = Body(...)):
    """Manually trigger a system event (Diagnostic/Emulation)"""
    await event_bus.publish("system_event", payload)
    return {"status": "fired", "event": payload}
