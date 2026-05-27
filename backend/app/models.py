from datetime import datetime, timezone
from typing import Optional, List, Dict, Any
from sqlmodel import SQLModel, Field, Relationship, JSON, Column

class Product(SQLModel, table=True):
    id: Optional[int] = Field(default=None, primary_key=True)
    name: str
    description: Optional[str] = None
    composition: Optional[str] = None
    gost_standard: Optional[str] = None
    manufacturer: Optional[str] = None
    volume: Optional[str] = None
    alcohol_percent: Optional[float] = None
    sugar_percent: Optional[float] = None
    shelf_life_days: Optional[int] = None
    barcode_ean13: Optional[str] = None
    logo_path: Optional[str] = None
    warnings: Optional[str] = None
    created_at: datetime = Field(default_factory=lambda: datetime.now(timezone.utc))
    updated_at: datetime = Field(default_factory=lambda: datetime.now(timezone.utc))

class Template(SQLModel, table=True):
    id: Optional[int] = Field(default=None, primary_key=True)
    name: str
    category: str
    width_mm: float
    height_mm: float
    layout: Dict[str, Any] = Field(default={}, sa_column=Column(JSON))
    created_at: datetime = Field(default_factory=lambda: datetime.now(timezone.utc))
    updated_at: datetime = Field(default_factory=lambda: datetime.now(timezone.utc))

class PrintLog(SQLModel, table=True):
    id: Optional[int] = Field(default=None, primary_key=True)
    product_id: Optional[int] = Field(default=None, foreign_key="product.id")
    template_id: Optional[int] = Field(default=None, foreign_key="template.id")
    batch_number: Optional[str] = None
    quantity: int = 1
    printed_at: datetime = Field(default_factory=lambda: datetime.now(timezone.utc))
    operator_name: Optional[str] = None

class User(SQLModel, table=True):
    id: Optional[int] = Field(default=None, primary_key=True)
    username: str = Field(index=True, unique=True)
    hashed_password: str
    full_name: Optional[str] = None
    is_active: bool = Field(default=True)
    role: str = Field(default="operator")
