# ALCO.BY Web Application

## Features
- **Finished Goods Accounting**: SKU, batches, balances, expiration dates.
- **Price Lists**: Segmented generation with discounts.
- **Auto-mailing**: Notifications and price list distribution.
- **Customer Personal Account**: Order history and profile management.
- **Industrial Security**: JWT Authentication with environment-based configuration.

## System Architecture
- **Backend**: NestJS (Node.js)
- **Frontend**: Next.js (React)
- **Database**: Extensible Repository Pattern (Current: Async JSON Data Provider)

## Getting Started

### Prerequisites
- Node.js (v18+)
- npm

### Installation
1. Clone the repository.
2. Install Backend dependencies:
   ```bash
   cd backend && npm install
   ```
3. Install Frontend dependencies:
   ```bash
   cd frontend && npm install
   ```

### Running the App
1. Start Backend:
   ```bash
   cd backend && npm run start
   ```
2. Start Frontend:
   ```bash
   cd frontend && npm run dev
   ```

## Configuration
Backend uses `.env` for:
- `JWT_SECRET`: Security token secret.
- `STORAGE_MODE`: `json` (currently implemented).
- `PORT`: Server port (default 3000).
