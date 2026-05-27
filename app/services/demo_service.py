import asyncio
import random
import logging
import json
from typing import List, Dict
from app.core.events import event_bus
from app.domain.models import Device, Zone, Relay, InputLoop, AccessLevel, Key, Scenario
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select

logger = logging.getLogger("orion_config_pro.demo")

class DemoService:
    def __init__(self):
        self.is_active = False
        self._simulation_task: asyncio.Task = None
        self._active_scenarios: List[Dict] = []

    async def start(self, db: AsyncSession):
        if self.is_active:
            return
        self.is_active = True
        logger.info("Demo Mode activated")

        await self._seed_all(db)

        # Load scenarios into memory for simulation
        result = await db.execute(select(Scenario))
        self._active_scenarios = [json.loads(s.definition) for s in result.scalars().all()]

        self._simulation_task = asyncio.create_task(self._run_simulation())
        event_bus.subscribe("system_event", self._on_event)

    async def stop(self):
        self.is_active = False
        if self._simulation_task:
            self._simulation_task.cancel()
        logger.info("Demo Mode deactivated")

    async def _on_event(self, event_data: Dict):
        if not self.is_active: return

        for scenario in self._active_scenarios:
            trigger = scenario.get("trigger", {})
            if trigger.get("event_type") == event_data.get("type"):
                await self._execute_actions(scenario.get("actions", []))

    async def _execute_actions(self, actions: List[Dict]):
        for action in actions:
            if not self.is_active: break

            action_type = action.get("type")
            if action_type == "relay_control":
                logger.info(f"DEMO: Relay {action.get('relay_id')} set to {action.get('state')}")
                # Publish hardware feedback event
                await event_bus.publish("system_event", {
                    "type": "RELAY_STATUS",
                    "relay_id": action.get("relay_id"),
                    "state": action.get("state")
                })
            elif action_type == "delay":
                await asyncio.sleep(action.get("seconds", 0))

    async def _seed_all(self, db: AsyncSession):
        # ... (Same as before, simplified for brevity but kept in mind)
        al1 = AccessLevel(name="Полный доступ")
        db.add(al1)
        await db.flush()
        db.add(Key(code="12345", type="PIN", user_name="Администратор", access_level_id=al1.id))

        panel = Device(name="С2000М (Demo)", type="Panel", address=127)
        kdl = Device(name="С2000-КДЛ (Demo)", type="Controller", address=1)
        sp1 = Device(name="С2000-СП1 (Demo)", type="Relay Module", address=2)
        db.add_all([panel, kdl, sp1])
        await db.flush()

        z1 = Zone(number=1, name="Входная группа")
        db.add(z1); await db.flush()
        db.add(InputLoop(device_id=kdl.id, number=1, zone_id=z1.id, type="Fire"))

        r1 = Relay(device_id=sp1.id, number=1, name="Сирена", program=1)
        db.add(r1)

        s1 = Scenario(
            name="Тревога: Сирена",
            definition=json.dumps({
                "trigger": {"event_type": "ALARM"},
                "actions": [
                    {"type": "relay_control", "relay_id": 1, "state": "ON"},
                    {"type": "delay", "seconds": 5},
                    {"type": "relay_control", "relay_id": 1, "state": "OFF"}
                ]
            })
        )
        db.add(s1)
        await db.commit()

    async def _run_simulation(self):
        while self.is_active:
            await asyncio.sleep(random.randint(10, 20))
            await event_bus.publish("system_event", {
                "type": random.choice(["ALARM", "FIRE", "RESTORE"]),
                "device_addr": 1,
                "zone_id": 1
            })

demo_service = DemoService()
