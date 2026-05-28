from fastapi import APIRouter, Depends
from typing import List
from sqlalchemy.ext.asyncio import AsyncSession
from app.infrastructure.database import get_db
from app.infrastructure.repositories import ZoneRepository
from app.domain.schemas import ZoneSchema

router = APIRouter(prefix="/zones", tags=["zones"])

@router.get("/", response_model=List[ZoneSchema])
async def get_zones(db: AsyncSession = Depends(get_db)):
    repo = ZoneRepository(db)
    items = await repo.get_all()
    return [ZoneSchema.model_validate(i) for i in items]
