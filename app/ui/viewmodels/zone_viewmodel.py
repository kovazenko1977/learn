from PySide6.QtCore import QObject, Signal, Property

class ZoneViewModel(QObject):
    zones_changed = Signal()

    def __init__(self):
        super().__init__()
        self._zones = []

    @Property(list, notify=zones_changed)
    def zones(self):
        return self._zones

    def add_zone(self, zone_data):
        self._zones.append(zone_data)
        self.zones_changed.emit()
