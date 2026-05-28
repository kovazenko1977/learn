from typing import List, Optional
from sqlalchemy import Column, Integer, String, Boolean, ForeignKey, Table, Text
from sqlalchemy.orm import DeclarativeBase, Mapped, mapped_column, relationship

class Base(DeclarativeBase):
    pass

# Many-to-many associations
zone_device_association = Table(
    "zone_device",
    Base.metadata,
    Column("zone_id", ForeignKey("zones.id"), primary_key=True),
    Column("device_id", ForeignKey("devices.id"), primary_key=True),
)

section_group_association = Table(
    "section_group_assoc",
    Base.metadata,
    Column("group_id", ForeignKey("section_groups.id"), primary_key=True),
    Column("zone_id", ForeignKey("zones.id"), primary_key=True),
)

class Connection(Base):
    __tablename__ = "connections"
    id: Mapped[int] = mapped_column(primary_key=True)
    name: Mapped[str] = mapped_column(String(100))
    type: Mapped[str] = mapped_column(String(20))  # SERIAL, TCP, HUB
    params: Mapped[str] = mapped_column(Text)

class Panel(Base):
    __tablename__ = "panels"
    id: Mapped[int] = mapped_column(primary_key=True)
    name: Mapped[str] = mapped_column(String(100))
    version: Mapped[str] = mapped_column(String(20))
    address: Mapped[int] = mapped_column(Integer, default=127)
    devices: Mapped[List["Device"]] = relationship(back_populates="panel")

class Device(Base):
    __tablename__ = "devices"
    id: Mapped[int] = mapped_column(primary_key=True)
    panel_id: Mapped[Optional[int]] = mapped_column(ForeignKey("panels.id"))
    address: Mapped[int] = mapped_column(Integer)
    type: Mapped[str] = mapped_column(String(50))
    name: Mapped[str] = mapped_column(String(100))
    description: Mapped[Optional[str]] = mapped_column(Text)

    panel: Mapped[Optional["Panel"]] = relationship(back_populates="devices")
    input_loops: Mapped[List["InputLoop"]] = relationship(back_populates="device")
    relays: Mapped[List["Relay"]] = relationship(back_populates="device")

class Zone(Base):
    __tablename__ = "zones"
    id: Mapped[int] = mapped_column(primary_key=True)
    number: Mapped[int] = mapped_column(Integer, unique=True)
    name: Mapped[str] = mapped_column(String(100))
    input_loops: Mapped[List["InputLoop"]] = relationship(back_populates="zone")
    groups: Mapped[List["SectionGroup"]] = relationship(secondary=section_group_association, back_populates="zones")

class SectionGroup(Base):
    __tablename__ = "section_groups"
    id: Mapped[int] = mapped_column(primary_key=True)
    name: Mapped[str] = mapped_column(String(100))
    zones: Mapped[List["Zone"]] = relationship(secondary=section_group_association, back_populates="groups")

class InputLoop(Base):
    __tablename__ = "input_loops"
    id: Mapped[int] = mapped_column(primary_key=True)
    device_id: Mapped[int] = mapped_column(ForeignKey("devices.id"))
    number: Mapped[int] = mapped_column(Integer)
    zone_id: Mapped[Optional[int]] = mapped_column(ForeignKey("zones.id"))
    type: Mapped[str] = mapped_column(String(50))
    device: Mapped["Device"] = relationship(back_populates="input_loops")
    zone: Mapped[Optional["Zone"]] = relationship(back_populates="input_loops")

class Relay(Base):
    __tablename__ = "relays"
    id: Mapped[int] = mapped_column(primary_key=True)
    device_id: Mapped[int] = mapped_column(ForeignKey("devices.id"))
    number: Mapped[int] = mapped_column(Integer)
    name: Mapped[str] = mapped_column(String(100))
    program: Mapped[int] = mapped_column(Integer, default=0)
    device: Mapped["Device"] = relationship(back_populates="relays")

class AccessLevel(Base):
    __tablename__ = "access_levels"
    id: Mapped[int] = mapped_column(primary_key=True)
    name: Mapped[str] = mapped_column(String(100))

class Key(Base):
    __tablename__ = "keys"
    id: Mapped[int] = mapped_column(primary_key=True)
    code: Mapped[str] = mapped_column(String(50), unique=True)
    type: Mapped[str] = mapped_column(String(20))
    user_name: Mapped[str] = mapped_column(String(100))
    access_level_id: Mapped[int] = mapped_column(ForeignKey("access_levels.id"))

class Scenario(Base):
    __tablename__ = "scenarios"
    id: Mapped[int] = mapped_column(primary_key=True)
    name: Mapped[str] = mapped_column(String(100))
    definition: Mapped[str] = mapped_column(Text)

class EventRename(Base):
    __tablename__ = "event_renames"
    id: Mapped[int] = mapped_column(primary_key=True)
    original_event: Mapped[str] = mapped_column(String(100))
    new_name: Mapped[str] = mapped_column(String(100))
