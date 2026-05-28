from fastapi import APIRouter
from app.services.diagnostic_service import diagnostic_service

router = APIRouter(prefix="/diagnostics", tags=["diagnostics"])

@router.get("/")
async def get_diagnostics():
    return await diagnostic_service.run_diagnostics()
