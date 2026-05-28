import time
import logging
from typing import Dict, Any
from app.infrastructure.database import engine
from sqlalchemy import text

logger = logging.getLogger("orion_config_pro.diagnostics")

class DiagnosticService:
    async def run_diagnostics(self) -> Dict[str, Any]:
        results = {
            "timestamp": time.time(),
            "checks": {
                "database": await self._check_db(),
                "backend": "healthy",
                "uptime": self._get_uptime()
            }
        }
        return results

    async def _check_db(self) -> str:
        try:
            async with engine.connect() as conn:
                await conn.execute(text("SELECT 1"))
            return "connected"
        except Exception as e:
            logger.error(f"DB Diagnostic failed: {e}")
            return "error"

    def _get_uptime(self) -> float:
        # Simple uptime implementation
        if not hasattr(self, '_start_time'):
            self._start_time = time.time()
        return time.time() - self._start_time

diagnostic_service = DiagnosticService()
