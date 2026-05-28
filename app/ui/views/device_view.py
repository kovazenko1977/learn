from PySide6.QtWidgets import (
    QWidget, QVBoxLayout, QTableWidget,
    QTableWidgetItem, QPushButton, QHBoxLayout
)
from app.ui.viewmodels.device_viewmodel import DeviceViewModel

class DeviceView(QWidget):
    def __init__(self, viewmodel: DeviceViewModel):
        super().__init__()
        self.vm = viewmodel
        self.layout = QVBoxLayout(self)

        # Table
        self.table = QTableWidget(0, 4)
        self.table.setHorizontalHeaderLabels(["ID", "Name", "Type", "Address"])
        self.layout.addWidget(self.table)

        # Buttons
        btn_layout = QHBoxLayout()
        self.add_btn = QPushButton("Add Device")
        self.del_btn = QPushButton("Remove Device")
        btn_layout.addWidget(self.add_btn)
        btn_layout.addWidget(self.del_btn)
        self.layout.addLayout(btn_layout)

        self.vm.devices_changed.connect(self.update_table)

    def update_table(self):
        self.table.setRowCount(len(self.vm.devices))
        for i, dev in enumerate(self.vm.devices):
            self.table.setItem(i, 0, QTableWidgetItem(str(dev.get("id", ""))))
            self.table.setItem(i, 1, QTableWidgetItem(dev.get("name", "")))
            self.table.setItem(i, 2, QTableWidgetItem(dev.get("type", "")))
            self.table.setItem(i, 3, QTableWidgetItem(str(dev.get("address", ""))))
