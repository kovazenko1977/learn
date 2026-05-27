import abc
import asyncio
from typing import Optional

class TransportInterface(abc.ABC):
    @abc.abstractmethod
    async def connect(self) -> bool:
        pass

    @abc.abstractmethod
    async def disconnect(self) -> None:
        pass

    @abc.abstractmethod
    async def send(self, data: bytes) -> None:
        pass

    @abc.abstractmethod
    async def receive(self, timeout: float = 1.0) -> Optional[bytes]:
        pass

class SerialTransport(TransportInterface):
    def __init__(self, port: str, baudrate: int = 9600):
        self.port = port
        self.baudrate = baudrate
        self.serial = None

    async def connect(self) -> bool:
        # In a real app, use a threadpool or async serial library
        # Simulating connection for MVP
        print(f"Connecting to Serial {self.port} at {self.baudrate}...")
        await asyncio.sleep(0.1)
        return True

    async def disconnect(self) -> None:
        print(f"Disconnecting Serial {self.port}")

    async def send(self, data: bytes) -> None:
        print(f"Serial Send: {data.hex()}")

    async def receive(self, timeout: float = 1.0) -> Optional[bytes]:
        await asyncio.sleep(0.1)
        return b"\x00" # Dummy response

class TCPTransport(TransportInterface):
    def __init__(self, host: str, port: int):
        self.host = host
        self.port = port
        self.reader: Optional[asyncio.StreamReader] = None
        self.writer: Optional[asyncio.StreamWriter] = None

    async def connect(self) -> bool:
        try:
            self.reader, self.writer = await asyncio.open_connection(self.host, self.port)
            return True
        except Exception as e:
            print(f"TCP Connection failed: {e}")
            return False

    async def disconnect(self) -> None:
        if self.writer:
            self.writer.close()
            await self.writer.wait_closed()

    async def send(self, data: bytes) -> None:
        if self.writer:
            self.writer.write(data)
            await self.writer.drain()

    async def receive(self, timeout: float = 1.0) -> Optional[bytes]:
        if self.reader:
            try:
                return await asyncio.wait_for(self.reader.read(1024), timeout)
            except asyncio.TimeoutError:
                return None
        return None
