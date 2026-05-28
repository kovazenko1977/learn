from PySide6.QtWidgets import QTreeView, QVBoxLayout, QWidget
from PySide6.QtGui import QStandardItemModel, QStandardItem

class DeviceTreeView(QWidget):
    def __init__(self):
        super().__init__()
        layout = QVBoxLayout(self)
        self.tree = QTreeView()
        layout.addWidget(self.tree)

        self.model = QStandardItemModel()
        self.model.setHorizontalHeaderLabels(["System Hierarchy"])
        self.tree.setModel(self.model)

        self._load_dummy_data()

    def _load_dummy_data(self):
        root = self.model.invisibleRootItem()

        com_port = QStandardItem("COM1 (Orion)")
        root.appendRow(com_port)

        panel = QStandardItem("S2000M v4.12")
        com_port.appendRow(panel)

        devices = QStandardItem("Devices")
        panel.appendRow(devices)
        devices.appendRow(QStandardItem("S2000-KDL (Addr: 1)"))
        devices.appendRow(QStandardItem("S2000-SP1 (Addr: 2)"))

        self.tree.expandAll()
