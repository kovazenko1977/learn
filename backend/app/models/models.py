from datetime import datetime
from typing import Optional, List, Dict, Any
from sqlmodel import SQLModel, Field, Relationship, Column, JSON

class Product(SQLModel, table=True):
    id: Optional[int] = Field(default=None, primary_key=True)
    name: str
    composition: Optional[str] = None
    gost: Optional[str] = None
    manufacturer: Optional[str] = None
    volume: Optional[str] = None
    alcohol: Optional[float] = None
    sugar: Optional[str] = None
    expiry_days: Optional[int] = None
    barcode: Optional[str] = None
    category: Optional[str] = None
    created_at: datetime = Field(default_factory=datetime.utcnow)

class Template(SQLModel, table=True):
    id: Optional[int] = Field(default=None, primary_key=True)
    name: str
    category: str  # 'keg', 'box', 'alcohol', 'transport', 'pallet'
    width: float  # mm
    height: float # mm
    elements: List[Dict[str, Any]] = Field(default_factory=list, sa_column=Column(JSON))
    created_at: datetime = Field(default_factory=datetime.utcnow)
    updated_at: datetime = Field(default_factory=datetime.utcnow)

class PrintHistory(SQLModel, table=True):
    id: Optional[int] = Field(default=None, primary_key=True)
    template_id: int
    product_id: Optional[int] = None
    printed_at: datetime = Field(default_factory=datetime.utcnow)
    quantity: int
    user_id: Optional[int] = None

class User(SQLModel, table=True):
    id: Optional[int] = Field(default=None, primary_key=True)
    username: str = Field(index=True, unique=True)
    hashed_password: str
    role: str = "operator" # "admin", "manager", "operator"
    is_active: bool = True
