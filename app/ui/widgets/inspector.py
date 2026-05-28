from PySide6.QtWidgets import (
    QWidget, QVBoxLayout, QFormLayout,
    QLineEdit, QSpinBox, QLabel, QGroupBox
)

class PropertyInspector(QWidget):
    def __init__(self):
        super().__init__()
        self.layout = QVBoxLayout(self)

        self.group = QGroupBox("Object Properties")
        self.form = QFormLayout(self.group)
        self.layout.addWidget(self.group)

        # Placeholder fields
        self.name_edit = QLineEdit()
        self.addr_spin = QSpinBox()
        self.addr_spin.setRange(1, 127)

        self.form.addRow("Name:", self.name_edit)
        self.form.addRow("Address:", self.addr_spin)

        self.layout.addStretch()

    def set_object(self, obj_data):
        self.name_edit.setText(obj_data.get("name", ""))
        self.addr_spin.setValue(obj_data.get("address", 1))
