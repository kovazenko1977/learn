from sqlalchemy.ext.asyncio import create_async_engine, async_sessionmaker, AsyncSession
from app.domain.models import Base

DATABASE_URL = "sqlite+aiosqlite:///./orion_config.db"

engine = create_async_engine(DATABASE_URL, echo=True)
async_session = async_sessionmaker(engine, expire_on_commit=False)

async def init_db():
    async with engine.begin() as conn:
        await conn.run_sync(Base.metadata.create_all)

async def get_db():
    async with async_session() as session:
        yield session
