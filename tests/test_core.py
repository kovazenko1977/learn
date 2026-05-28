import pytest
from app.core.events import EventBus

@pytest.mark.asyncio
async def test_event_bus_publish_subscribe():
    eb = EventBus()
    received = []

    async def callback(data):
        received.append(data)

    eb.subscribe("test_event", callback)
    await eb.publish("test_event", {"msg": "hello"})

    assert len(received) == 1
    assert received[0]["msg"] == "hello"
