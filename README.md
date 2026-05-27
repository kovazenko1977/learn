# LabelPro - Промышленная система печати этикеток

Профессиональное решение для маркировки продукции, тары и упаковки в соответствии с ГОСТ 14192-96 и международными стандартами штрихкодирования.

## Технологический стек

- **Backend:** Python 3.10+, FastAPI, SQLModel (SQLAlchemy + Pydantic)
- **Frontend:** React 18, TypeScript, Tailwind CSS, Konva.js (Canvas editor)
- **Desktop:** Electron
- **Database:** SQLite
- **PDF Generation:** ReportLab / PyFPDF
- **Barcode Generation:** python-barcode, qrcode, treepoem (DataMatrix)

## Возможности

- Визуальный Drag-and-drop редактор этикеток
- Библиотека знаков ГОСТ 14192-96
- Поддержка штрихкодов: EAN-13, Code128, QR-код, DataMatrix
- Каталог продукции с переменными полями
- Импорт из Excel/CSV
- Прямая печать на термопринтеры (Zebra, TSC, XPrinter) и обычные принтеры

## Структура проекта

- `backend/` - FastAPI приложение
- `frontend/` - React приложение
- `desktop/` - Electron обертка
- `assets/` - Статические ресурсы (знаки ГОСТ, логотипы)
- `data/` - База данных и шаблоны
