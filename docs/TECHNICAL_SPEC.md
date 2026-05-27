# Техническая спецификация LabelPro

## Архитектура
- **Backend**: FastAPI (Python 3.12). Используется асинхронный подход для API и синхронный для генерации PDF (ReportLab).
- **Frontend**: React + TypeScript + Vite. Рендеринг холста через Canvas API (Konva).
- **База данных**: SQLite (для desktop-версии) или PostgreSQL (для web-сервера).

## Основные модули
- `app.utils.barcodes`: Обертка над библиотеками python-barcode, treepoem и qrcode.
- `app.utils.pdf_gen`: Логика отрисовки PDF-документов по заданным координатам.
- `app.routes`: REST API эндпоинты для управления данными.

## Интеграция с принтерами
- Генерация PDF с точными размерами в мм.
- Desktop-версия (Electron) использует IPC для прямой отправки заданий на печать.
