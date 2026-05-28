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
        "panels": 12,
        "controllers": 60,
        "relays": 100,
        "zones": 300,
        "desc": "Крупный объект: Бизнес-центр (1000+ датчиков)"
    }
}

DEVICE_MODELS = [
    "S2000-KDL", "S2000-SP1", "S2000-ASPT", "S2000-BI",
    "S2000-Ethernet", "S2000-PGE", "S2000-BRS2", "S2000-AR2"
]

class DemoService:
    def __init__(self):
        self.is_active = False
        self.template = "Apartment"
        self.profile = "Random"
        self._simulation_task: asyncio.Task = None
        self._active_scenarios: List[Dict] = []

    async def start(self, db: AsyncSession, template: str = "Apartment", profile: str = "Random"):
        if self.is_active: await self.stop()

        self.is_active = True
        self.template = template
        self.profile = profile
        logger.info(f"DEMO START: {template} ({profile})")

        await self._clear_db(db)
        await self._seed_template(db, template)

        result = await db.execute(select(Scenario))
        self._active_scenarios = [json.loads(s.definition) for s in result.scalars().all()]

        self._simulation_task = asyncio.create_task(self._run_simulation())

    async def stop(self):
        self.is_active = False
        if self._simulation_task:
            self._simulation_task.cancel()
        logger.info("DEMO STOP")

    async def trigger_scenario(self, scenario_type: str):
        """Dynamic event bursts for specific simulation scenarios"""
        if not self.is_active: return

        logger.info(f"DEMO SCENARIO TRIGGER: {scenario_type}")
        if scenario_type == "MASSIVE_FIRE":
            # Fire in multiple zones simultaneously
            for i in range(1, 6):
                await event_bus.publish("system_event", {"type": "FIRE", "device_addr": i, "zone_id": i})
                await asyncio.sleep(0.1)
        elif scenario_type == "SYSTEM_FAULT":
            # Multiple devices go offline/fault
            for i in range(10, 15):
                await event_bus.publish("system_event", {"type": "FAULT", "device_addr": i, "zone_id": 0})
        elif scenario_type == "RESET":
            # Restore all to normal
            await event_bus.publish("system_event", {"type": "RESTORE_ALL"})

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
        al = AccessLevel(name="Администратор")
        db.add(al); await db.flush()

        for i in range(cfg["panels"]):
            db.add(Device(name=f"С2000М v4.12 #{i+1}", type="Panel", address=127-i))

        controllers = []
        for i in range(cfg["controllers"]):
            model = random.choice(DEVICE_MODELS)
            c = Device(name=f"{model} #{i+1}", type=model, address=(i % 110) + 1)
            db.add(c); controllers.append(c)
        await db.flush()

        for i in range(cfg["relays"]):
            r_dev = Device(name=f"С2000-СП1 #{i+1}", type="Relay Module", address=80+(i%30))
            db.add(r_dev); await db.flush()
            db.add(Relay(device_id=r_dev.id, number=1, name=f"Выход {i+1}", program=1))

        for i in range(cfg["zones"]):
            z = Zone(number=i+1, name=f"Помещение {i+101}")
            db.add(z); await db.flush()
            ctrl = random.choice(controllers)
            db.add(InputLoop(device_id=ctrl.id, number=(i%127)+1, zone_id=z.id, type=random.choice(["Fire", "Smoke", "Intrusion"])))

        db.add(Scenario(
            name="Автоматическое пожаротушение",
            definition=json.dumps({
                "trigger": {"event_type": "FIRE"},
                "actions": [
                    {"type": "relay_control", "relay_id": 1, "state": "ON"},
                    {"type": "delay", "seconds": 3},
                    {"type": "relay_control", "relay_id": 1, "state": "OFF"}
                ]
            })
        ))
        await db.commit()

    async def _run_simulation(self):
        while self.is_active:
            wait = 1 if self.template == "Large Building" else 5
            await asyncio.sleep(random.randint(wait, wait*5))
            etype = "ALARM"
            if self.profile == "Fire": etype = "FIRE"
            elif self.profile == "Fault": etype = "FAULT"
            elif self.profile == "Random": etype = random.choice(["ALARM", "FIRE", "FAULT", "RESTORE", "TAMPER"])

            await event_bus.publish("system_event", {
                "type": etype,
                "device_addr": random.randint(1, 127),
                "zone_id": random.randint(1, 100),
                "timestamp": asyncio.get_event_loop().time()
            })

demo_service = DemoService()
event_bus.subscribe("system_event", demo_service._on_event)
