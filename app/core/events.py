import asyncio
from typing import Callable, Dict, List, Any, Coroutine

class EventBus:
    def __init__(self):
        self._subscribers: Dict[str, List[Callable[[Any], Coroutine[Any, Any, None]]]] = {}

    def subscribe(self, event_type: str, callback: Callable[[Any], Coroutine[Any, Any, None]]):
        if event_type not in self._subscribers:
            self._subscribers[event_type] = []
        self._subscribers[event_type].append(callback)

    async def publish(self, event_type: str, data: Any):
        if event_type in self._subscribers:
            tasks = [callback(data) for callback in self._subscribers[event_type]]
            await asyncio.gather(*tasks)

# Global event bus instance
event_bus = EventBus()
