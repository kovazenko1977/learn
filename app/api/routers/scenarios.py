from fastapi import APIRouter, Depends
from typing import List
from sqlalchemy.ext.asyncio import AsyncSession
from app.infrastructure.database import get_db
from app.infrastructure.repositories import ScenarioRepository
from app.domain.schemas import ScenarioSchema
import json

router = APIRouter(prefix="/scenarios", tags=["scenarios"])

@router.get("/", response_model=List[ScenarioSchema])
async def get_scenarios(db: AsyncSession = Depends(get_db)):
    repo = ScenarioRepository(db)
    items = await repo.get_all()
    results = []
    for item in items:
        # Manually construct dict from SQLAlchemy object and parse JSON definition
        results.append({
            "id": item.id,
            "name": item.name,
            "definition": json.loads(item.definition)
        })
    return results
