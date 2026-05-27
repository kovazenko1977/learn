import sys
import asyncio
import threading
import uvicorn
from PySide6.QtWidgets import QApplication
from app.ui.main_window import MainWindow
from app.api.main import app as fastapi_app
from app.core.logging_config import setup_logging

def run_backend():
    uvicorn.run(fastapi_app, host="127.0.0.1", port=8000, log_level="info")

def main():
    setup_logging()

    # Start FastAPI in a separate thread
    backend_thread = threading.Thread(target=run_backend, daemon=True)
    backend_thread.start()

    # Start PySide6 Application
    qt_app = QApplication(sys.argv)
    window = MainWindow()
    window.show()

    sys.exit(qt_app.exec())

if __name__ == "__main__":
    main()
