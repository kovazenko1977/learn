<?php
// Simple API for template storage
$dataFile = __DIR__ . '/data/templates.json';

// Ensure data directory exists
if (!is_dir(__DIR__ . '/data')) {
    mkdir(__DIR__ . '/data', 0755, true);
}

// Ensure the templates file exists
if (!file_exists($dataFile)) {
    file_put_contents($dataFile, json_encode([], JSON_UNESCAPED_UNICODE));
}

// API router
if (isset($_GET['action'])) {
    header('Content-Type: application/json; charset=utf-8');

    if ($_GET['action'] === 'get_templates') {
        $data = @file_get_contents($dataFile);
        echo $data ? $data : '[]';
        exit;
    }

    if ($_GET['action'] === 'save_template' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['name'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Invalid template data']);
            exit;
        }

        // Read current templates with lock
        $templates = [];
        $fp = fopen($dataFile, 'c+');
        if ($fp && flock($fp, LOCK_EX)) {
            $size = filesize($dataFile);
            $content = $size > 0 ? fread($fp, $size) : '[]';
            $templates = json_decode($content, true) ?: [];

            // Check if template exists to update, or insert new
            $exists = false;
            foreach ($templates as &$t) {
                if ($t['id'] === $input['id']) {
                    $t = $input;
                    $exists = true;
                    break;
                }
            }
            if (!$exists) {
                $templates[] = $input;
            }

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($templates, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            echo json_encode(['success' => true, 'templates' => $templates]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Cannot lock file']);
        }
        exit;
    }

    if ($_GET['action'] === 'delete_template' && $_SERVER['REQUEST_METHOD'] === 'POST') {
        $input = json_decode(file_get_contents('php://input'), true);
        if (!$input || !isset($input['id'])) {
            http_response_code(400);
            echo json_encode(['error' => 'Missing ID']);
            exit;
        }

        $fp = fopen($dataFile, 'c+');
        if ($fp && flock($fp, LOCK_EX)) {
            $size = filesize($dataFile);
            $content = $size > 0 ? fread($fp, $size) : '[]';
            $templates = json_decode($content, true) ?: [];

            $filtered = array_values(array_filter($templates, function($t) use ($input) {
                return $t['id'] !== $input['id'];
            }));

            ftruncate($fp, 0);
            rewind($fp);
            fwrite($fp, json_encode($filtered, JSON_UNESCAPED_UNICODE | JSON_PRETTY_PRINT));
            fflush($fp);
            flock($fp, LOCK_UN);
            fclose($fp);

            echo json_encode(['success' => true, 'templates' => $filtered]);
        } else {
            http_response_code(500);
            echo json_encode(['error' => 'Cannot lock file']);
        }
        exit;
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Конструктор этикеток на кеги - ГОСТ / СТБ РБ</title>
    <!-- Tailwind CSS -->
    <script src="https://cdn.tailwindcss.com"></script>
    <script>
        tailwind.config = {
            theme: {
                extend: {
                    colors: {
                        brand: {
                            50: '#f5f7f5',
                            100: '#e3eae3',
                            500: '#2d6a4f',
                            600: '#1b4332',
                            700: '#081c15',
                        }
                    }
                }
            }
        }
    </script>
    <!-- Google Fonts for professional typography -->
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&family=Montserrat:wght@400;500;600;700;800&family=PT+Sans+Narrow:wght@400;700&display=swap" rel="stylesheet">
    <!-- React & Babel -->
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react/18.2.0/umd/react.production.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/react-dom/18.2.0/umd/react-dom.production.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/babel-standalone/7.23.5/babel.min.js"></script>
    <!-- Barcode & QR Code Libraries -->
    <script src="https://cdn.jsdelivr.net/npm/jsbarcode@3.11.5/dist/JsBarcode.all.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/qrcode@1.4.4/build/qrcode.min.js"></script>
    <script src="https://cdnjs.cloudflare.com/ajax/libs/html2canvas/1.4.1/html2canvas.min.js"></script>
    <style>
        /* General Web UI styles */
        body {
            font-family: 'Inter', sans-serif;
            background-color: #f1f5f9;
        }
        /* Style for printing - exact A5 Landscape (1 or 2 per A4 Page) */
        @media print {
            body {
                background: white !important;
                margin: 0 !important;
                padding: 0 !important;
            }
            .no-print {
                display: none !important;
            }
            .print-container {
                display: block !important;
                margin: 0 auto !important;
                padding: 0 !important;
                box-sizing: border-box !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important;
            }
            .custom-active {
                display: block !important;
                page-break-after: avoid !important;
                page-break-before: avoid !important;
            }
            .custom-print-grid {
                display: grid !important;
                page-break-inside: avoid !important;
            }

            /* Basic landscape A5 page styling */
            .print-label {
                box-sizing: border-box !important;
                border: 1px dashed #ccc !important;
                position: relative !important;
                page-break-inside: avoid !important;
                overflow: hidden !important;
                background: white !important;
            }

            /* Layout: 1 horizontal */
            .print-layout-h1 {
                width: 210mm !important;
                height: 297mm !important;
                display: flex !important;
                align-items: center !important;
                justify-content: center !important;
            }
            .print-layout-h1 .print-label {
                width: 210mm !important;
                height: 148mm !important;
            }

            /* Layout: 2 horizontal */
            .print-layout-h2 {
                width: 210mm !important;
                height: 297mm !important;
            }
            .print-layout-h2 .print-label {
                width: 210mm !important;
                height: 148mm !important;
                margin-bottom: 0.5mm !important;
            }

            /* Layout: 2 vertical (side-by-side) on landscape A4 */
            .print-layout-v2 {
                width: 297mm !important;
                height: 210mm !important;
            }
            .flex-landscape-container {
                display: flex !important;
                flex-direction: row !important;
                width: 297mm !important;
                height: 210mm !important;
            }
            .vertical-print-col {
                width: 148.5mm !important;
                height: 210mm !important;
                position: relative !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
                border-right: 1px dashed #ccc !important;
            }
            .rotated-print-label {
                width: 210mm !important;
                height: 148mm !important;
                transform: rotate(90deg) translate(0, -210mm) !important;
                transform-origin: top left !important;
            }

            /* Layout: 3 vertical (side-by-side) on landscape A4 */
            .print-layout-v3 {
                width: 297mm !important;
                height: 210mm !important;
            }
            .flex-landscape-container-v3 {
                display: flex !important;
                flex-direction: row !important;
                width: 297mm !important;
                height: 210mm !important;
            }
            .vertical-print-col-v3 {
                width: 99mm !important;
                height: 210mm !important;
                position: relative !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
                border-right: 1px dashed #ccc !important;
            }
            .rotated-print-label-v3 {
                width: 210mm !important;
                height: 148mm !important;
                transform: scale(0.66) rotate(90deg) translate(0, -210mm) !important;
                transform-origin: top left !important;
            }

            /* Layout: 4 grid items (2x2) */
            .print-layout-grid4 {
                width: 210mm !important;
                height: 297mm !important;
            }
            .grid-4-container {
                display: grid !important;
                grid-template-columns: 105mm 105mm !important;
                grid-template-rows: 148.5mm 148.5mm !important;
                width: 210mm !important;
                height: 297mm !important;
                box-sizing: border-box !important;
            }
            .grid-item-wrapper {
                width: 105mm !important;
                height: 148.5mm !important;
                position: relative !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
                border: 1px dashed #ccc !important;
            }
            .grid-item-wrapper .print-label {
                width: 210mm !important;
                height: 148mm !important;
                transform: scale(0.5) !important;
                transform-origin: top left !important;
            }

            /* Layout: 6 grid items (2x3) */
            .print-layout-grid6 {
                width: 210mm !important;
                height: 297mm !important;
            }
            .grid-6-container {
                display: grid !important;
                grid-template-columns: 105mm 105mm !important;
                grid-template-rows: 99mm 99mm 99mm !important;
                width: 210mm !important;
                height: 297mm !important;
                box-sizing: border-box !important;
            }
            .grid-item-wrapper-6 {
                width: 105mm !important;
                height: 99mm !important;
                position: relative !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
                border: 1px dashed #ccc !important;
            }
            .grid-item-wrapper-6 .print-label {
                width: 210mm !important;
                height: 148mm !important;
                transform: scale(0.5) !important;
                transform-origin: top left !important;
            }

            /* Layout: 8 grid items (2x4) */
            .print-layout-grid8 {
                width: 210mm !important;
                height: 297mm !important;
            }
            .grid-8-container {
                display: grid !important;
                grid-template-columns: 105mm 105mm !important;
                grid-template-rows: 74.25mm 74.25mm 74.25mm 74.25mm !important;
                width: 210mm !important;
                height: 297mm !important;
                box-sizing: border-box !important;
            }
            .grid-item-wrapper-8 {
                width: 105mm !important;
                height: 74.25mm !important;
                position: relative !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
                border: 1px dashed #ccc !important;
            }
            .grid-item-wrapper-8 .print-label {
                width: 210mm !important;
                height: 148mm !important;
                transform: scale(0.5) !important;
                transform-origin: top left !important;
            }

            /* Layout: 12 grid items (3x4) */
            .print-layout-grid12 {
                width: 210mm !important;
                height: 297mm !important;
            }
            .grid-12-container {
                display: grid !important;
                grid-template-columns: 70mm 70mm 70mm !important;
                grid-template-rows: 74.25mm 74.25mm 74.25mm 74.25mm !important;
                width: 210mm !important;
                height: 297mm !important;
                box-sizing: border-box !important;
            }
            .grid-item-wrapper-12 {
                width: 70mm !important;
                height: 74.25mm !important;
                position: relative !important;
                overflow: hidden !important;
                box-sizing: border-box !important;
                border: 1px dashed #ccc !important;
            }
            .grid-item-wrapper-12 .print-label {
                width: 210mm !important;
                height: 148mm !important;
                transform: scale(0.333) !important;
                transform-origin: top left !important;
            }
        }

        /* Live Editor Zoom & Scroll adjustments */
        .label-preview-container {
            width: 210mm;
            height: 148mm;
            background: white;
            box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1);
            position: relative;
            overflow: hidden;
            transform-origin: top left;
        }

        /* High-quality narrow font for technical details (saves space, matches real labels) */
        .font-narrow {
            font-family: 'PT Sans Narrow', sans-serif;
        }

        /* Helper styling for draggable blocks in editor */
        .draggable-element {
            cursor: grab;
            transition: outline-color 0.15s, background-color 0.15s;
        }
        .draggable-element:active {
            cursor: grabbing;
        }
        .draggable-element:hover {
            outline: 1px dashed #10b981;
            background-color: rgba(16, 185, 129, 0.04);
        }
        .draggable-selected {
            outline: 2px solid #3b82f6 !important;
            background-color: rgba(59, 130, 246, 0.06) !important;
            z-index: 50 !important;
        }

        /* Grid Background pattern */
        .grid-background {
            background-size: 5mm 5mm;
            background-image: linear-gradient(to right, rgba(0, 0, 0, 0.04) 1px, transparent 1px),
                              linear-gradient(to bottom, rgba(0, 0, 0, 0.04) 1px, transparent 1px);
        }

        /* Ruler styling */
        .ruler-container {
            position: relative;
            padding-top: 25px;
            padding-left: 25px;
            box-sizing: content-box;
        }
        .hr-ruler {
            position: absolute;
            top: 0;
            left: 25px;
            width: 210mm;
            height: 25px;
            background: #f8fafc;
            border-bottom: 1px solid #cbd5e1;
            box-sizing: border-box;
        }
        .vr-ruler {
            position: absolute;
            top: 25px;
            left: 0;
            width: 25px;
            height: 148mm;
            background: #f8fafc;
            border-right: 1px solid #cbd5e1;
            box-sizing: border-box;
        }
        .ruler-tick {
            position: absolute;
            background-color: #94a3b8;
        }
        .ruler-label {
            position: absolute;
            font-size: 7.5px;
            color: #64748b;
            font-weight: 600;
            line-height: 1;
            font-family: 'Inter', sans-serif;
        }
    </style>
</head>
<body>
    <div id="root"></div>

    <script type="text/babel">
        const { useState, useEffect, useRef } = React;

        // Custom High-Quality SVG Icons
        const Icon = ({ name, className = "w-5 h-5" }) => {
            const icons = {
                beer: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <path d="M17 11h1a3 3 0 0 1 0 6h-1" />
                        <path d="M9 12v6" />
                        <path d="M13 12v6" />
                        <path d="M14 7.5a2.5 2.5 0 0 0-5 0v11a1.5 1.5 0 0 0 1.5 1.5h5a1.5 1.5 0 0 0 1.5-1.5V7.5z" />
                        <path d="M8 22h8" />
                    </svg>
                ),
                printer: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <polyline points="6 9 6 2 18 2 18 9" />
                        <path d="M6 18H4a2 2 0 0 1-2-2v-5a2 2 0 0 1 2-2h16a2 2 0 0 1 2 2v5a2 2 0 0 1-2 2h-2" />
                        <rect x="6" y="14" width="12" height="8" />
                    </svg>
                ),
                alertCircle: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <circle cx="12" cy="12" r="10" />
                        <line x1="12" y1="8" x2="12" y2="12" />
                        <line x1="12" y1="16" x2="12.01" y2="16" />
                    </svg>
                ),
                checkCircle: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <path d="M22 11.08V12a10 10 0 1 1-5.93-9.14" />
                        <polyline points="22 4 12 14.01 9 11.01" />
                    </svg>
                ),
                download: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="7 10 12 15 17 10" />
                        <line x1="12" y1="15" x2="12" y2="3" />
                    </svg>
                ),
                upload: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4" />
                        <polyline points="17 8 12 3 7 8" />
                        <line x1="12" y1="3" x2="12" y2="15" />
                    </svg>
                ),
                save: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <path d="M19 21H5a2 2 0 0 1-2-2V5a2 2 0 0 1 2-2h11l5 5v11a2 2 0 0 1-2 2z" />
                        <polyline points="17 21 17 13 7 13 7 21" />
                        <polyline points="7 3 7 8 15 8" />
                    </svg>
                ),
                trash: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <polyline points="3 6 5 6 21 6" />
                        <path d="M19 6v14a2 2 0 0 1-2 2H7a2 2 0 0 1-2-2V6m3 0V4a2 2 0 0 1 2-2h4a2 2 0 0 1 2 2v2" />
                    </svg>
                ),
                fileText: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z" />
                        <polyline points="14 2 14 8 20 8" />
                        <line x1="16" y1="13" x2="8" y2="13" />
                        <line x1="16" y1="17" x2="8" y2="17" />
                        <polyline points="10 9 9 9 8 9" />
                    </svg>
                ),
                cog: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <circle cx="12" cy="12" r="3" />
                        <path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 1 1-2.83 2.83l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-4 0v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 1 1-2.83-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1 0-4h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 1 1 2.83-2.83l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 4 0v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 1 1 2.83 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 0 4h-.09a1.65 1.65 0 0 0-1.51 1z" />
                    </svg>
                ),
                undo: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <path d="M3 7v6h6" />
                        <path d="M21 17a9 9 0 0 0-9-9 9 9 0 0 0-6 2.3L3 13" />
                    </svg>
                ),
                redo: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <path d="M21 7v6h-6" />
                        <path d="M3 17a9 9 0 0 1 9-9 9 9 0 1 1 6 2.3l3 2.7" />
                    </svg>
                ),
                grid: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <rect x="3" y="3" width="18" height="18" rx="2" />
                        <line x1="9" y1="3" x2="9" y2="21" />
                        <line x1="15" y1="3" x2="15" y2="21" />
                        <line x1="3" y1="9" x2="21" y2="9" />
                        <line x1="3" y1="15" x2="21" y2="15" />
                    </svg>
                ),
                image: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <rect x="3" y="3" width="18" height="18" rx="2" ry="2" />
                        <circle cx="9" cy="9" r="2" />
                        <path d="M21 15l-3.086-3.086a2 2 0 0 0-2.828 0L6 21" />
                    </svg>
                ),
                plus: (
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="2" strokeLinecap="round" strokeLinejoin="round" className={className}>
                        <line x1="12" y1="5" x2="12" y2="19" />
                        <line x1="5" y1="12" x2="19" y2="12" />
                    </svg>
                )
            };
            return icons[name] || <span className="text-red-500">?</span>;
        };

        // Static standard configurations
        const PRESETS = [
            {
                id: 'cider',
                name: 'Сидр Традиционный Сухой (СТБ)',
                brandName: 'МИНСК КРИСТАЛЛ ГРУПП',
                productType: 'СИДР',
                subtitle: 'ФРУКТОВО-ЯГОДНЫЙ ГАЗИРОВАННЫЙ ПОЛУСЛАДКИЙ «Эпл Бум» («Apple Boom»)',
                standard: 'СТБ 1861-2008',
                tiNumber: 'ТИ ВУ 690277551.026-2023',
                alcohol: '5,7%',
                sugar: '50 г/л',
                volume: '30 л',
                ingredients: 'Состав: виноматериал яблочный натуральный столовый, вода питьевая, сахар, пищевые добавки: регулятор кислотности лимонная кислота, антиокислитель E224, консервант E202. Пищевая ценность 100 мл продукта: углеводы - 5 г; энергетическая ценность - 55 ккал/100 мл (220 кДж/100 мл). Алкоголь противопоказан детям и подросткам до 18 лет, беременным и кормящим женщинам, лицам с заболеваниями нервной системы и внутренних органов.',
                nutrition: 'Хранить в условиях, исключающих воздействие прямого солнечного света, при температуре от 5 °С до 20 °С. После подключения ПЭТ- КЕГ к оборудованию для розлива, сидр следует хранить под давлением двуокиси углерода в течение 10 суток при температуре от 5 °С до 20 °С.',
                storage: 'Изготовитель: ОАО "Пищевой комбинат "Веселово", 222132, Республика Беларусь, Минская обл., Борисовский р-н, д. Веселово, ул. Заводская, 24. Тел.:(0177)933-400, e-mail:info@alco.by, www.alco.by',
                expiration: 'СРОК ГОДНОСТИ: 4 МЕСЯЦА С ДАТЫ РОЗЛИВА.',
                warningText: 'ЧРЕЗМЕРНОЕ УПОТРЕБЛЕНИЕ АЛКОГОЛЯ ВРЕДИТ ВАШЕМУ ЗДОРОВЬЮ',
                barcode: '4811173002052',
                barcodeFormat: 'EAN13',
                qrCode: 'https://iz.by/cider-apple-boom-30l',
                eacActive: true,
                foodActive: true,
                petActive: true,
                warningHeightPercent: 12,
                customImages: [], // Holds array of { id, src, x, y, width, height, opacity }
                elements: {
                    header: { x: 105, y: 4, size: 10, visible: true, bold: true },
                    title: { x: 105, y: 12, size: 34, visible: true, bold: true },
                    subtitle: { x: 105, y: 26, size: 8, visible: true, bold: true },
                    stats: { x: 4, y: 10, size: 12, visible: true },
                    icons: { x: 178, y: 5, size: 38, visible: true },
                    body: { x: 6, y: 52, size: 6.5, visible: true },
                    barcode: { x: 140, y: 88, size: 40, visible: true },
                    qrcode: { x: 182, y: 15, size: 22, visible: true }
                }
            },
            {
                id: 'beer',
                name: 'Пиво Светлое Классическое (ГОСТ)',
                brandName: 'БРЕСТСКОЕ ПИВО',
                productType: 'ПИВО',
                subtitle: 'СВЕТЛОЕ ПАСТЕРИЗОВАННОЕ ФИЛЬТРОВАННОЕ «КЛАССИЧЕСКОЕ»',
                standard: 'ГОСТ 31711-2012',
                tiNumber: 'ТИ BY 200020111.002-2022',
                alcohol: '4,5%',
                sugar: '0 г/л',
                volume: '30 л',
                ingredients: 'Состав: вода питьевая, солод пивоваренный ячменный светлый, хмель прессованный, хмелепродукты. Пищевая ценность 100 мл пива: углеводы - не более 4.6 г; энергетическая ценность - 42 ккал / 170 кДж.',
                nutrition: 'Хранить в затемненных помещениях при температуре от 2 °С до 12 °С. После вскрытия кега хранить пиво под избыточным давлением углекислого газа не более 7 суток.',
                storage: 'Изготовитель: ОАО "Брестское пиво", 224014, Республика Беларусь, г. Брест, ул. Писателя Смирнова, 168. Тел.: +375 (162) 24-51-12, info@brestbeer.by',
                expiration: 'СРОК ГОДНОСТИ: 180 СУТОК С ДАТЫ РОЗЛИВА.',
                warningText: 'ЧРЕЗМЕРНОЕ УПОТРЕБЛЕНИЕ АЛКОГОЛЯ ВРЕДИТ ВАШЕМУ ЗДОРОВЬЮ',
                barcode: '4810123456789',
                barcodeFormat: 'EAN13',
                qrCode: 'https://brestbeer.by/classic-keg-30',
                eacActive: true,
                foodActive: true,
                petActive: true,
                warningHeightPercent: 12,
                customImages: [],
                elements: {
                    header: { x: 105, y: 4, size: 10, visible: true, bold: true },
                    title: { x: 105, y: 12, size: 34, visible: true, bold: true },
                    subtitle: { x: 105, y: 26, size: 8, visible: true, bold: true },
                    stats: { x: 4, y: 10, size: 12, visible: true },
                    icons: { x: 178, y: 5, size: 38, visible: true },
                    body: { x: 6, y: 52, size: 6.5, visible: true },
                    barcode: { x: 140, y: 88, size: 40, visible: true },
                    qrcode: { x: 182, y: 15, size: 22, visible: true }
                }
            }
        ];

        const ELEMENT_NAMES_RU = {
            header: 'Шапка (Производитель)',
            title: 'Название продукта (Тип)',
            subtitle: 'Подзаголовок / Описание',
            stats: 'Характеристики (Алк, Сахар, Объем)',
            icons: 'Значки соответствия (EAC, PET...)',
            body: 'Основной текст (Состав, условия...)',
            barcode: 'Штрих-код',
            qrcode: 'Генератор QR-кода'
        };

        function App() {
            const [form, setForm] = useState({ ...PRESETS[0] });
            const [savedTemplates, setSavedTemplates] = useState([]);
            const [activeTab, setActiveTab] = useState('editor'); // editor | templates
            const [customName, setCustomName] = useState('');
            const [zoom, setZoom] = useState(0.85);
            const [selectedElementId, setSelectedElementId] = useState(null); // ID of currently clicked canvas element
            const [showGrid, setShowGrid] = useState(false);
            const [printLayout, setPrintLayout] = useState('h2'); // h1, h2, v2, grid4
            const [customPrintConfig, setCustomPrintConfig] = useState({
                active: false,
                columns: 2,
                rows: 3,
                gap: 1.0,
                scale: 0.5,
                pageOrientation: 'portrait',
                labelCount: 6
            });

            // Checklist requirements СТБ 1100-2016
            const [checklist, setChecklist] = useState({
                manufacturer: true,
                expiration: true,
                ingredients: true,
                legalWarning: true,
                stbStandard: true
            });

            // Simple Undo/Redo Stacks
            const [history, setHistory] = useState([]);
            const [historyIndex, setHistoryIndex] = useState(-1);

            // Load templates from server PHP endpoint
            const fetchTemplates = () => {
                fetch('index.php?action=get_templates')
                    .then(res => res.json())
                    .then(data => {
                        if (Array.isArray(data)) {
                            setSavedTemplates(data);
                        }
                    })
                    .catch(err => console.error('Failed to load templates', err));
            };

            useEffect(() => {
                fetchTemplates();
            }, []);

            // Save history snapshot helper
            const recordHistory = (newState) => {
                const cleanState = JSON.parse(JSON.stringify(newState));
                const newHistory = history.slice(0, historyIndex + 1);
                newHistory.push(cleanState);
                if (newHistory.length > 20) newHistory.shift();
                setHistory(newHistory);
                setHistoryIndex(newHistory.length - 1);
            };

            const updateFormWithHistory = (updater) => {
                setForm(prev => {
                    const next = typeof updater === 'function' ? updater(prev) : { ...prev, ...updater };
                    recordHistory(next);
                    return next;
                });
            };

            // Undo/Redo handlers
            const handleUndo = () => {
                if (historyIndex > 0) {
                    const nextIndex = historyIndex - 1;
                    setHistoryIndex(nextIndex);
                    setForm(JSON.parse(JSON.stringify(history[nextIndex])));
                }
            };

            const handleRedo = () => {
                if (historyIndex < history.length - 1) {
                    const nextIndex = historyIndex + 1;
                    setHistoryIndex(nextIndex);
                    setForm(JSON.parse(JSON.stringify(history[nextIndex])));
                }
            };

            // Initialize first history frame
            useEffect(() => {
                if (history.length === 0) {
                    recordHistory(form);
                }
            }, []);

            // Keyboard navigation listener (1mm step or 5mm with Shift)
            useEffect(() => {
                const handleKeyDown = (e) => {
                    if (!selectedElementId) return;

                    // If focusing on inputs/textareas, skip moving elements
                    const tag = document.activeElement ? document.activeElement.tagName.toLowerCase() : '';
                    if (tag === 'input' || tag === 'textarea' || document.activeElement.isContentEditable) {
                        return;
                    }

                    const step = e.shiftKey ? 5 : 1;
                    let dx = 0;
                    let dy = 0;

                    if (e.key === 'ArrowUp') {
                        dy = -step;
                        e.preventDefault();
                    } else if (e.key === 'ArrowDown') {
                        dy = step;
                        e.preventDefault();
                    } else if (e.key === 'ArrowLeft') {
                        dx = -step;
                        e.preventDefault();
                    } else if (e.key === 'ArrowRight') {
                        dx = step;
                        e.preventDefault();
                    }

                    if (dx !== 0 || dy !== 0) {
                        handleCanvasElementDrag(selectedElementId, dx, dy);
                    }
                };

                window.addEventListener('keydown', handleKeyDown);
                return () => window.removeEventListener('keydown', handleKeyDown);
            }, [selectedElementId]);

            const applyPreset = (preset) => {
                setForm({ ...preset });
                setSelectedElementId(null);
                recordHistory(preset);
            };

            const handleFieldChange = (key, val) => {
                updateFormWithHistory(prev => ({ ...prev, [key]: val }));
            };

            // Inspector value modifications
            const updateElementAttribute = (elementId, attribute, value) => {
                updateFormWithHistory(prev => {
                    const updated = { ...prev };
                    if (elementId.startsWith('custom-img-')) {
                        updated.customImages = updated.customImages.map(img => {
                            if (img.id === elementId) {
                                return { ...img, [attribute]: value };
                            }
                            return img;
                        });
                    } else if (updated.elements[elementId]) {
                        updated.elements[elementId] = {
                            ...updated.elements[elementId],
                            [attribute]: value
                        };
                    }
                    return updated;
                });
            };

            // Direct Canvas Drag offset updates
            const handleCanvasElementDrag = (elementId, deltaX, deltaY) => {
                updateFormWithHistory(prev => {
                    const updated = { ...prev };
                    if (elementId.startsWith('custom-img-')) {
                        updated.customImages = updated.customImages.map(img => {
                            if (img.id === elementId) {
                                return {
                                    ...img,
                                    x: Math.max(0, Math.min(210, img.x + deltaX)),
                                    y: Math.max(0, Math.min(148, img.y + deltaY))
                                };
                            }
                            return img;
                        });
                    } else if (updated.elements[elementId]) {
                        updated.elements[elementId] = {
                            ...updated.elements[elementId],
                            x: Math.max(0, Math.min(210, updated.elements[elementId].x + deltaX)),
                            y: Math.max(0, Math.min(148, updated.elements[elementId].y + deltaY))
                        };
                    }
                    return updated;
                });
            };

            // Element Centering Tools
            const centerElementHorizontally = (elementId) => {
                if (!elementId) return;
                updateElementAttribute(elementId, 'x', 105);
            };

            const centerElementVertically = (elementId) => {
                if (!elementId) return;
                updateElementAttribute(elementId, 'y', 74);
            };

            // Image uploader handler (converts to Base64)
            const handleImageUpload = (event) => {
                const file = event.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    const base64Data = e.target.result;
                    const newImage = {
                        id: `custom-img-${Date.now()}`,
                        src: base64Data,
                        x: 85,
                        y: 54,
                        width: 40,
                        height: 40,
                        opacity: 1,
                        visible: true
                    };
                    updateFormWithHistory(prev => ({
                        ...prev,
                        customImages: [...(prev.customImages || []), newImage]
                    }));
                    setSelectedElementId(newImage.id);
                };
                reader.readAsDataURL(file);
            };

            // Delete selected custom image
            const deleteCustomImage = (imgId) => {
                updateFormWithHistory(prev => ({
                    ...prev,
                    customImages: prev.customImages.filter(img => img.id !== imgId)
                }));
                setSelectedElementId(null);
            };

            // Clean compliance verification checks (including 5mm margins check)
            const verifyCompliance = (data) => {
                const reports = [];
                let score = 100;

                const isWarningSizeValid = data.warningHeightPercent >= 10;
                if (!isWarningSizeValid) {
                    score -= 25;
                    reports.push({
                        status: 'error',
                        text: `Предупреждающая надпись занимает всего ${data.warningHeightPercent}%. По закону РБ площадь должна быть не менее 10%!`
                    });
                } else {
                    reports.push({
                        status: 'success',
                        text: `Предупреждающий баннер занимает ${data.warningHeightPercent}% (соответствует требованиям Минздрава РБ).`
                    });
                }

                const cleanedWarning = data.warningText ? data.warningText.toUpperCase().trim() : '';
                const exactPhrase = "ЧРЕЗМЕРНОЕ УПОТРЕБЛЕНИЕ АЛКОГОЛЯ ВРЕДИТ ВАШЕМУ ЗДОРОВЬЮ";
                if (cleanedWarning !== exactPhrase) {
                    score -= 20;
                    reports.push({
                        status: 'error',
                        text: `Текст предупреждения должен точно соответствовать закону: "${exactPhrase}".`
                    });
                } else {
                    reports.push({
                        status: 'success',
                        text: 'Предупреждающий текст полностью идентичен официальному регламенту РБ.'
                    });
                }

                if (!data.eacActive) {
                    score -= 15;
                    reports.push({
                        status: 'warning',
                        text: 'Маркировка знаком Евразийского соответствия EAC обязательна по ТР ТС 022/2011.'
                    });
                }

                // Check 5mm print-safe margin boundaries
                let marginViolation = false;
                if (data.elements) {
                    Object.entries(data.elements).forEach(([key, value]) => {
                        if (value.visible !== false) {
                            // Elements are absolute positioned, some are centered so they can slightly exceed bounds
                            // For security checks, we verify that X & Y are placed reasonably within 5mm to 205mm / 5mm to 143mm
                            if (value.y < 5 || value.y > (148 - 5)) {
                                marginViolation = true;
                            }
                        }
                    });
                }
                if (marginViolation) {
                    score -= 10;
                    reports.push({
                        status: 'warning',
                        text: 'Внимание: некоторые элементы размещены ближе чем 5 мм от границы обрезки этикетки.'
                    });
                } else {
                    reports.push({
                        status: 'success',
                        text: 'Все ключевые элементы расположены внутри 5-миллиметрового поля безопасности.'
                    });
                }

                if (data.barcode) {
                    const trimmed = data.barcode.trim();
                    let isEan13Valid = false;
                    if (/^\d{13}$/.test(trimmed)) {
                        let sum = 0;
                        for (let i = 0; i < 12; i++) {
                            sum += parseInt(trimmed[i], 10) * (i % 2 === 0 ? 1 : 3);
                        }
                        const calculatedChecksum = (10 - (sum % 10)) % 10;
                        isEan13Valid = calculatedChecksum === parseInt(trimmed[12], 10);
                    }

                    let isEan8Valid = false;
                    if (/^\d{8}$/.test(trimmed)) {
                        let sum = 0;
                        for (let i = 0; i < 7; i++) {
                            sum += parseInt(trimmed[i], 10) * (i % 2 === 0 ? 3 : 1);
                        }
                        const calculatedChecksum = (10 - (sum % 10)) % 10;
                        isEan8Valid = calculatedChecksum === parseInt(trimmed[7], 10);
                    }

                    if (data.barcodeFormat === 'EAN13' && !isEan13Valid) {
                        score -= 15;
                        reports.push({
                            status: 'error',
                            text: 'Неверный формат или контрольная сумма штрих-кода EAN-13.'
                        });
                    } else if (data.barcodeFormat === 'EAN8' && !isEan8Valid) {
                        score -= 15;
                        reports.push({
                            status: 'error',
                            text: 'Неверный формат или контрольная сумма штрих-кода EAN-8.'
                        });
                    } else {
                        reports.push({
                            status: 'success',
                            text: `Штрихкод ${data.barcodeFormat || 'EAN13'} успешно валидирован.`
                        });
                    }
                }

                return { score: Math.max(0, score), reports };
            };

            const compliance = verifyCompliance(form);

            // Get configuration details of currently selected element
            const getSelectedElementData = () => {
                if (!selectedElementId) return null;
                if (selectedElementId.startsWith('custom-img-')) {
                    return form.customImages.find(img => img.id === selectedElementId) || null;
                }
                return form.elements[selectedElementId] ? { id: selectedElementId, ...form.elements[selectedElementId] } : null;
            };

            const selectedElement = getSelectedElementData();

            // Save template using PHP Backend API
            const saveTemplate = () => {
                if (!customName.trim()) {
                    alert('Пожалуйста, укажите наименование шаблона для сохранения!');
                    return;
                }
                const newTemplate = {
                    ...form,
                    id: Date.now().toString(),
                    name: customName
                };

                fetch('index.php?action=save_template', {
                    method: 'POST',
                    headers: { 'Content-Type': 'application/json' },
                    body: JSON.stringify(newTemplate)
                })
                .then(res => res.json())
                .then(data => {
                    if (data.success) {
                        setSavedTemplates(data.templates);
                        setCustomName('');
                        alert('Шаблон успешно сохранен в базу данных PHP!');
                    } else {
                        alert('Ошибка сохранения: ' + data.error);
                    }
                })
                .catch(err => {
                    console.error(err);
                    alert('Сервер недоступен при сохранении.');
                });
            };

            const deleteTemplate = (id) => {
                if (confirm('Вы уверены, что хотите удалить этот шаблон?')) {
                    fetch('index.php?action=delete_template', {
                        method: 'POST',
                        headers: { 'Content-Type': 'application/json' },
                        body: JSON.stringify({ id })
                    })
                    .then(res => res.json())
                    .then(data => {
                        if (data.success) {
                            setSavedTemplates(data.templates);
                            alert('Шаблон удален!');
                        } else {
                            alert('Ошибка удаления: ' + data.error);
                        }
                    })
                    .catch(err => console.error(err));
                }
            };

            const exportTemplates = () => {
                const dataStr = "data:text/json;charset=utf-8," + encodeURIComponent(JSON.stringify(form));
                const downloadAnchor = document.createElement('a');
                downloadAnchor.setAttribute("href", dataStr);
                downloadAnchor.setAttribute("download", `этикетка_конструктор_${form.productType}.json`);
                document.body.appendChild(downloadAnchor);
                downloadAnchor.click();
                downloadAnchor.remove();
            };

            const exportAsImage = () => {
                const container = document.querySelector('.label-preview-container');
                if (!container) return;

                html2canvas(container, {
                    scale: 2.5, // High resolution
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff'
                }).then(canvas => {
                    const dataUrl = canvas.toDataURL('image/png');
                    const link = document.createElement('a');
                    link.download = `этикетка_${form.productType || 'напиток'}.png`;
                    link.href = dataUrl;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                }).catch(err => {
                    console.error("Failed to generate image", err);
                    alert("Ошибка при экспорте изображения.");
                });
            };

            const exportAsWord = () => {
                const container = document.querySelector('.label-preview-container');
                if (!container) return;

                html2canvas(container, {
                    scale: 2.5, // High resolution
                    useCORS: true,
                    allowTaint: true,
                    backgroundColor: '#ffffff'
                }).then(canvas => {
                    const pngDataUri = canvas.toDataURL('image/png');

                    const wordHtml = `
                    <html xmlns:o="urn:schemas-microsoft-com:office:office" xmlns:w="urn:schemas-microsoft-com:office:word" xmlns="http://www.w3.org/TR/REC-html40">
                    <head>
                        <title>Этикетка на кегу - ${form.productType}</title>
                        <!--[if gte mso 9]>
                        <xml>
                            <w:WordDocument>
                                <w:View>Print</w:View>
                                <w:Zoom>100</w:Zoom>
                                <w:DoNotOptimizeForBrowser/>
                            </w:WordDocument>
                        </xml>
                        <![endif]-->
                        <style>
                            @page {
                                size: 210mm 148mm;
                                margin: 10mm;
                            }
                            body {
                                font-family: "Arial", sans-serif;
                                text-align: center;
                                background-color: #ffffff;
                                padding: 20px;
                            }
                            h2 {
                                font-family: "Arial", sans-serif;
                                margin-bottom: 5px;
                                color: #333333;
                            }
                            p {
                                font-family: "Arial", sans-serif;
                                font-size: 11px;
                                color: #666666;
                                margin-bottom: 20px;
                            }
                            .image-container {
                                text-align: center;
                                margin-top: 10px;
                            }
                            img {
                                max-width: 100%;
                                height: auto;
                                border: 1px solid #cccccc;
                            }
                        </style>
                    </head>
                    <body>
                        <h2>Этикетка на кегу - ${form.brandName || 'Бренд'} ${form.productType}</h2>
                        <p>${form.subtitle}</p>
                        <div class="image-container">
                            <img src="${pngDataUri}" alt="Этикетка" />
                        </div>
                    </body>
                    </html>
                    `;

                    const blob = new Blob(['\ufeff' + wordHtml], { type: 'application/msword;charset=utf-8' });
                    const link = document.createElement('a');
                    link.href = URL.createObjectURL(blob);
                    link.download = `этикетка_${form.productType || 'напиток'}.doc`;
                    document.body.appendChild(link);
                    link.click();
                    link.remove();
                }).catch(err => {
                    console.error("Failed to generate Word document", err);
                    alert("Ошибка при экспорте в MS Word.");
                });
            };

            const handleImport = (event) => {
                const file = event.target.files[0];
                if (!file) return;
                const reader = new FileReader();
                reader.onload = (e) => {
                    try {
                        const imported = JSON.parse(e.target.result);
                        if (imported && imported.productType) {
                            setForm(imported);
                            alert('Шаблон успешно импортирован!');
                        } else {
                            alert('Неверная структура шаблона.');
                        }
                    } catch (err) {
                        alert('Не удалось прочесть файл JSON.');
                    }
                };
                reader.readAsText(file);
            };

            const isLandscape = customPrintConfig.active
                ? customPrintConfig.pageOrientation === 'landscape'
                : (printLayout === 'v2' || printLayout === 'v3');

            const dynamicPageSizeStyle = isLandscape
                ? `@media print { @page { size: A4 landscape !important; margin: 0 !important; } }`
                : `@media print { @page { size: A4 portrait !important; margin: 0 !important; } }`;

            return (
                <div className="min-h-screen flex flex-col">
                    <style dangerouslySetInnerHTML={{ __html: dynamicPageSizeStyle }} />
                    <div className="no-print min-h-screen flex flex-col">
                        {/* Header */}
                        <header className="bg-gradient-to-r from-brand-700 to-brand-500 text-white shadow-md">
                        <div className="max-w-7xl mx-auto px-4 py-3 flex flex-col sm:flex-row justify-between items-center space-y-3 sm:space-y-0">
                            <div className="flex items-center space-x-3">
                                <div className="p-2 bg-white/10 rounded-lg">
                                    <Icon name="beer" className="w-8 h-8 text-emerald-300" />
                                </div>
                                <div>
                                    <h1 className="text-xl font-bold tracking-tight Montserrat">
                                        Этикетки Кег PHP 3.5 <span className="text-emerald-300">РБ</span>
                                    </h1>
                                    <p className="text-xs text-brand-100 font-medium">
                                        Сет СТБ 1100-2016 • Генератор EAN-13/EAN-8/Code-128 • Физические линейки в мм
                                    </p>
                                </div>
                            </div>

                            <div className="flex space-x-2">
                                <button
                                    onClick={() => setActiveTab('editor')}
                                    className={`px-3 py-1.5 rounded-lg text-sm font-semibold transition-all flex items-center space-x-1.5 ${
                                        activeTab === 'editor' ? 'bg-white text-brand-700 shadow-md' : 'text-white hover:bg-white/10'
                                    }`}
                                >
                                    <Icon name="cog" className="w-4 h-4" />
                                    <span>Конструктор</span>
                                </button>
                                <button
                                    onClick={() => setActiveTab('templates')}
                                    className={`px-3 py-1.5 rounded-lg text-sm font-semibold transition-all flex items-center space-x-1.5 ${
                                        activeTab === 'templates' ? 'bg-white text-brand-700 shadow-md' : 'text-white hover:bg-white/10'
                                    }`}
                                >
                                    <Icon name="fileText" className="w-4 h-4" />
                                    <span>Библиотека</span>
                                </button>
                                <button
                                    onClick={() => window.print()}
                                    className="px-3.5 py-1.5 bg-emerald-600 hover:bg-emerald-500 text-white rounded-lg text-sm font-bold shadow-md transition-all flex items-center space-x-1.5 border border-emerald-400"
                                >
                                    <Icon name="printer" className="w-4 h-4" />
                                    <span>Печать</span>
                                </button>
                            </div>
                        </div>
                    </header>

                    {/* Main workspace */}
                    <main className="flex-1 max-w-[1680px] w-full mx-auto p-4 md:p-6 grid grid-cols-1 xl:grid-cols-12 gap-6">
                        {activeTab === 'editor' && (
                            <>
                                {/* Canvas panel */}
                                <div className="xl:col-span-7 flex flex-col space-y-4">
                                    <div className="bg-white rounded-xl shadow-md p-4 border border-slate-200">
                                        <div className="flex flex-wrap justify-between items-center gap-3 mb-3 pb-3 border-b">
                                            {/* Toolbar actions */}
                                            <div className="flex items-center space-x-2">
                                                <button
                                                    onClick={handleUndo}
                                                    disabled={historyIndex <= 0}
                                                    className="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded disabled:opacity-40 transition"
                                                    title="Отменить (Ctrl+Z)"
                                                >
                                                    <Icon name="undo" className="w-4 h-4" />
                                                </button>
                                                <button
                                                    onClick={handleRedo}
                                                    disabled={historyIndex >= history.length - 1}
                                                    className="p-2 bg-slate-100 hover:bg-slate-200 text-slate-700 rounded disabled:opacity-40 transition"
                                                    title="Повторить"
                                                >
                                                    <Icon name="redo" className="w-4 h-4" />
                                                </button>
                                                <span className="h-6 w-px bg-slate-200"></span>
                                                <button
                                                    onClick={() => setShowGrid(!showGrid)}
                                                    className={`p-2 rounded transition flex items-center space-x-1 ${
                                                        showGrid ? 'bg-brand-100 text-brand-800 font-bold' : 'bg-slate-100 text-slate-700'
                                                    }`}
                                                    title="Сетка"
                                                >
                                                    <Icon name="grid" className="w-4 h-4" />
                                                    <span className="text-xs hidden sm:inline">Сетка (5мм)</span>
                                                </button>
                                            </div>

                                            <div className="text-xs text-slate-400 font-medium">
                                                Перемещайте стрелочками клавиатуры (1мм, +Shift = 5мм)
                                            </div>

                                            {/* Scale sliders */}
                                            <div className="flex items-center space-x-2">
                                                <span className="text-xs text-slate-500">Масштаб:</span>
                                                <input
                                                    type="range"
                                                    min="0.5"
                                                    max="1.2"
                                                    step="0.05"
                                                    value={zoom}
                                                    onChange={(e) => setZoom(parseFloat(e.target.value))}
                                                    className="w-24 accent-brand-500 h-1.5 bg-slate-200 rounded"
                                                />
                                                <span className="text-xs font-semibold text-slate-700 w-8">
                                                    {Math.round(zoom * 100)}%
                                                </span>
                                            </div>
                                        </div>

                                        {/* Canvas Wrapper with Rulers */}
                                        <div className="overflow-auto bg-slate-100 p-4 rounded-lg border border-slate-300 flex justify-center items-start min-h-[500px]">
                                            <div style={{ transform: `scale(${zoom})` }} className="origin-top-left transition-transform duration-75">
                                                <div className="ruler-container">
                                                    {/* Horizontal Ruler (210mm) */}
                                                    <div className="hr-ruler">
                                                        {Array.from({ length: 22 }).map((_, i) => {
                                                            const mm = i * 10;
                                                            const leftPos = mm * 3.779;
                                                            return (
                                                                <React.Fragment key={i}>
                                                                    <div className="ruler-tick h-3 w-px" style={{ left: `${leftPos}px`, bottom: 0 }}></div>
                                                                    {mm % 20 === 0 && (
                                                                        <span className="ruler-label" style={{ left: `${leftPos + 2}px`, bottom: '14px' }}>
                                                                            {mm}
                                                                        </span>
                                                                    )}
                                                                </React.Fragment>
                                                            );
                                                        })}
                                                        {Array.from({ length: 42 }).map((_, i) => {
                                                            const mm = i * 5;
                                                            if (mm % 10 === 0) return null;
                                                            const leftPos = mm * 3.779;
                                                            return (
                                                                <div key={i} className="ruler-tick h-1.5 w-px" style={{ left: `${leftPos}px`, bottom: 0 }}></div>
                                                            );
                                                        })}
                                                    </div>

                                                    {/* Vertical Ruler (148mm) */}
                                                    <div className="vr-ruler">
                                                        {Array.from({ length: 16 }).map((_, i) => {
                                                            const mm = i * 10;
                                                            const topPos = mm * 3.779;
                                                            return (
                                                                <React.Fragment key={i}>
                                                                    <div className="ruler-tick w-3 h-px" style={{ top: `${topPos}px`, right: 0 }}></div>
                                                                    {mm % 20 === 0 && (
                                                                        <span className="ruler-label" style={{ top: `${topPos + 2}px`, right: '14px' }}>
                                                                            {mm}
                                                                        </span>
                                                                    )}
                                                                </React.Fragment>
                                                            );
                                                        })}
                                                        {Array.from({ length: 30 }).map((_, i) => {
                                                            const mm = i * 5;
                                                            if (mm % 10 === 0) return null;
                                                            const topPos = mm * 3.779;
                                                            return (
                                                                <div key={i} className="ruler-tick w-1.5 h-px" style={{ top: `${topPos}px`, right: 0 }}></div>
                                                            );
                                                        })}
                                                    </div>

                                                    {/* The core workspace */}
                                                    <LabelPreview
                                                        data={form}
                                                        selectedId={selectedElementId}
                                                        onSelectElement={setSelectedElementId}
                                                        onDragElement={handleCanvasElementDrag}
                                                        showGrid={showGrid}
                                                    />
                                                </div>
                                            </div>
                                        </div>
                                    </div>

                                    {/* Compliance index & error listings */}
                                    <div className="bg-white rounded-xl shadow-md p-4 border border-slate-200">
                                        <div className="flex items-center justify-between border-b pb-2 mb-3">
                                            <h3 className="font-bold text-slate-800 flex items-center space-x-2">
                                                <span>Индикатор маркировки РБ (СТБ 1100-2016)</span>
                                            </h3>
                                            <div className="flex items-center space-x-1.5">
                                                <span className="text-xs font-bold text-slate-500">Индекс легальности:</span>
                                                <span className={`px-2 py-0.5 rounded text-xs font-bold ${
                                                    compliance.score >= 90 ? 'bg-emerald-100 text-emerald-800' : 'bg-red-100 text-red-800'
                                                }`}>
                                                    {compliance.score}%
                                                </span>
                                            </div>
                                        </div>

                                        <div className="space-y-2 max-h-[140px] overflow-y-auto pr-1">
                                            {compliance.reports.map((r, idx) => (
                                                <div key={idx} className={`p-2 rounded-lg text-xs flex items-start space-x-2 border ${
                                                    r.status === 'success' ? 'bg-emerald-50 text-emerald-900 border-emerald-200' : 'bg-red-50 text-red-900 border-red-200'
                                                }`}>
                                                    <span className="mt-0.5 shrink-0">
                                                        {r.status === 'success' ? <Icon name="checkCircle" className="w-4 h-4 text-emerald-600" /> : <Icon name="alertCircle" className="w-4 h-4 text-red-600" />}
                                                    </span>
                                                    <span className="font-medium">{r.text}</span>
                                                </div>
                                            ))}
                                        </div>
                                    </div>
                                </div>

                                {/* Form settings / inspector side panel */}
                                <div className="xl:col-span-5 bg-white rounded-xl shadow-md border border-slate-200 flex flex-col">
                                    {/* Top Preset selector */}
                                    <div className="p-3 bg-brand-50 border-b border-slate-200 flex items-center space-x-2 overflow-x-auto">
                                        <span className="text-xs font-bold text-slate-500 shrink-0">Предустановки:</span>
                                        {PRESETS.map((p) => (
                                            <button
                                                key={p.id}
                                                onClick={() => applyPreset(p)}
                                                className={`px-3 py-1 rounded-full text-xs font-semibold shrink-0 transition-all ${
                                                    form.id === p.id ? 'bg-brand-600 text-white' : 'bg-white text-brand-700 hover:bg-brand-100 border border-brand-200'
                                                }`}
                                            >
                                                {p.productType}
                                            </button>
                                        ))}
                                    </div>

                                    {/* Form tabs */}
                                    <div className="flex-1 p-4 space-y-4 overflow-y-auto max-h-[580px]">

                                        {/* Inspector block if element is selected */}
                                        {selectedElement && (
                                            <div className="bg-blue-50/70 border border-blue-200 rounded-lg p-3 space-y-3">
                                                <div className="flex justify-between items-center border-b border-blue-200/50 pb-2">
                                                    <span className="text-xs font-bold text-blue-900 uppercase">
                                                        Настройки: {selectedElement.id.startsWith('custom-img-') ? 'Пользовательское изображение' : (ELEMENT_NAMES_RU[selectedElement.id] || selectedElement.id)}
                                                    </span>
                                                    <button
                                                        onClick={() => setSelectedElementId(null)}
                                                        className="text-xs text-blue-600 hover:text-blue-900 font-semibold"
                                                    >
                                                        Снять выбор
                                                    </button>
                                                </div>

                                                {/* Alignment Shortcuts */}
                                                <div className="flex space-x-2">
                                                    <button
                                                        onClick={() => centerElementHorizontally(selectedElement.id)}
                                                        className="flex-1 px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-[10px] font-bold transition"
                                                    >
                                                        Центр по X (105мм)
                                                    </button>
                                                    <button
                                                        onClick={() => centerElementVertically(selectedElement.id)}
                                                        className="flex-1 px-2 py-1 bg-blue-600 hover:bg-blue-700 text-white rounded text-[10px] font-bold transition"
                                                    >
                                                        Центр по Y (74мм)
                                                    </button>
                                                </div>

                                                <div className="grid grid-cols-2 gap-3">
                                                    <div>
                                                        <label className="block text-[11px] font-bold text-slate-600 mb-0.5">Координата X (мм)</label>
                                                        <input
                                                            type="range" min="0" max="210" step="1"
                                                            value={selectedElement.x}
                                                            onChange={(e) => updateElementAttribute(selectedElement.id, 'x', parseInt(e.target.value))}
                                                            className="w-full accent-blue-600"
                                                        />
                                                        <div className="text-right text-[10px] font-bold text-slate-500">{selectedElement.x} мм</div>
                                                    </div>
                                                    <div>
                                                        <label className="block text-[11px] font-bold text-slate-600 mb-0.5">Координата Y (мм)</label>
                                                        <input
                                                            type="range" min="0" max="148" step="1"
                                                            value={selectedElement.y}
                                                            onChange={(e) => updateElementAttribute(selectedElement.id, 'y', parseInt(e.target.value))}
                                                            className="w-full accent-blue-600"
                                                        />
                                                        <div className="text-right text-[10px] font-bold text-slate-500">{selectedElement.y} мм</div>
                                                    </div>
                                                </div>

                                                <div className="grid grid-cols-2 gap-3">
                                                    {selectedElement.id.startsWith('custom-img-') ? (
                                                        <>
                                                            <div>
                                                                <label className="block text-[11px] font-bold text-slate-600 mb-0.5">Ширина (мм)</label>
                                                                <input
                                                                    type="range" min="5" max="150" step="1"
                                                                    value={selectedElement.width}
                                                                    onChange={(e) => updateElementAttribute(selectedElement.id, 'width', parseInt(e.target.value))}
                                                                    className="w-full accent-blue-600"
                                                                />
                                                                <div className="text-right text-[10px] font-bold text-slate-500">{selectedElement.width} мм</div>
                                                            </div>
                                                            <div>
                                                                <label className="block text-[11px] font-bold text-slate-600 mb-0.5">Высота (мм)</label>
                                                                <input
                                                                    type="range" min="5" max="150" step="1"
                                                                    value={selectedElement.height}
                                                                    onChange={(e) => updateElementAttribute(selectedElement.id, 'height', parseInt(e.target.value))}
                                                                    className="w-full accent-blue-600"
                                                                />
                                                                <div className="text-right text-[10px] font-bold text-slate-500">{selectedElement.height} мм</div>
                                                            </div>
                                                        </>
                                                    ) : (selectedElement.id === 'barcode' || selectedElement.id === 'qrcode') ? (
                                                        <div>
                                                            <label className="block text-[11px] font-bold text-slate-600 mb-0.5">Размер / Ширина (мм)</label>
                                                            <input
                                                                type="range" min="10" max="150" step="1"
                                                                value={selectedElement.size || (selectedElement.id === 'barcode' ? 40 : 22)}
                                                                onChange={(e) => updateElementAttribute(selectedElement.id, 'size', parseFloat(e.target.value))}
                                                                className="w-full accent-blue-600"
                                                            />
                                                            <div className="text-right text-[10px] font-bold text-slate-500">{selectedElement.size || (selectedElement.id === 'barcode' ? 40 : 22)} мм</div>
                                                        </div>
                                                    ) : (
                                                        <div>
                                                            <label className="block text-[11px] font-bold text-slate-600 mb-0.5">Размер шрифта / иконок</label>
                                                            <input
                                                                type="range" min="5" max="64" step="0.5"
                                                                value={selectedElement.size}
                                                                onChange={(e) => updateElementAttribute(selectedElement.id, 'size', parseFloat(e.target.value))}
                                                                className="w-full accent-blue-600"
                                                            />
                                                            <div className="text-right text-[10px] font-bold text-slate-500">{selectedElement.size} единиц</div>
                                                        </div>
                                                    )}
                                                </div>

                                                <div className="flex justify-between items-center pt-2">
                                                    <label className="flex items-center space-x-2 text-xs font-semibold text-slate-700">
                                                        <input
                                                            type="checkbox"
                                                            checked={selectedElement.visible !== false}
                                                            onChange={(e) => updateElementAttribute(selectedElement.id, 'visible', e.target.checked)}
                                                            className="rounded text-blue-600 w-4 h-4"
                                                        />
                                                        <span>Отображать элемент</span>
                                                    </label>

                                                    {selectedElement.id.startsWith('custom-img-') && (
                                                        <button
                                                            onClick={() => deleteCustomImage(selectedElement.id)}
                                                            className="px-2.5 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded text-xs font-bold flex items-center space-x-1"
                                                        >
                                                            <Icon name="trash" className="w-3.5 h-3.5" />
                                                            <span>Удалить изображение</span>
                                                        </button>
                                                    )}
                                                </div>
                                            </div>
                                        )}

                                        {/* СТБ 1100-2016 Checklist regulations */}
                                        <div className="bg-emerald-50/50 border border-emerald-200 rounded-xl p-3">
                                            <h4 className="text-xs font-bold text-emerald-800 mb-2 flex items-center space-x-1">
                                                <Icon name="checkCircle" className="w-4 h-4" />
                                                <span>СТБ 1100-2016 Требования к маркировке</span>
                                            </h4>
                                            <div className="space-y-1.5 text-xs text-slate-700">
                                                <label className="flex items-center space-x-2 cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={checklist.manufacturer}
                                                        onChange={(e) => setChecklist(prev => ({ ...prev, manufacturer: e.target.checked }))}
                                                        className="rounded text-emerald-600 w-4 h-4"
                                                    />
                                                    <span>Информация об изготовителе (РБ / Импортер)</span>
                                                </label>
                                                <label className="flex items-center space-x-2 cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={checklist.expiration}
                                                        onChange={(e) => setChecklist(prev => ({ ...prev, expiration: e.target.checked }))}
                                                        className="rounded text-emerald-600 w-4 h-4"
                                                    />
                                                    <span>Срок годности и условия хранения</span>
                                                </label>
                                                <label className="flex items-center space-x-2 cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={checklist.ingredients}
                                                        onChange={(e) => setChecklist(prev => ({ ...prev, ingredients: e.target.checked }))}
                                                        className="rounded text-emerald-600 w-4 h-4"
                                                    />
                                                    <span>Состав сырья, красители, консерванты</span>
                                                </label>
                                                <label className="flex items-center space-x-2 cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={checklist.legalWarning}
                                                        onChange={(e) => setChecklist(prev => ({ ...prev, legalWarning: e.target.checked }))}
                                                        className="rounded text-emerald-600 w-4 h-4"
                                                    />
                                                    <span>Минздрав Предупреждение (мин. 10% высоты)</span>
                                                </label>
                                                <label className="flex items-center space-x-2 cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={checklist.stbStandard}
                                                        onChange={(e) => setChecklist(prev => ({ ...prev, stbStandard: e.target.checked }))}
                                                        className="rounded text-emerald-600 w-4 h-4"
                                                    />
                                                    <span>Номер стандарта (ГОСТ или СТБ)</span>
                                                </label>
                                            </div>
                                        </div>

                                        {/* External custom image uploader */}
                                        <div className="bg-slate-50 border border-dashed border-slate-300 rounded-xl p-3 flex flex-col items-center justify-center space-y-2">
                                            <div className="text-center">
                                                <p className="text-xs font-bold text-slate-700">Загрузка логотипа / герба / знаков</p>
                                                <p className="text-[10px] text-slate-400">Изображение добавится на холст как перетаскиваемый элемент</p>
                                            </div>
                                            <label className="px-4 py-2 bg-emerald-50 hover:bg-emerald-100 text-emerald-800 border border-emerald-300 rounded-lg text-xs font-bold cursor-pointer flex items-center space-x-1.5 transition">
                                                <Icon name="image" className="w-4 h-4" />
                                                <span>Загрузить изображение</span>
                                                <input type="file" accept="image/*" onChange={handleImageUpload} className="hidden" />
                                            </label>
                                        </div>

                                        <div className="space-y-3">
                                            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Основная информация</h4>
                                            <div className="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label className="block text-xs font-semibold text-slate-600 mb-1">Бренд (Шапка)</label>
                                                    <input
                                                        type="text"
                                                        value={form.brandName}
                                                        onChange={(e) => handleFieldChange('brandName', e.target.value)}
                                                        className="w-full text-xs border border-slate-300 rounded-lg p-2"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block text-xs font-semibold text-slate-600 mb-1">Вид продукции</label>
                                                    <input
                                                        type="text"
                                                        value={form.productType}
                                                        onChange={(e) => handleFieldChange('productType', e.target.value)}
                                                        className="w-full text-xs border border-slate-300 rounded-lg p-2"
                                                    />
                                                </div>
                                            </div>

                                            <div>
                                                <label className="block text-xs font-semibold text-slate-600 mb-1">Наименование/Подзаголовок</label>
                                                <textarea
                                                    rows="2"
                                                    value={form.subtitle}
                                                    onChange={(e) => handleFieldChange('subtitle', e.target.value)}
                                                    className="w-full text-xs border border-slate-300 rounded-lg p-2"
                                                />
                                            </div>

                                            <div className="grid grid-cols-3 gap-3">
                                                <div>
                                                    <label className="block text-xs font-semibold text-slate-600 mb-1">Объем</label>
                                                    <input
                                                        type="text"
                                                        value={form.volume}
                                                        onChange={(e) => handleFieldChange('volume', e.target.value)}
                                                        className="w-full text-xs border border-slate-300 rounded-lg p-2"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block text-xs font-semibold text-slate-600 mb-1">Алкоголь</label>
                                                    <input
                                                        type="text"
                                                        value={form.alcohol}
                                                        onChange={(e) => handleFieldChange('alcohol', e.target.value)}
                                                        className="w-full text-xs border border-slate-300 rounded-lg p-2"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block text-xs font-semibold text-slate-600 mb-1">Сахар</label>
                                                    <input
                                                        type="text"
                                                        value={form.sugar}
                                                        onChange={(e) => handleFieldChange('sugar', e.target.value)}
                                                        className="w-full text-xs border border-slate-300 rounded-lg p-2"
                                                    />
                                                </div>
                                            </div>

                                            <div className="grid grid-cols-2 gap-3">
                                                <div>
                                                    <label className="block text-xs font-semibold text-slate-600 mb-1">ГОСТ / СТБ</label>
                                                    <input
                                                        type="text"
                                                        value={form.standard}
                                                        onChange={(e) => handleFieldChange('standard', e.target.value)}
                                                        className="w-full text-xs border border-slate-300 rounded-lg p-2"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block text-xs font-semibold text-slate-600 mb-1">ТИ / ТУ</label>
                                                    <input
                                                        type="text"
                                                        value={form.tiNumber}
                                                        onChange={(e) => handleFieldChange('tiNumber', e.target.value)}
                                                        className="w-full text-xs border border-slate-300 rounded-lg p-2"
                                                    />
                                                </div>
                                            </div>
                                        </div>

                                        <hr />

                                        {/* Description parameters */}
                                        <div className="space-y-3">
                                            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Маркировочные данные</h4>
                                            <div>
                                                <label className="block text-xs font-semibold text-slate-600 mb-1">Состав</label>
                                                <textarea
                                                    rows="3"
                                                    value={form.ingredients}
                                                    onChange={(e) => handleFieldChange('ingredients', e.target.value)}
                                                    className="w-full text-xs border border-slate-300 rounded-lg p-2 font-narrow"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-xs font-semibold text-slate-600 mb-1">Условия хранения</label>
                                                <textarea
                                                    rows="2"
                                                    value={form.nutrition}
                                                    onChange={(e) => handleFieldChange('nutrition', e.target.value)}
                                                    className="w-full text-xs border border-slate-300 rounded-lg p-2 font-narrow"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-xs font-semibold text-slate-600 mb-1">Изготовитель / Производитель</label>
                                                <textarea
                                                    rows="2"
                                                    value={form.storage}
                                                    onChange={(e) => handleFieldChange('storage', e.target.value)}
                                                    className="w-full text-xs border border-slate-300 rounded-lg p-2 font-narrow"
                                                />
                                            </div>
                                            <div>
                                                <label className="block text-xs font-semibold text-slate-600 mb-1">Срок годности</label>
                                                <input
                                                    type="text"
                                                    value={form.expiration}
                                                    onChange={(e) => handleFieldChange('expiration', e.target.value)}
                                                    className="w-full text-xs border border-slate-300 rounded-lg p-2 font-bold"
                                                />
                                            </div>
                                        </div>

                                        <hr />

                                        {/* Codes, Warnings & Options */}
                                        <div className="space-y-3">
                                            <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Коды, Предупреждения и Знаки</h4>
                                            <div className="grid grid-cols-3 gap-2">
                                                <div className="col-span-2">
                                                    <label className="block text-xs font-semibold text-slate-600 mb-1">Код (Штрихкод)</label>
                                                    <input
                                                        type="text"
                                                        value={form.barcode}
                                                        onChange={(e) => handleFieldChange('barcode', e.target.value)}
                                                        className="w-full text-xs border border-slate-300 rounded-lg p-2 font-mono"
                                                    />
                                                </div>
                                                <div>
                                                    <label className="block text-xs font-semibold text-slate-600 mb-1">Формат</label>
                                                    <select
                                                        value={form.barcodeFormat || 'EAN13'}
                                                        onChange={(e) => handleFieldChange('barcodeFormat', e.target.value)}
                                                        className="w-full text-xs border border-slate-300 rounded-lg p-2 font-semibold text-slate-700"
                                                    >
                                                        <option value="EAN13">EAN-13</option>
                                                        <option value="EAN8">EAN-8</option>
                                                        <option value="CODE128">Code-128</option>
                                                    </select>
                                                </div>
                                            </div>

                                            <div>
                                                <label className="block text-xs font-semibold text-slate-600 mb-1">QR-код (Ссылка или данные)</label>
                                                <input
                                                    type="text"
                                                    value={form.qrCode}
                                                    onChange={(e) => handleFieldChange('qrCode', e.target.value)}
                                                    className="w-full text-xs border border-slate-300 rounded-lg p-2 font-mono"
                                                />
                                            </div>

                                            <div>
                                                <label className="block text-xs font-semibold text-slate-600 mb-1">Предупреждающий баннер</label>
                                                <input
                                                    type="text"
                                                    value={form.warningText}
                                                    onChange={(e) => handleFieldChange('warningText', e.target.value)}
                                                    className="w-full text-xs border border-slate-300 rounded-lg p-2 font-bold"
                                                />
                                            </div>

                                            <div>
                                                <div className="flex justify-between text-xs font-semibold text-slate-600 mb-1">
                                                    <span>Высота баннера предупреждения</span>
                                                    <span className={`font-bold ${form.warningHeightPercent >= 10 ? 'text-emerald-600' : 'text-red-500'}`}>
                                                        {form.warningHeightPercent}% (РБ: >= 10%)
                                                    </span>
                                                </div>
                                                <input
                                                    type="range" min="6" max="25" step="1"
                                                    value={form.warningHeightPercent}
                                                    onChange={(e) => handleFieldChange('warningHeightPercent', parseInt(e.target.value, 10))}
                                                    className="w-full accent-brand-500"
                                                />
                                            </div>

                                            <div className="bg-slate-50 p-2.5 rounded-lg border border-slate-200 grid grid-cols-3 gap-2">
                                                <label className="flex items-center space-x-2 text-xs font-medium cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={form.eacActive}
                                                        onChange={(e) => handleFieldChange('eacActive', e.target.checked)}
                                                        className="rounded text-brand-600 w-4 h-4"
                                                    />
                                                    <span>Знак EAC</span>
                                                </label>
                                                <label className="flex items-center space-x-2 text-xs font-medium cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={form.foodActive}
                                                        onChange={(e) => handleFieldChange('foodActive', e.target.checked)}
                                                        className="rounded text-brand-600 w-4 h-4"
                                                    />
                                                    <span>Бокал-Вилка</span>
                                                </label>
                                                <label className="flex items-center space-x-2 text-xs font-medium cursor-pointer">
                                                    <input
                                                        type="checkbox"
                                                        checked={form.petActive}
                                                        onChange={(e) => handleFieldChange('petActive', e.target.checked)}
                                                        className="rounded text-brand-600 w-4 h-4"
                                                    />
                                                    <span>ПЭТ 01</span>
                                                </label>
                                            </div>
                                        </div>

                                        <hr />

                                        {/* Print Page Customization */}
                                        <div className="space-y-4">
                                            <div className="flex justify-between items-center pb-1 border-b border-slate-200">
                                                <h4 className="text-xs font-bold text-slate-400 uppercase tracking-wider">Макет Печати (А4)</h4>
                                                <label className="flex items-center space-x-1.5 cursor-pointer select-none">
                                                    <span className="text-[10px] font-bold text-slate-500 uppercase">Свой макет:</span>
                                                    <input
                                                        type="checkbox"
                                                        checked={customPrintConfig.active}
                                                        onChange={(e) => setCustomPrintConfig(prev => ({ ...prev, active: e.target.checked }))}
                                                        className="w-4 h-4 text-brand-600 rounded border-slate-300 focus:ring-brand-500"
                                                    />
                                                </label>
                                            </div>

                                            {!customPrintConfig.active ? (
                                                <div>
                                                    <label className="block text-xs font-semibold text-slate-600 mb-2">Формат раскладки на листе:</label>
                                                    <div className="grid grid-cols-2 gap-2">
                                                        <button
                                                            onClick={() => setPrintLayout('h1')}
                                                            className={`p-2 rounded text-xs font-bold border transition text-left flex flex-col justify-between h-16 ${
                                                                printLayout === 'h1' ? 'bg-brand-600 text-white border-brand-600 shadow' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            <span>1 горизонтально</span>
                                                            <span className="text-[10px] opacity-80 font-normal">A5 Альбомная (Центр)</span>
                                                        </button>
                                                        <button
                                                            onClick={() => setPrintLayout('h2')}
                                                            className={`p-2 rounded text-xs font-bold border transition text-left flex flex-col justify-between h-16 ${
                                                                printLayout === 'h2' ? 'bg-brand-600 text-white border-brand-600 shadow' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            <span>2 горизонтально</span>
                                                            <span className="text-[10px] opacity-80 font-normal">А5 x 2 Портрет</span>
                                                        </button>
                                                        <button
                                                            onClick={() => setPrintLayout('v2')}
                                                            className={`p-2 rounded text-xs font-bold border transition text-left flex flex-col justify-between h-16 ${
                                                                printLayout === 'v2' ? 'bg-brand-600 text-white border-brand-600 shadow' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            <span>2 вертикально</span>
                                                            <span className="text-[10px] opacity-80 font-normal">А5 х 2 Ландшафт</span>
                                                        </button>
                                                        <button
                                                            onClick={() => setPrintLayout('v3')}
                                                            className={`p-2 rounded text-xs font-bold border transition text-left flex flex-col justify-between h-16 ${
                                                                printLayout === 'v3' ? 'bg-brand-600 text-white border-brand-600 shadow' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            <span>3 вертикально</span>
                                                            <span className="text-[10px] opacity-80 font-normal">А5 х 3 Ландшафт</span>
                                                        </button>
                                                        <button
                                                            onClick={() => setPrintLayout('grid4')}
                                                            className={`p-2 rounded text-xs font-bold border transition text-left flex flex-col justify-between h-16 ${
                                                                printLayout === 'grid4' ? 'bg-brand-600 text-white border-brand-600 shadow' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            <span>4 на листе (2х2)</span>
                                                            <span className="text-[10px] opacity-80 font-normal">A6 x 4 Портрет</span>
                                                        </button>
                                                        <button
                                                            onClick={() => setPrintLayout('grid6')}
                                                            className={`p-2 rounded text-xs font-bold border transition text-left flex flex-col justify-between h-16 ${
                                                                printLayout === 'grid6' ? 'bg-brand-600 text-white border-brand-600 shadow' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            <span>6 на листе (2х3)</span>
                                                            <span className="text-[10px] opacity-80 font-normal">Малый х 6 Портрет</span>
                                                        </button>
                                                        <button
                                                            onClick={() => setPrintLayout('grid8')}
                                                            className={`p-2 rounded text-xs font-bold border transition text-left flex flex-col justify-between h-16 ${
                                                                printLayout === 'grid8' ? 'bg-brand-600 text-white border-brand-600 shadow' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            <span>8 на листе (2х4)</span>
                                                            <span className="text-[10px] opacity-80 font-normal">Малый х 8 Портрет</span>
                                                        </button>
                                                        <button
                                                            onClick={() => setPrintLayout('grid12')}
                                                            className={`p-2 rounded text-xs font-bold border transition text-left flex flex-col justify-between h-16 ${
                                                                printLayout === 'grid12' ? 'bg-brand-600 text-white border-brand-600 shadow' : 'bg-white text-slate-700 border-slate-300 hover:bg-slate-50'
                                                            }`}
                                                        >
                                                            <span>12 на листе (3х4)</span>
                                                            <span className="text-[10px] opacity-80 font-normal">Мини х 12 Портрет</span>
                                                        </button>
                                                    </div>
                                                </div>
                                            ) : (
                                                <div className="bg-slate-50 border border-slate-200 rounded-xl p-3 space-y-3">
                                                    <div className="text-xs font-bold text-slate-700 border-b pb-1 mb-2 uppercase tracking-wide flex justify-between items-center">
                                                        <span>Конфигуратор размещения</span>
                                                        <span className="text-[10px] text-brand-600">Активен</span>
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label className="block text-[11px] font-semibold text-slate-600 mb-1">Колонки (1-5)</label>
                                                            <input
                                                                type="number" min="1" max="5"
                                                                value={customPrintConfig.columns}
                                                                onChange={(e) => setCustomPrintConfig(prev => ({ ...prev, columns: Math.max(1, Math.min(5, parseInt(e.target.value) || 1)) }))}
                                                                className="w-full text-xs border border-slate-300 rounded p-1 font-bold text-center"
                                                            />
                                                        </div>
                                                        <div>
                                                            <label className="block text-[11px] font-semibold text-slate-600 mb-1">Строки (1-8)</label>
                                                            <input
                                                                type="number" min="1" max="8"
                                                                value={customPrintConfig.rows}
                                                                onChange={(e) => setCustomPrintConfig(prev => ({ ...prev, rows: Math.max(1, Math.min(8, parseInt(e.target.value) || 1)) }))}
                                                                className="w-full text-xs border border-slate-300 rounded p-1 font-bold text-center"
                                                            />
                                                        </div>
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label className="block text-[11px] font-semibold text-slate-600 mb-0.5">Разрыв (мм)</label>
                                                            <input
                                                                type="range" min="0" max="15" step="0.5"
                                                                value={customPrintConfig.gap}
                                                                onChange={(e) => setCustomPrintConfig(prev => ({ ...prev, gap: parseFloat(e.target.value) }))}
                                                                className="w-full accent-brand-600"
                                                            />
                                                            <div className="text-right text-[10px] font-bold text-slate-500">{customPrintConfig.gap} мм</div>
                                                        </div>
                                                        <div>
                                                            <label className="block text-[11px] font-semibold text-slate-600 mb-0.5">Масштаб этикетки</label>
                                                            <input
                                                                type="range" min="0.1" max="1.5" step="0.05"
                                                                value={customPrintConfig.scale}
                                                                onChange={(e) => setCustomPrintConfig(prev => ({ ...prev, scale: parseFloat(e.target.value) }))}
                                                                className="w-full accent-brand-600"
                                                            />
                                                            <div className="text-right text-[10px] font-bold text-slate-500">{Math.round(customPrintConfig.scale * 100)}%</div>
                                                        </div>
                                                    </div>

                                                    <div className="grid grid-cols-2 gap-2">
                                                        <div>
                                                            <label className="block text-[11px] font-semibold text-slate-600 mb-1">Ориентация А4</label>
                                                            <select
                                                                value={customPrintConfig.pageOrientation}
                                                                onChange={(e) => setCustomPrintConfig(prev => ({ ...prev, pageOrientation: e.target.value }))}
                                                                className="w-full text-xs border border-slate-300 rounded p-1 font-bold"
                                                            >
                                                                <option value="portrait">Портретная</option>
                                                                <option value="landscape">Альбомная</option>
                                                            </select>
                                                        </div>
                                                        <div>
                                                            <label className="block text-[11px] font-semibold text-slate-600 mb-1">Количество (шт)</label>
                                                            <input
                                                                type="number" min="1" max="40"
                                                                value={customPrintConfig.labelCount}
                                                                onChange={(e) => setCustomPrintConfig(prev => ({ ...prev, labelCount: Math.max(1, Math.min(40, parseInt(e.target.value) || 1)) }))}
                                                                className="w-full text-xs border border-slate-300 rounded p-1 font-bold text-center"
                                                            />
                                                        </div>
                                                    </div>
                                                </div>
                                            )}
                                        </div>
                                    </div>

                                    {/* Footer save/export items */}
                                    <div className="p-4 border-t border-slate-200 bg-slate-50/70 rounded-b-xl flex flex-col space-y-3">
                                        <div className="flex space-y-2 sm:space-y-0 sm:space-x-2 flex-col sm:flex-row">
                                            <input
                                                type="text"
                                                placeholder="Имя нового шаблона..."
                                                value={customName}
                                                onChange={(e) => setCustomName(e.target.value)}
                                                className="flex-1 text-xs border border-slate-300 rounded-lg p-2"
                                            />
                                            <button
                                                onClick={saveTemplate}
                                                className="px-4 py-2 bg-brand-600 hover:bg-brand-700 text-white font-bold rounded-lg text-xs flex items-center justify-center space-x-1.5 shadow"
                                            >
                                                <Icon name="save" className="w-4 h-4" />
                                                <span>Сохранить в PHP</span>
                                            </button>
                                        </div>

                                        <div className="grid grid-cols-2 gap-2 pt-2 border-t border-slate-200/50">
                                            <button
                                                onClick={exportAsImage}
                                                className="px-3 py-1.5 bg-emerald-50 hover:bg-emerald-100 border border-emerald-200 rounded-lg text-emerald-800 font-bold text-xs flex items-center justify-center space-x-1 transition"
                                                title="Скачать этикетку в виде качественного PNG изображения"
                                            >
                                                <Icon name="image" className="w-3.5 h-3.5" />
                                                <span>Скачать PNG</span>
                                            </button>
                                            <button
                                                onClick={exportAsWord}
                                                className="px-3 py-1.5 bg-blue-50 hover:bg-blue-100 border border-blue-200 rounded-lg text-blue-800 font-bold text-xs flex items-center justify-center space-x-1 transition"
                                                title="Экспортировать этикетку как встроенный рисунок в документ Microsoft Word"
                                            >
                                                <Icon name="fileText" className="w-3.5 h-3.5" />
                                                <span>В MS Word (.doc)</span>
                                            </button>
                                        </div>

                                        <div className="flex justify-between items-center pt-2">
                                            <button
                                                onClick={exportTemplates}
                                                className="px-3 py-1.5 bg-slate-100 border rounded-lg text-slate-700 font-semibold text-xs flex items-center space-x-1"
                                            >
                                                <Icon name="download" className="w-3.5 h-3.5" />
                                                <span>Экспорт шаблона</span>
                                            </button>

                                            <label className="px-3 py-1.5 bg-slate-100 border rounded-lg text-slate-700 font-semibold text-xs flex items-center space-x-1 cursor-pointer">
                                                <Icon name="upload" className="w-3.5 h-3.5" />
                                                <span>Импорт шаблона</span>
                                                <input type="file" accept=".json" onChange={handleImport} className="hidden" />
                                            </label>
                                        </div>
                                    </div>
                                </div>
                            </>
                        )}

                            {activeTab === 'templates' && (
                                <div className="col-span-12 bg-white rounded-xl shadow-md p-6 border border-slate-200">
                                    <h3 className="text-lg font-bold text-slate-800 mb-4 flex items-center space-x-2">
                                        <Icon name="fileText" className="w-5 h-5 text-brand-600" />
                                        <span>Ваши сохраненные шаблоны (База JSON PHP)</span>
                                    </h3>

                                    {savedTemplates.length === 0 ? (
                                        <div className="text-center py-12 border-2 border-dashed border-slate-200 rounded-xl bg-slate-50/50">
                                            <p className="text-slate-500 font-medium">У вас пока нет сохраненных шаблонов в templates.json.</p>
                                        </div>
                                    ) : (
                                        <div className="grid grid-cols-1 md:grid-cols-2 lg:grid-cols-3 gap-4">
                                            {savedTemplates.map((t) => (
                                                <div key={t.id} className="p-4 border border-slate-200 rounded-xl hover:shadow-md transition bg-slate-50 flex flex-col justify-between">
                                                    <div>
                                                        <div className="flex justify-between items-start mb-2">
                                                            <h4 className="font-bold text-slate-800 truncate pr-2">{t.name}</h4>
                                                            <span className="text-[10px] bg-brand-100 text-brand-800 px-2 py-0.5 rounded-full font-bold">
                                                                {t.productType}
                                                            </span>
                                                        </div>
                                                        <p className="text-xs text-slate-500 mb-1 truncate">{t.subtitle}</p>
                                                        <p className="text-xs text-slate-400">Спирт: {t.alcohol} | Сахар: {t.sugar}</p>
                                                    </div>
                                                    <div className="flex justify-end space-x-2 mt-4 pt-3 border-t border-slate-200/60">
                                                        <button
                                                            onClick={() => {
                                                                setForm({ ...t });
                                                                setActiveTab('editor');
                                                            }}
                                                            className="px-3 py-1 bg-brand-600 hover:bg-brand-700 text-white rounded text-xs font-bold flex items-center space-x-1"
                                                        >
                                                            <Icon name="cog" className="w-3.5 h-3.5" />
                                                            <span>Открыть</span>
                                                        </button>
                                                        <button
                                                            onClick={() => deleteTemplate(t.id)}
                                                            className="px-2 py-1 bg-red-100 hover:bg-red-200 text-red-700 rounded text-xs font-semibold"
                                                        >
                                                            <Icon name="trash" className="w-3.5 h-3.5" />
                                                        </button>
                                                    </div>
                                                </div>
                                            ))}
                                        </div>
                                    )}
                                </div>
                            )}
                        </main>
                    </div>

                    {/* Print outputs layout */}
                    <div className={`hidden print:block print-container ${customPrintConfig.active ? 'custom-active' : `print-layout-${printLayout}`}`}>
                        {customPrintConfig.active ? (
                            (() => {
                                const pageWidth = customPrintConfig.pageOrientation === 'landscape' ? 297 : 210;
                                const pageHeight = customPrintConfig.pageOrientation === 'landscape' ? 210 : 297;
                                const cellWidth = (pageWidth - (customPrintConfig.columns - 1) * customPrintConfig.gap) / customPrintConfig.columns;
                                const cellHeight = (pageHeight - (customPrintConfig.rows - 1) * customPrintConfig.gap) / customPrintConfig.rows;
                                return (
                                    <div
                                        className="custom-print-grid"
                                        style={{
                                            display: 'grid',
                                            gridTemplateColumns: `repeat(${customPrintConfig.columns}, ${cellWidth}mm)`,
                                            gridTemplateRows: `repeat(${customPrintConfig.rows}, ${cellHeight}mm)`,
                                            gap: `${customPrintConfig.gap}mm`,
                                            width: `${pageWidth}mm`,
                                            height: `${pageHeight}mm`,
                                            boxSizing: 'border-box'
                                        }}
                                    >
                                        {Array.from({ length: customPrintConfig.labelCount }).map((_, i) => (
                                            <div
                                                key={i}
                                                style={{
                                                    width: `${cellWidth}mm`,
                                                    height: `${cellHeight}mm`,
                                                    position: 'relative',
                                                    overflow: 'hidden',
                                                    boxSizing: 'border-box',
                                                    border: '1px dashed #ccc'
                                                }}
                                            >
                                                <div
                                                    className="print-label"
                                                    style={{
                                                        width: '210mm',
                                                        height: '148mm',
                                                        transform: `scale(${customPrintConfig.scale})`,
                                                        transformOrigin: 'top left',
                                                        position: 'absolute'
                                                    }}
                                                >
                                                    <LabelPreview data={form} selectedId={null} />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                );
                            })()
                        ) : (
                            <>
                                {printLayout === 'h1' && (
                                    <div className="print-label">
                                        <LabelPreview data={form} selectedId={null} />
                                    </div>
                                )}
                                {printLayout === 'h2' && (
                                    <>
                                        <div className="print-label">
                                            <LabelPreview data={form} selectedId={null} />
                                        </div>
                                        <div className="print-label">
                                            <LabelPreview data={form} selectedId={null} />
                                        </div>
                                    </>
                                )}
                                {printLayout === 'v2' && (
                                    <div className="flex-landscape-container">
                                        <div className="vertical-print-col">
                                            <div className="print-label rotated-print-label">
                                                <LabelPreview data={form} selectedId={null} />
                                            </div>
                                        </div>
                                        <div className="vertical-print-col">
                                            <div className="print-label rotated-print-label">
                                                <LabelPreview data={form} selectedId={null} />
                                            </div>
                                        </div>
                                    </div>
                                )}
                                {printLayout === 'v3' && (
                                    <div className="flex-landscape-container-v3">
                                        <div className="vertical-print-col-v3">
                                            <div className="print-label rotated-print-label-v3">
                                                <LabelPreview data={form} selectedId={null} />
                                            </div>
                                        </div>
                                        <div className="vertical-print-col-v3">
                                            <div className="print-label rotated-print-label-v3">
                                                <LabelPreview data={form} selectedId={null} />
                                            </div>
                                        </div>
                                        <div className="vertical-print-col-v3">
                                            <div className="print-label rotated-print-label-v3">
                                                <LabelPreview data={form} selectedId={null} />
                                            </div>
                                        </div>
                                    </div>
                                )}
                                {printLayout === 'grid4' && (
                                    <div className="grid-4-container">
                                        {Array.from({ length: 4 }).map((_, i) => (
                                            <div key={i} className="grid-item-wrapper">
                                                <div className="print-label">
                                                    <LabelPreview data={form} selectedId={null} />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                                {printLayout === 'grid6' && (
                                    <div className="grid-6-container">
                                        {Array.from({ length: 6 }).map((_, i) => (
                                            <div key={i} className="grid-item-wrapper-6">
                                                <div className="print-label">
                                                    <LabelPreview data={form} selectedId={null} />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                                {printLayout === 'grid8' && (
                                    <div className="grid-8-container">
                                        {Array.from({ length: 8 }).map((_, i) => (
                                            <div key={i} className="grid-item-wrapper-8">
                                                <div className="print-label">
                                                    <LabelPreview data={form} selectedId={null} />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                                {printLayout === 'grid12' && (
                                    <div className="grid-12-container">
                                        {Array.from({ length: 12 }).map((_, i) => (
                                            <div key={i} className="grid-item-wrapper-12">
                                                <div className="print-label">
                                                    <LabelPreview data={form} selectedId={null} />
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                )}
                            </>
                        )}
                    </div>
                </div>
            );
        }

        // Complete Label layout renderer
        function LabelPreview({ data, selectedId, onSelectElement, onDragElement, showGrid = false }) {
            const barcodeRef = useRef(null);
            const qrcodeRef = useRef(null);
            const dragInfo = useRef({ active: false, elementId: null, startX: 0, startY: 0 });

            useEffect(() => {
                if (barcodeRef.current && data.barcode && data.barcode.trim().length > 0) {
                    try {
                        const fmt = data.barcodeFormat || "EAN13";
                        JsBarcode(barcodeRef.current, data.barcode, {
                            format: fmt,
                            width: 1.1,
                            height: 30,
                            displayValue: true,
                            font: "PT Sans Narrow",
                            fontSize: 10,
                            margin: 1
                        });
                    } catch (e) {
                        console.error(e);
                    }
                }
            }, [data.barcode, data.barcodeFormat]);

            useEffect(() => {
                if (qrcodeRef.current && data.qrCode) {
                    qrcodeRef.current.innerHTML = "";
                    try {
                        new QRCode(qrcodeRef.current, {
                            text: data.qrCode,
                            width: 38,
                            height: 38,
                            colorDark: "#000000",
                            colorLight: "#ffffff",
                            correctLevel: QRCode.CorrectLevel.M
                        });
                    } catch (e) {
                        console.error(e);
                    }
                }
            }, [data.qrCode]);

            const handleMouseDown = (e, elementId) => {
                if (!onDragElement || !onSelectElement) return;

                // Prevent trigger deselect click on parent container
                e.stopPropagation();
                e.preventDefault();

                onSelectElement(elementId);
                dragInfo.current = {
                    active: true,
                    elementId,
                    startX: e.clientX,
                    startY: e.clientY
                };
                document.addEventListener('mousemove', handleMouseMove);
                document.addEventListener('mouseup', handleMouseUp);
            };

            const handleMouseMove = (e) => {
                if (!dragInfo.current.active) return;
                const dx = (e.clientX - dragInfo.current.startX) / 3.779;
                const dy = (e.clientY - dragInfo.current.startY) / 3.779;
                if (Math.abs(dx) >= 0.5 || Math.abs(dy) >= 0.5) {
                    onDragElement(dragInfo.current.elementId, dx, dy);
                    dragInfo.current.startX = e.clientX;
                    dragInfo.current.startY = e.clientY;
                }
            };

            const handleMouseUp = (e) => {
                if (dragInfo.current.active) {
                    dragInfo.current.active = false;
                }
                document.removeEventListener('mousemove', handleMouseMove);
                document.removeEventListener('mouseup', handleMouseUp);
            };

            const el = {
                header: { x: 105, y: 4, size: 10, visible: true, ...(data.elements?.header || {}) },
                title: { x: 105, y: 12, size: 34, visible: true, ...(data.elements?.title || {}) },
                subtitle: { x: 105, y: 26, size: 8, visible: true, ...(data.elements?.subtitle || {}) },
                stats: { x: 4, y: 10, size: 12, visible: true, ...(data.elements?.stats || {}) },
                icons: { x: 178, y: 5, size: 38, visible: true, ...(data.elements?.icons || {}) },
                body: { x: 6, y: 52, size: 6.5, visible: true, ...(data.elements?.body || {}) },
                barcode: { x: 140, y: 88, size: 40, visible: true, ...(data.elements?.barcode || {}) },
                qrcode: { x: 182, y: 15, size: 22, visible: true, ...(data.elements?.qrcode || {}) }
            };

            const isSel = (id) => selectedId === id;

            return (
                <div
                    className={`label-preview-container select-none text-black relative border border-slate-300 bg-white ${showGrid ? 'grid-background' : ''}`}
                    onMouseDown={() => onSelectElement && onSelectElement(null)}
                >
                    {/* Brand header */}
                    {el.header.visible !== false && (
                        <div
                            style={{ left: `${el.header.x}mm`, top: `${el.header.y}mm`, fontSize: `${el.header.size}px` }}
                            onMouseDown={(e) => handleMouseDown(e, 'header')}
                            onClick={(e) => e.stopPropagation()}
                            className={`absolute -translate-x-1/2 text-center flex items-center justify-center space-x-1.5 draggable-element ${isSel('header') ? 'draggable-selected' : ''}`}
                        >
                            <svg className="w-3.5 h-3.5 text-black shrink-0" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                                <circle cx="12" cy="12" r="10" />
                                <path d="M12 2v20M2 12h20M6 6l12 12M6 18L18 6" />
                            </svg>
                            <span className="font-bold tracking-[0.25em] font-narrow uppercase shrink-0 leading-none">{data.brandName || 'БРЕНД'}</span>
                        </div>
                    )}

                    {/* Main Category */}
                    {el.title.visible !== false && (
                        <div
                            style={{ left: `${el.title.x}mm`, top: `${el.title.y}mm` }}
                            onMouseDown={(e) => handleMouseDown(e, 'title')}
                            onClick={(e) => e.stopPropagation()}
                            className={`absolute -translate-x-1/2 text-center draggable-element ${isSel('title') ? 'draggable-selected' : ''}`}
                        >
                            <h2 style={{ fontSize: `${el.title.size}px` }} className="font-black tracking-[0.1em] leading-none font-narrow m-0 py-0 uppercase">
                                {data.productType || 'НАПИТОК'}
                            </h2>
                        </div>
                    )}

                    {/* Subtitle / Standards */}
                    {el.subtitle.visible !== false && (
                        <div
                            style={{ left: `${el.subtitle.x}mm`, top: `${el.subtitle.y}mm` }}
                            onMouseDown={(e) => handleMouseDown(e, 'subtitle')}
                            onClick={(e) => e.stopPropagation()}
                            className={`absolute -translate-x-1/2 w-[85%] text-center draggable-element ${isSel('subtitle') ? 'draggable-selected' : ''}`}
                        >
                            <p style={{ fontSize: `${el.subtitle.size}px` }} className="font-bold uppercase tracking-wide leading-tight text-center">
                                {data.subtitle || 'Описание'}
                            </p>
                            <p style={{ fontSize: `${el.subtitle.size * 0.9}px` }} className="font-bold text-center mt-0.5 font-narrow">
                                {data.standard} {data.tiNumber ? `| ${data.tiNumber}` : ''}
                            </p>
                        </div>
                    )}

                    {/* Left stats parameters */}
                    {el.stats.visible !== false && (
                        <div
                            style={{ left: `${el.stats.x}mm`, top: `${el.stats.y}mm` }}
                            onMouseDown={(e) => handleMouseDown(e, 'stats')}
                            onClick={(e) => e.stopPropagation()}
                            className={`absolute w-[34mm] flex flex-col font-narrow border-t border-black/30 pt-1 draggable-element ${isSel('stats') ? 'draggable-selected' : ''}`}
                        >
                            <div className="pb-1 border-b border-black/30">
                                <div className="text-[6.5px] font-bold uppercase tracking-wider text-slate-700 leading-none">СПИРТ</div>
                                <div style={{ fontSize: `${el.stats.size}px` }} className="font-black leading-tight mt-0.5">{data.alcohol || '0'}</div>
                            </div>
                            <div className="py-1 border-b border-black/30">
                                <div className="text-[6.5px] font-bold uppercase tracking-wider text-slate-700 leading-none">ОБЪЕМ</div>
                                <div style={{ fontSize: `${el.stats.size}px` }} className="font-black leading-tight mt-0.5">{data.volume || '0 л'}</div>
                            </div>
                            <div className="pt-1">
                                <div className="text-[6.5px] font-bold uppercase tracking-wider text-slate-700 leading-none">САХАР</div>
                                <div style={{ fontSize: `${el.stats.size}px` }} className="font-black leading-tight mt-0.5">{data.sugar || '0'}</div>
                            </div>
                        </div>
                    )}

                    {/* Right regulatory icons */}
                    {el.icons.visible !== false && (
                        <div
                            style={{ left: `${el.icons.x}mm`, top: `${el.icons.y}mm` }}
                            onMouseDown={(e) => handleMouseDown(e, 'icons')}
                            onClick={(e) => e.stopPropagation()}
                            className={`absolute w-[26mm] flex flex-col items-center space-y-1.5 draggable-element ${isSel('icons') ? 'draggable-selected' : ''}`}
                        >
                            {data.eacActive && (
                                <span className="font-bold text-[14px] tracking-tight border border-black/90 px-1 rounded font-mono leading-none">
                                    EAC
                                </span>
                            )}

                            <div className="flex items-center space-x-1.5">
                                {data.foodActive && (
                                    <svg className="w-4 h-4 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                                        <path d="M7 3v7a4 4 0 0 0 8 0V3M11 3v7" />
                                        <path d="M12 14v6M8 20h8" />
                                    </svg>
                                )}

                                {data.petActive && (
                                    <div className="flex flex-col items-center relative">
                                        <svg className="w-4 h-4 text-black" viewBox="0 0 24 24" fill="none" stroke="currentColor" strokeWidth="1.8">
                                            <path d="M12 2l8 14H4L12 2z" />
                                        </svg>
                                        <span className="text-[5.5px] font-black absolute top-1.5 font-mono">1</span>
                                        <span className="text-[4.5px] font-bold tracking-tighter font-mono uppercase">PET</span>
                                    </div>
                                )}
                            </div>
                        </div>
                    )}

                    {/* Custom base64 uploaded images */}
                    {data.customImages && data.customImages.map(img => (
                        <img
                            key={img.id}
                            src={img.src}
                            style={{
                                left: `${img.x}mm`,
                                top: `${img.y}mm`,
                                width: `${img.width}mm`,
                                height: `${img.height}mm`,
                                opacity: img.opacity !== undefined ? img.opacity : 1,
                                display: img.visible !== false ? 'block' : 'none'
                            }}
                            onMouseDown={(e) => handleMouseDown(e, img.id)}
                            onClick={(e) => e.stopPropagation()}
                            className={`absolute draggable-element ${isSel(img.id) ? 'draggable-selected' : ''}`}
                            alt="Custom user element"
                        />
                    ))}

                    {/* Standalone Barcode Element */}
                    {el.barcode && el.barcode.visible !== false && data.barcode && (
                        <div
                            style={{
                                left: `${el.barcode.x}mm`,
                                top: `${el.barcode.y}mm`,
                                width: `${el.barcode.size || 40}mm`
                            }}
                            onMouseDown={(e) => handleMouseDown(e, 'barcode')}
                            onClick={(e) => e.stopPropagation()}
                            className={`absolute flex flex-col items-center justify-center draggable-element ${isSel('barcode') ? 'draggable-selected' : ''}`}
                        >
                            <svg ref={barcodeRef} style={{ width: '100%', height: 'auto' }}></svg>
                        </div>
                    )}

                    {/* Standalone QR Code Element */}
                    {el.qrcode && el.qrcode.visible !== false && data.qrCode && (
                        <div
                            style={{
                                left: `${el.qrcode.x}mm`,
                                top: `${el.qrcode.y}mm`,
                                width: `${el.qrcode.size || 22}mm`,
                                height: `${el.qrcode.size || 22}mm`
                            }}
                            onMouseDown={(e) => handleMouseDown(e, 'qrcode')}
                            onClick={(e) => e.stopPropagation()}
                            className={`absolute flex flex-col items-center justify-center bg-white p-0.5 border border-black/30 rounded draggable-element ${isSel('qrcode') ? 'draggable-selected' : ''}`}
                        >
                            <span className="text-[4px] font-bold tracking-tighter uppercase mb-0.5 leading-none font-sans">BY BEER/WINE</span>
                            <div ref={qrcodeRef} className="w-full h-full flex items-center justify-center overflow-hidden"></div>
                        </div>
                    )}

                    {/* Body columns / Composition */}
                    {el.body.visible !== false && (
                        <div
                            style={{ left: `${el.body.x}mm`, top: `${el.body.y}mm` }}
                            onMouseDown={(e) => handleMouseDown(e, 'body')}
                            onClick={(e) => e.stopPropagation()}
                            className={`absolute w-[94%] grid grid-cols-12 gap-3 draggable-element ${isSel('body') ? 'draggable-selected' : ''}`}
                        >
                            <div className="col-span-8 flex flex-col space-y-0.5 font-narrow">
                                <p style={{ fontSize: `${el.body.size}px` }} className="leading-[1.2] text-justify font-semibold">
                                    {data.ingredients}
                                </p>
                                <p style={{ fontSize: `${el.body.size}px` }} className="leading-[1.2] text-justify font-semibold text-slate-800">
                                    {data.nutrition}
                                </p>
                                <p style={{ fontSize: `${el.body.size}px` }} className="leading-[1.2] text-justify font-semibold text-slate-900">
                                    {data.storage}
                                </p>
                                <p style={{ fontSize: `${el.body.size * 1.1}px` }} className="font-bold text-left tracking-wider pt-0.5 uppercase">
                                    {data.expiration}
                                </p>
                            </div>

                            {/* Dates columns */}
                            <div className="col-span-4 flex flex-col items-end space-y-1 pr-1 justify-end">
                                <div className="w-full pl-3 space-y-0.5 font-narrow">
                                    <div className="flex justify-between items-center text-[7px] border-b border-black/40 pb-px">
                                        <span className="font-bold">Дата розлива</span>
                                        <span className="w-14 border-b border-black h-1.5"></span>
                                    </div>
                                    <div className="flex justify-between items-center text-[7px] border-b border-black/40 pb-px">
                                        <span className="font-bold">Дата подключения</span>
                                        <span className="w-14 border-b border-black h-1.5"></span>
                                    </div>
                                </div>
                            </div>
                        </div>
                    )}

                    {/* Legal health warning footer */}
                    <div
                        style={{
                            height: `${data.warningHeightPercent}%`,
                            top: `${100 - data.warningHeightPercent}%`
                        }}
                        className="absolute left-0 w-full bg-white border-t-2 border-black flex items-center justify-center px-4"
                    >
                        <h4 className="text-[9.5px] font-black tracking-wider leading-none text-center uppercase text-black font-narrow">
                            {data.warningText || 'ЧРЕЗМЕРНОЕ УПОТРЕБЛЕНИЕ АЛКОГОЛЯ ВРЕДИТ ВАШЕМУ ЗДОРОВЬЮ'}
                        </h4>
                    </div>
                </div>
            );
        }

        const container = document.getElementById('root');
        const root = ReactDOM.createRoot(container);
        root.render(<App />);
    </script>
</body>
</html>
