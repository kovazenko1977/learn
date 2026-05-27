import asyncio
import random
import logging
from typing import List, Dict, Any
from app.core.events import event_bus
from app.infrastructure.repositories import DeviceRepository
from app.infrastructure.database import async_session
from app.services.demo_service import demo_service
from app.domain.models import Device

logger = logging.getLogger("orion_config_pro.hardware")

class HardwareService:
    def __init__(self):
        self.is_connected = False
        self.active_port = None
        self.is_scanning = False

    async def get_available_ports(self) -> List[Dict[str, str]]:
        return [
            {"id": "COM1", "name": "Serial Port 1 (RS-232)"},
            {"id": "COM3", "name": "USB-Serial Converter (RS-485)"},
            {"id": "DEMO_VIRTUAL", "name": "Demo Virtual Port (Simulated)"},
            {"id": "TCP:192.168.1.100", "name": "C3000-Hub (Ethernet)"}
        ]

    async def connect(self, port_id: str) -> bool:
        if self.is_connected and self.active_port == port_id:
            self.is_connected = False
            self.active_port = None
            return True

        logger.info(f"Connecting to {port_id}...")
        await asyncio.sleep(1.0)
        self.is_connected = True
        self.active_port = port_id
        return True

    async def scan_devices(self) -> List[Dict[str, Any]]:
        if not self.is_connected:
            raise Exception("Port not connected")

        self.is_scanning = True
        found_devices = []

        # Simulated scan addresses 1-127
        scan_range = [1, 2, 80, 127] if self.active_port == "DEMO_VIRTUAL" else []

        for addr in range(1, 128):
            if not self.is_scanning: break
            await asyncio.sleep(0.01) # Rapid probing

            if addr in scan_range:
                await asyncio.sleep(0.2) # Protocol identification
                model = "S2000-KDL" if addr < 50 else "S2000-SP1"
                if addr == 127: model = "S2000M"

                dev = {"address": addr, "model": model}
                found_devices.append(dev)
                await event_bus.publish("system_event", {"type": "DEVICE_FOUND", "device": dev})

        # Auto-provision found devices into DB if in Demo Mode
        if found_devices:
            async with async_session() as db:
                repo = DeviceRepository(db)
                for d in found_devices:
                    await repo.create(Device(
                        name=f"{d['model']} (Addr: {d['address']})",
                        type=d['model'],
                        address=d['address']
                    ))

        self.is_scanning = False
        return found_devices

    async def sync_config(self, direction: str):
        for progress in range(0, 101, 10):
            await asyncio.sleep(0.3)
            await event_bus.publish("system_event", {"type": "SYNC_PROGRESS", "value": progress, "dir": direction})

hardware_service = HardwareService()
