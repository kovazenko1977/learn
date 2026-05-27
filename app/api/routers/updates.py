from fastapi import APIRouter

router = APIRouter(prefix="/updates", tags=["updates"])

CURRENT_VERSION = "0.2.1"

@router.get("/check")
async def check_updates():
    # Simulated update check
    return {
        "current_version": CURRENT_VERSION,
        "latest_version": "0.2.5",
        "update_available": True,
        "changelog": "Added diagnostic tools and manual event triggers."
    }
