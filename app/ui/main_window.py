from PySide6.QtWidgets import (
    QMainWindow, QDockWidget, QTreeView, QTabWidget,
    QStatusBar, QToolBar, QVBoxLayout, QWidget, QMenu
)
from PySide6.QtCore import Qt
from app.ui.widgets.tree_view import DeviceTreeView
from app.ui.widgets.inspector import PropertyInspector

class MainWindow(QMainWindow):
    def __init__(self):
        super().__init__()
        self.setWindowTitle("Orion Config Pro")
        self.resize(1200, 800)

        self._init_ui()

    def _init_ui(self):
        # Central Tab Widget
        self.tabs = QTabWidget()
        self.setCentralWidget(self.tabs)

        # Tree View Dock
        self.tree_dock = QDockWidget("System Structure", self)
        self.device_tree = DeviceTreeView()
        self.tree_dock.setWidget(self.device_tree)
        self.addDockWidget(Qt.LeftDockWidgetArea, self.tree_dock)

        # Inspector Dock
        self.inspector_dock = QDockWidget("Properties", self)
        self.inspector = PropertyInspector()
        self.inspector_dock.setWidget(self.inspector)
        self.addDockWidget(Qt.RightDockWidgetArea, self.inspector_dock)

        # Toolbar
        self.toolbar = QToolBar("Main Toolbar")
        self.addToolBar(self.toolbar)
        self.toolbar.addAction("New")
        self.toolbar.addAction("Open")
        self.toolbar.addAction("Save")
        self.toolbar.addSeparator()
        self.toolbar.addAction("Read from Device")
        self.toolbar.addAction("Write to Device")

        # Status Bar
        self.setStatusBar(QStatusBar())
        self.statusBar().showMessage("Ready")

    def add_page(self, widget, title):
        self.tabs.addTab(widget, title)
