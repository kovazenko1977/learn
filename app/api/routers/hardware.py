from fastapi import APIRouter, HTTPException
from typing import List, Dict, Any
from app.services.hardware_service import hardware_service

router = APIRouter(prefix="/hardware", tags=["hardware"])

@router.get("/ports")
async def get_ports():
    return await hardware_service.get_available_ports()

@router.post("/connect")
async def connect_port(port_id: str):
    success = await hardware_service.connect(port_id)
    if not success:
        raise HTTPException(status_code=400, detail="Failed to connect to port")
    return {"status": "connected", "port": port_id}

@router.post("/scan")
async def scan_devices():
    devices = await hardware_service.scan_devices()
    return {"status": "completed", "found_count": len(devices), "devices": devices}

@router.post("/sync")
async def sync_config(direction: str = "read"):
    await hardware_service.sync_config(direction)
    return {"status": "success"}

@router.get("/status")
async def get_status():
    return {
        "is_connected": hardware_service.is_connected,
        "active_port": hardware_service.active_port,
        "is_scanning": hardware_service.is_scanning
    }
