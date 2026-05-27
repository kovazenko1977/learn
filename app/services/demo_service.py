import asyncio
import random
import logging
import json
from typing import List, Dict, Optional
from app.core.events import event_bus
from app.domain.models import Device, Zone, Relay, InputLoop, AccessLevel, Key, Scenario
from sqlalchemy.ext.asyncio import AsyncSession
from sqlalchemy import select, delete

logger = logging.getLogger("orion_config_pro.demo")

TEMPLATES = {
    "Apartment": {
        "panels": 1,
        "controllers": 1,
        "relays": 2,
        "zones": 4,
        "desc": "Малая система: Квартира"
    },
    "Hospital": {
        "panels": 3,
        "controllers": 10,
        "relays": 15,
        "zones": 40,
        "desc": "Средняя система: Больница"
    },
    "Large Building": {
        "panels": 10,
        "controllers": 50,
        "relays": 100,
        "zones": 200,
        "desc": "Крупный объект: Бизнес-центр"
    }
}

class DemoService:
    def __init__(self):
        self.is_active = False
        self.template = "Apartment"
        self.profile = "Random"
        self._simulation_task: asyncio.Task = None
        self._active_scenarios: List[Dict] = []

    async def start(self, db: AsyncSession, template: str = "Apartment", profile: str = "Random"):
        if self.is_active:
            await self.stop()

        self.is_active = True
        self.template = template
        self.profile = profile
        logger.info(f"DEMO START: {template} ({profile})")

        # 1. Clear database
        await self._clear_db(db)

        # 2. Seed data
        await self._seed_template(db, template)

        # 3. Cache scenarios for logic execution
        result = await db.execute(select(Scenario))
        self._active_scenarios = [json.loads(s.definition) for s in result.scalars().all()]

        # 4. Start background tasks
        self._simulation_task = asyncio.create_task(self._run_simulation())
        # Note: Event bus subscription should only happen once or be managed
        # For simplicity in MVP, we just check is_active in _on_event

    async def stop(self):
        self.is_active = False
        if self._simulation_task:
            self._simulation_task.cancel()
            try:
                await self._simulation_task
            except asyncio.CancelledError:
                pass
        logger.info("DEMO STOP")

    async def _on_event(self, event_data: Dict):
        if not self.is_active: return
        for scenario in self._active_scenarios:
            trigger = scenario.get("trigger", {})
            if trigger.get("event_type") == event_data.get("type"):
                await self._execute_actions(scenario.get("actions", []))

    async def _execute_actions(self, actions: List[Dict]):
        for action in actions:
            if not self.is_active: break
            if action.get("type") == "relay_control":
                logger.info(f"DEMO RELAY: {action.get('relay_id')} -> {action.get('state')}")
                await event_bus.publish("system_event", {
                    "type": "RELAY_STATUS",
                    "relay_id": action.get("relay_id"),
                    "state": action.get("state")
                })
            elif action.get("type") == "delay":
                await asyncio.sleep(action.get("seconds", 0))

    async def _clear_db(self, db: AsyncSession):
        for table in [InputLoop, Relay, Zone, Device, Key, AccessLevel, Scenario]:
            await db.execute(delete(table))
        await db.commit()

    async def _seed_template(self, db: AsyncSession, template: str):
        cfg = TEMPLATES.get(template, TEMPLATES["Apartment"])

        # Access Levels
        al = AccessLevel(name="Администратор")
        db.add(al); await db.flush()

        # Panels
        for i in range(cfg["panels"]):
            db.add(Device(name=f"С2000М v4.12 #{i+1}", type="Panel", address=127-i))

        # Controllers
        controllers = []
        for i in range(cfg["controllers"]):
            c = Device(name=f"С2000-КДЛ #{i+1}", type="Controller", address=i+1)
            db.add(c); controllers.append(c)
        await db.flush() # Get IDs

        # Relays
        for i in range(cfg["relays"]):
            r_dev = Device(name=f"С2000-СП1 #{i+1}", type="Relay Module", address=80+i)
            db.add(r_dev); await db.flush()
            db.add(Relay(device_id=r_dev.id, number=1, name=f"Выход {i+1}", program=1))

        # Zones & Loops
        for i in range(cfg["zones"]):
            z = Zone(number=i+1, name=f"Помещение {i+101}")
            db.add(z); await db.flush()

            # Attach to a random controller
            ctrl = random.choice(controllers)
            db.add(InputLoop(device_id=ctrl.id, number=(i%127)+1, zone_id=z.id, type=random.choice(["Fire", "Smoke", "Intrusion"])))

        # Scenarios
        db.add(Scenario(
            name="Пожарная тревога: Оповещение",
            definition=json.dumps({
                "trigger": {"event_type": "FIRE"},
                "actions": [
                    {"type": "relay_control", "relay_id": 1, "state": "ON"},
                    {"type": "delay", "seconds": 10},
                    {"type": "relay_control", "relay_id": 1, "state": "OFF"}
                ]
            })
        ))
        db.add(Scenario(
            name="Проникновение: Блокировка",
            definition=json.dumps({
                "trigger": {"event_type": "ALARM"},
                "actions": [{"type": "relay_control", "relay_id": 2, "state": "ON"}]
            })
        ))

        await db.commit()
        logger.info(f"Seeding completed for {template}: {cfg}")

    async def _run_simulation(self):
        while self.is_active:
            # Wait time based on template size
            wait = 2 if self.template == "Large Building" else 5
            await asyncio.sleep(random.randint(wait, wait*3))

            etype = "ALARM"
            if self.profile == "Fire": etype = "FIRE"
            elif self.profile == "Fault": etype = "FAULT"
            elif self.profile == "Random": etype = random.choice(["ALARM", "FIRE", "FAULT", "RESTORE", "TAMPER"])

            await event_bus.publish("system_event", {
                "type": etype,
                "device_addr": random.randint(1, 10),
                "zone_id": random.randint(1, 20),
                "timestamp": asyncio.get_event_loop().time()
            })

demo_service = DemoService()
# Subscribe once
event_bus.subscribe("system_event", demo_service._on_event)
