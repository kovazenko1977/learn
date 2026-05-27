from PySide6.QtWidgets import QWidget, QVBoxLayout, QTableWidget, QTableWidgetItem
from app.ui.viewmodels.log_viewmodels import InputLoopViewModel, EventLogViewModel

class InputLoopView(QWidget):
    def __init__(self, vm: InputLoopViewModel):
        super().__init__()
        layout = QVBoxLayout(self)
        self.table = QTableWidget(0, 4)
        self.table.setHorizontalHeaderLabels(["ID", "Device", "Number", "Zone"])
        layout.addWidget(self.table)

class EventLogView(QWidget):
    def __init__(self, vm: EventLogViewModel):
        super().__init__()
        layout = QVBoxLayout(self)
        self.table = QTableWidget(0, 3)
        self.table.setHorizontalHeaderLabels(["Timestamp", "Type", "Description"])
        layout.addWidget(self.table)
        vm.events_changed.connect(self.update_log)

    def update_log(self):
        # Implementation for updating the table
        pass
