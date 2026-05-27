import uvicorn
from app.api.main import app as fastapi_app
from app.core.logging_config import setup_logging

def main():
    setup_logging()
    print("Starting Orion Config Pro Web Server...")
    print("Open http://localhost:8000 in your browser.")
    uvicorn.run(fastapi_app, host="0.0.0.0", port=8000)

if __name__ == "__main__":
    main()
