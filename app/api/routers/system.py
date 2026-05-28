from fastapi import APIRouter, Depends, Body, Query
from sqlalchemy.ext.asyncio import AsyncSession
from app.infrastructure.database import get_db
from app.services.demo_service import demo_service
from app.core.events import event_bus
from typing import Dict, Any, Optional

router = APIRouter(prefix="/system", tags=["system"])

@router.get("/demo/status")
async def get_demo_status():
    return {
        "active": demo_service.is_active,
        "template": demo_service.template,
        "profile": demo_service.profile
    }

@router.post("/demo/toggle")
async def toggle_demo(
    active: bool,
    template: str = Query("Apartment"),
    profile: str = Query("Random"),
    db: AsyncSession = Depends(get_db)
):
    if active:
        await demo_service.start(db, template, profile)
    else:
        await demo_service.stop()
    return await get_demo_status()

@router.post("/demo/trigger")
async def trigger_demo_scenario(scenario: str):
    """Trigger a specific emulated scenario (e.g. MASSIVE_FIRE)"""
    await demo_service.trigger_scenario(scenario)
    return {"status": "triggered", "scenario": scenario}

@router.post("/fire-event")
async def fire_event(payload: Dict[str, Any] = Body(...)):
    await event_bus.publish("system_event", payload)
    return {"status": "fired", "event": payload}
