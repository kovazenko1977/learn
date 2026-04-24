# Service CRM - Documentation

## Overview
CRM system for managing service requests and maintenance tasks in organizations.

## Core Features
- **Request Lifecycle:** Localized statuses (Новая, Назначена, В работе, Выполнено, Отклонено).
- **Role-Based Access (RBAC):**
    - **Administrator:** Full system control, user management, advanced settings.
    - **Department Head:** Drag-and-drop assignment, analytics dashboard.
    - **Responsible:** Request creation, file attachments, tracking.
    - **Executor:** Task fulfillment, reporting, interaction.
- **Dynamic SLA:** Precise deadline calculation using configurable business hours and priorities.
- **UI & UX:** Modern "Strict" corporate theme (Slate/Blue), Dark mode support, and integrated help tooltips.
- **Hybrid Storage:** Seamless transition between JSON files and MySQL database with auto-migration.
- **Analytics:** Real-time dashboard for team load and activity logging.

## Installation
1. Upload files to a PHP-enabled server (PHP 7.4+).
2. Ensure `data/` directory is writable (`chmod -R 777 data/`).
3. Default credentials: `admin` / `admin`.

## Technical Stack
- **Frontend:** Vue.js 3, Tailwind CSS (Custom "Strict" configuration), FontAwesome.
- **Backend:** PHP (API-first architecture).
- **Security:** JWT-based authentication, Sanitized IO.
- **Storage:** Atomic JSON or MySQL.
