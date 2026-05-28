from PySide6.QtCore import QObject, Signal, Property

class RelayViewModel(QObject):
    relays_changed = Signal()
    def __init__(self):
        super().__init__()
        self._relays = []
    @Property(list, notify=relays_changed)
    def relays(self): return self._relays

class AccessViewModel(QObject):
    levels_changed = Signal()
    def __init__(self):
        super().__init__()
        self._levels = []
    @Property(list, notify=levels_changed)
    def levels(self): return self._levels

class KeyViewModel(QObject):
    keys_changed = Signal()
    def __init__(self):
        super().__init__()
        self._keys = []
    @Property(list, notify=keys_changed)
    def keys(self): return self._keys
