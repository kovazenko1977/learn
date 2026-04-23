# Service CRM - Documentation

## Overview
CRM system for managing service requests and maintenance tasks in organizations.

## Core Features
- **Request Lifecycle:** New, Assigned, In Work, Completed, Rejected.
- **Role-Based Access (RBAC):**
    - **Administrator:** Full system control, user management, settings.
    - **Department Head:** Request assignment, monitoring, reporting.
    - **Responsible:** Request creation, status tracking.
    - **Executor:** Task execution, status updates, comments.
- **SLA Tracking:** Deadlines calculated based on business hours (09:00-18:00) and priority.
- **Analytics:** Dashboard with workload visualization and activity logs.
- **Telegram Integration:** Notifications for request updates.
- **Storage:** JSON-based persistent storage.

## Installation
1. Upload files to a PHP-enabled server.
2. Ensure `data/` directory is writable (`chmod -R 777 data/`).
3. Default credentials: `admin` / `admin`.

## Technical Stack
- **Frontend:** Vue.js 3 (Composition API), Tailwind CSS, FontAwesome.
- **Backend:** PHP 8.x (Clean API Architecture).
- **Security:** JWT-based stateless authentication.
