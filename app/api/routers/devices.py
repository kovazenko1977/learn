from fastapi import APIRouter, Depends
from typing import List
from sqlalchemy.ext.asyncio import AsyncSession
from app.infrastructure.database import get_db
from app.infrastructure.repositories import DeviceRepository
from app.domain.schemas import DeviceSchema
from app.domain.models import Device

router = APIRouter(prefix="/devices", tags=["devices"])

@router.get("/", response_model=List[DeviceSchema])
async def get_devices(db: AsyncSession = Depends(get_db)):
    repo = DeviceRepository(db)
    devices = await repo.get_all()
    return [DeviceSchema.model_validate(d) for d in devices]

@router.post("/", response_model=DeviceSchema)
async def create_device(device: DeviceSchema, db: AsyncSession = Depends(get_db)):
    repo = DeviceRepository(db)
    new_device = Device(
        address=device.address,
        type=device.type,
        name=device.name,
        description=device.description
    )
    created = await repo.create(new_device)
    return DeviceSchema.model_validate(created)
