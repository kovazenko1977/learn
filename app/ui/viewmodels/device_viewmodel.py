import httpx
from PySide6.QtCore import QObject, Signal, Property
from typing import List, Dict

class DeviceViewModel(QObject):
    devices_changed = Signal()
    error_occurred = Signal(str)

    def __init__(self, api_url: str = "http://localhost:8000"):
        super().__init__()
        self.api_url = api_url
        self._devices = []

    @Property(list, notify=devices_changed)
    def devices(self):
        return self._devices

    async def fetch_devices(self):
        async with httpx.AsyncClient() as client:
            try:
                response = await client.get(f"{self.api_url}/devices/")
                if response.status_code == 200:
                    self._devices = response.json()
                    self.devices_changed.emit()
                else:
                    self.error_occurred.emit(f"API Error: {response.status_code}")
            except Exception as e:
                self.error_occurred.emit(str(e))

    async def add_device(self, device_data: Dict):
        async with httpx.AsyncClient() as client:
            try:
                response = await client.post(f"{self.api_url}/devices/", json=device_data)
                if response.status_code == 200:
                    await self.fetch_devices()
                else:
                    self.error_occurred.emit(f"API Error: {response.status_code}")
            except Exception as e:
                self.error_occurred.emit(str(e))
