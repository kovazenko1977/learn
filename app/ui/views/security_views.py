from PySide6.QtWidgets import QWidget, QVBoxLayout, QTableWidget, QTableWidgetItem
from app.ui.viewmodels.security_viewmodels import RelayViewModel, AccessViewModel, KeyViewModel

class RelayView(QWidget):
    def __init__(self, vm: RelayViewModel):
        super().__init__()
        layout = QVBoxLayout(self)
        self.table = QTableWidget(0, 4)
        self.table.setHorizontalHeaderLabels(["ID", "Name", "Device", "Program"])
        layout.addWidget(self.table)

class AccessView(QWidget):
    def __init__(self, vm: AccessViewModel):
        super().__init__()
        layout = QVBoxLayout(self)
        self.table = QTableWidget(0, 2)
        self.table.setHorizontalHeaderLabels(["ID", "Name"])
        layout.addWidget(self.table)

class KeyView(QWidget):
    def __init__(self, vm: KeyViewModel):
        super().__init__()
        layout = QVBoxLayout(self)
        self.table = QTableWidget(0, 4)
        self.table.setHorizontalHeaderLabels(["ID", "User", "Type", "Code"])
        layout.addWidget(self.table)
