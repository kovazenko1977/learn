from PySide6.QtWidgets import (
    QWidget, QVBoxLayout, QTableWidget,
    QTableWidgetItem, QPushButton, QHBoxLayout
)
from app.ui.viewmodels.zone_viewmodel import ZoneViewModel

class ZoneView(QWidget):
    def __init__(self, viewmodel: ZoneViewModel):
        super().__init__()
        self.vm = viewmodel
        self.layout = QVBoxLayout(self)

        self.table = QTableWidget(0, 3)
        self.table.setHorizontalHeaderLabels(["ID", "Number", "Name"])
        self.layout.addWidget(self.table)

        btn_layout = QHBoxLayout()
        self.add_btn = QPushButton("Add Zone")
        btn_layout.addWidget(self.add_btn)
        self.layout.addLayout(btn_layout)

        self.vm.zones_changed.connect(self.update_table)

    def update_table(self):
        self.table.setRowCount(len(self.vm.zones))
        for i, zone in enumerate(self.vm.zones):
            self.table.setItem(i, 0, QTableWidgetItem(str(zone.get("id", ""))))
            self.table.setItem(i, 1, QTableWidgetItem(str(zone.get("number", ""))))
            self.table.setItem(i, 2, QTableWidgetItem(zone.get("name", "")))
