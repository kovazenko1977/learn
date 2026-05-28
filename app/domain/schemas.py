from typing import List, Optional, Any, Dict
from pydantic import BaseModel, ConfigDict

class ConnectionSchema(BaseModel):
    id: Optional[int] = None
    name: str
    type: str
    params: Dict[str, Any]
    model_config = ConfigDict(from_attributes=True)

class DeviceSchema(BaseModel):
    id: Optional[int] = None
    address: int
    type: str
    name: str
    description: Optional[str] = None
    model_config = ConfigDict(from_attributes=True)

class PanelSchema(BaseModel):
    id: Optional[int] = None
    name: str
    version: str
    address: int = 127
    devices: List[DeviceSchema] = []
    model_config = ConfigDict(from_attributes=True)

class ZoneSchema(BaseModel):
    id: Optional[int] = None
    number: int
    name: str
    model_config = ConfigDict(from_attributes=True)

class InputLoopSchema(BaseModel):
    id: Optional[int] = None
    device_id: int
    number: int
    zone_id: Optional[int] = None
    type: str
    model_config = ConfigDict(from_attributes=True)

class RelaySchema(BaseModel):
    id: Optional[int] = None
    device_id: int
    number: int
    name: str
    program: int = 0
    model_config = ConfigDict(from_attributes=True)

class AccessLevelSchema(BaseModel):
    id: Optional[int] = None
    name: str
    model_config = ConfigDict(from_attributes=True)

class KeySchema(BaseModel):
    id: Optional[int] = None
    code: str
    type: str
    user_name: str
    access_level_id: int
    model_config = ConfigDict(from_attributes=True)

class ScenarioSchema(BaseModel):
    id: Optional[int] = None
    name: str
    definition: Dict[str, Any]
    model_config = ConfigDict(from_attributes=True)
