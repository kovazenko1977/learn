# Orion Config Pro

Modern configuration platform for Bolid Orion / S2000 / S2000M security systems.

## Architecture

```mermaid
graph TD
    UI[PySide6 GUI] --> VM[ViewModels]
    VM --> API[FastAPI Backend]
    API --> Repo[Repositories]
    Repo --> DB[(SQLite/SQLAlchemy)]
    API --> HW[Hardware Transport]
    HW --> Serial[RS-232/485]
    HW --> TCP[TCP/IP]

    subgraph "Core Services"
        EB[Event Bus]
        SE[Scenario Engine]
        PM[Plugin Manager]
    end
```

### Key Components

- **Domain Layer**: Entities (`Device`, `Zone`, `Relay`, etc.) and Pydantic schemas.
- **Infrastructure**: Async database management and hardware transport layer.
- **Service Layer**: Event-driven scenario processing and plugin system.
- **API**: Modular REST interface for local/remote management.
- **GUI**: MVVM-based Desktop interface with docking panels and visual editors.

## Getting Started

1. Install dependencies:
   ```bash
   pip install .
   ```
2. Run the application:
   ```bash
   python main.py
   ```

## Testing

Run tests with pytest:
```bash
pytest
```
