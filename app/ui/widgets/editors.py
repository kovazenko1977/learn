from PySide6.QtWidgets import QGraphicsView, QGraphicsScene, QWidget, QVBoxLayout, QLabel
from PySide6.QtCore import Qt

class ScenarioEditor(QWidget):
    def __init__(self):
        super().__init__()
        layout = QVBoxLayout(self)
        layout.addWidget(QLabel("Visual Scenario Editor (Drag & Drop Blocks)"))

        self.scene = QGraphicsScene()
        self.view = QGraphicsView(self.scene)
        self.view.setRenderHint(QGraphicsView.Antialiasing)
        layout.addWidget(self.view)

class TopologyEditor(QWidget):
    def __init__(self):
        super().__init__()
        layout = QVBoxLayout(self)
        layout.addWidget(QLabel("System Topology Visualization"))

        self.scene = QGraphicsScene()
        self.view = QGraphicsView(self.scene)
        layout.addWidget(self.view)
