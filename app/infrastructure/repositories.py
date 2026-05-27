from typing import TypeVar, Generic, Type, List, Optional
from sqlalchemy.future import select
from sqlalchemy.ext.asyncio import AsyncSession
from app.domain.models import Base

T = TypeVar("T", bound=Base)

class BaseRepository(Generic[T]):
    def __init__(self, model: Type[T], session: AsyncSession):
        self.model = model
        self.session = session

    async def get_all(self) -> List[T]:
        result = await self.session.execute(select(self.model))
        return list(result.scalars().all())

    async def get_by_id(self, id: int) -> Optional[T]:
        result = await self.session.execute(select(self.model).where(self.model.id == id))
        return result.scalar_one_or_none()

    async def create(self, entity: T) -> T:
        self.session.add(entity)
        await self.session.commit()
        await self.session.refresh(entity)
        return entity

    async def update(self, entity: T) -> T:
        await self.session.commit()
        await self.session.refresh(entity)
        return entity

    async def delete(self, id: int) -> bool:
        entity = await self.get_by_id(id)
        if entity:
            await self.session.delete(entity)
            await self.session.commit()
            return True
        return False

# Specialized repositories
from app.domain.models import Device, Panel, Zone, Relay, Scenario

class DeviceRepository(BaseRepository[Device]):
    def __init__(self, session: AsyncSession):
        super().__init__(Device, session)

class PanelRepository(BaseRepository[Panel]):
    def __init__(self, session: AsyncSession):
        super().__init__(Panel, session)

class ZoneRepository(BaseRepository[Zone]):
    def __init__(self, session: AsyncSession):
        super().__init__(Zone, session)

class RelayRepository(BaseRepository[Relay]):
    def __init__(self, session: AsyncSession):
        super().__init__(Relay, session)

class ScenarioRepository(BaseRepository[Scenario]):
    def __init__(self, session: AsyncSession):
        super().__init__(Scenario, session)
