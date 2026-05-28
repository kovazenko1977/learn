import json
import asyncio
from typing import Dict, Any, List, Optional
from app.core.events import event_bus

class ScenarioEngine:
    def __init__(self):
        self.scenarios: Dict[int, Dict[str, Any]] = {}
        self.running = False

    def load_scenario(self, scenario_id: int, definition: str):
        self.scenarios[scenario_id] = json.loads(definition)

    async def start(self):
        self.running = True
        event_bus.subscribe("system_event", self._handle_event)
        print("Scenario Engine started")

    async def stop(self):
        self.running = False

    async def _handle_event(self, event_data: Any):
        if not self.running:
            return

        # Simple event-driven logic execution
        # In a real app, this would evaluate the definition (blocks, conditions, etc.)
        for scenario_id, defn in self.scenarios.items():
            if self._should_trigger(defn, event_data):
                await self._execute_actions(defn.get("actions", []), event_data)

    def _should_trigger(self, definition: Dict[str, Any], event_data: Any) -> bool:
        # Placeholder for complex trigger logic (AND/OR, State Machine)
        trigger = definition.get("trigger", {})
        return trigger.get("event_type") == event_data.get("type")

    async def _execute_actions(self, actions: List[Dict[str, Any]], event_data: Any):
        for action in actions:
            action_type = action.get("type")
            if action_type == "relay_control":
                await event_bus.publish("hardware_command", {
                    "cmd": "set_relay",
                    "id": action.get("relay_id"),
                    "state": action.get("state")
                })
            elif action_type == "delay":
                await asyncio.sleep(action.get("seconds", 0))
            print(f"Executed action: {action_type}")
