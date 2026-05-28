from PySide6.QtCore import QObject, Signal, Property

class InputLoopViewModel(QObject):
    loops_changed = Signal()
    def __init__(self):
        super().__init__()
        self._loops = []
    @Property(list, notify=loops_changed)
    def loops(self): return self._loops

class EventLogViewModel(QObject):
    events_changed = Signal()
    def __init__(self):
        super().__init__()
        self._events = []
    @Property(list, notify=events_changed)
    def events(self): return self._events

    def add_event(self, event):
        self._events.insert(0, event) # Newest first
        self.events_changed.emit()
        if len(self._events) > 1000: self._events.pop()
