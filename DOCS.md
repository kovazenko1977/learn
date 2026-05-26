# Documentation: Label Printing according to GOST 14192-96

## Overview
This application is designed to print marking labels for cargo (boxes, kegs, etc.) in compliance with GOST 14192-96.

## Label Requirements (GOST 14192-96)
Based on the standard, the label should include the following fields:

### Shipping Marks (Основные надписи)
- **Consignee (Получатель):** Full or abbreviated name of the receiver.
- **Destination (Пункт назначения):** Destination point (city, port, etc.).
- **Package Count (Количество мест):** Total number of packages in the consignment.
- **Item Number (Порядковый номер):** The number of the current package in the consignment (e.g., "1/10").

### Information Marks (Информационные надписи)
- **Gross Weight (Масса брутто):** Total weight of the package including packaging.
- **Net Weight (Масса нетто):** Weight of the product without packaging.
- **Dimensions (Габаритные размеры):** Length, width, and height of the package.

### Manipulation Signs (Манипуляционные знаки)
Standard graphical symbols to indicate handling requirements:
- Fragile (Хрупкое. Осторожно)
- Keep Dry (Беречь от влаги)
- This Way Up (Верх)

## Barcode Symbology
- **Type:** Code 128
- **Reasoning:** Code 128 is widely used in transport and logistics for its high density and ability to encode all 128 ASCII characters. It is compliant with modern logistics requirements for cargo marking in Russia.

## Implementation Details
- **Backend:** PHP 8.x
- **Barcode Generation:** `picqer/php-barcode-generator` library.
- **Frontend:** HTML5, CSS3 (with `@media print` optimization), JavaScript for preview.
- **Label Size:** Default recommended size is 100mm x 150mm.

## Examples

### Keg Marking Example (Пример маркировки кег)
- **Consignee:** ООО "Пивоварня Север"
- **Destination:** г. Новосибирск, ул. Промышленная, 45
- **Package Count:** 50
- **Item Number:** 1/50
- **Gross Weight:** 62.5 кг (для 50л кеги с пивом)
- **Net Weight:** 50.0 кг
- **Dimensions:** 40x40x60 см
- **Signs:** Keep Dry (Беречь от влаги), This Way Up (Верх)

### Keg Product Label Example (Напиток "ЧИНАЗЕС")
- **Product:** Напиток слабоалкогольный «ЧИНАЗЕС»
- **Volume:** 30 Л
- **ABV:** 5%
- **Regulatory:** СТБ 1122-2010, РЦ BY 690277551.004-2026
- **Manufacturer:** ОАО «Пищевой комбинат «Веселово»
- **Barcode:** 4811173002809

### Box Marking Example (Пример маркировки коробок)
- **Consignee:** ИП Иванов А.В.
- **Destination:** г. Екатеринбург, склад №3
- **Package Count:** 10
- **Item Number:** 5/10
- **Gross Weight:** 12.0 кг
- **Net Weight:** 10.5 кг
- **Dimensions:** 30x30x30 см
- **Signs:** Fragile (Хрупкое)
