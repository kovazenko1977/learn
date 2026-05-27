import asyncio
import random
import logging
from typing import List, Dict
from app.core.events import event_bus
from app.domain.models import Device
from app.infrastructure.repositories import DeviceRepository
from sqlalchemy.ext.asyncio import AsyncSession

logger = logging.getLogger("orion_config_pro.demo")

class DemoService:
    def __init__(self):
        self.is_active = False
        self._simulation_task: asyncio.Task = None

    async def start(self, db_session: AsyncSession):
        if self.is_active:
            return

        self.is_active = True
        logger.info("Demo Mode activated")

        # 1. Seed demo devices
        await self._seed_devices(db_session)

        # 2. Start background event simulation
        self._simulation_task = asyncio.create_task(self._run_simulation())

    async def stop(self):
        self.is_active = False
        if self._simulation_task:
            self._simulation_task.cancel()
            try:
                await self._simulation_task
            except asyncio.CancelledError:
                pass
        logger.info("Demo Mode deactivated")

    async def _seed_devices(self, db: AsyncSession):
        repo = DeviceRepository(db)
        existing = await repo.get_all()
        if not existing:
            demo_devices = [
                Device(name="S2000M (Demo)", type="Panel", address=127),
                Device(name="S2000-KDL (Demo)", type="Controller", address=1),
                Device(name="S2000-SP1 (Demo)", type="Relay Module", address=2),
                Device(name="S2000-AR2 (Demo)", type="Addressable Unit", address=3),
            ]
            for dev in demo_devices:
                await repo.create(dev)
            logger.info("Demo devices seeded")

    async def _run_simulation(self):
        event_types = ["ALARM", "RESTORE", "FIRE", "FAULT", "TAMPER"]
        device_addresses = [1, 2, 3]

        while self.is_active:
            await asyncio.sleep(random.randint(5, 15))

            event = {
                "type": random.choice(event_types),
                "device_addr": random.choice(device_addresses),
                "zone_id": random.randint(1, 10),
                "timestamp": asyncio.get_event_loop().time()
            }

            logger.info(f"Demo Event: {event}")
            await event_bus.publish("system_event", event)

demo_service = DemoService()
