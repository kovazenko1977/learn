# System Design: Sanatorium Booking System

## Architecture Overview

The system is a standalone PHP application designed for high performance and autonomy. It follows a modular architecture with a clear separation between the core logic and the presentation layer.

### 1. Core Logic (sanatorium-booking/core/)
The core is built using PSR-4 compliant autoloading and consists of several managers:
- **RoomManager:** Manages room status and categories.
- **BookingManager:** Handles reservation logic, price calculation, and status transitions.
- **CalendarManager:** Generates occupancy grids and date ranges.
- **PlanningManager:** Manages tasks and schedules.
- **UserManager:** Handles authentication and roles.

### 2. Data Storage (sanatorium-booking/data/)
The system uses a flat-file JSON database (`JsonStore`).
- **Concurrency:** Uses `flock()` for file-level locking to prevent data corruption during simultaneous writes.
- **Security:** Data directory is protected via `.htaccess` to prevent direct web access.

### 3. Administrative Interface (sanatorium-booking/admin/)
A desktop-first web interface using modern CSS (Mica/Glassmorphism) for management tasks.

### 4. Mobile Interface (sanatorium-booking/mobile/)
A mobile-optimized web interface designed for on-the-go staff. It provides touch-friendly interactions for common operations.

### 5. API (sanatorium-booking/api/v1.php)
A RESTful API supporting:
- Authentication (Token-based)
- Dashboard statistics
- Booking creation and management
- Service/Package catalogs

## Key Features
- **SLA & Calculations:** Automatic price calculations based on packages and durations.
- **Occupancy Engine:** Real-time availability checks and conflict prevention.
- **Unified Status Logic:** Shared state between Desktop, Mobile, and API.
