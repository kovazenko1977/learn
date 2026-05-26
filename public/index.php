<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\LabelGenerator;

$generator = new LabelGenerator();

$data = [
    'consignee' => $_POST['consignee'] ?? 'ООО "Пример"',
    'destination' => $_POST['destination'] ?? 'г. Москва, ул. Примерная, 1',
    'package_count' => $_POST['package_count'] ?? '10',
    'item_number' => $_POST['item_number'] ?? '1/10',
    'gross_weight' => $_POST['gross_weight'] ?? '15.5',
    'net_weight' => $_POST['net_weight'] ?? '14.0',
    'dimensions' => $_POST['dimensions'] ?? '40x40x60 см',
    'barcode_data' => $_POST['barcode_data'] ?? '123456789012',
];

$formattedData = $generator->formatData($data);
$barcodeSvg = $generator->generateBarcode($data['barcode_data']);

$fragile = isset($_POST['sign_fragile']);
$keep_dry = isset($_POST['sign_keep_dry']);
$this_way_up = isset($_POST['sign_this_way_up']);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Печать этикеток ГОСТ 14192-96</title>
    <link rel="stylesheet" href="assets/css/label.css">
    <style>
        /* Дополнительные стили группировки */
        .group-header {
            font-size: 8pt;
            text-transform: uppercase;
            color: #666;
            margin-bottom: 1mm;
            border-bottom: 1px dashed #ccc;
        }
    </style>
</head>
<body>
    <div class="container">
        <h1>Генератор этикеток ГОСТ 14192-96</h1>
        <form method="POST">
            <div class="form-group">
                <label>Получатель:</label>
                <input type="text" name="consignee" value="<?= htmlspecialchars($data['consignee']) ?>">
            </div>
            <div class="form-group">
                <label>Пункт назначения:</label>
                <input type="text" name="destination" value="<?= htmlspecialchars($data['destination']) ?>">
            </div>
            <div class="form-group">
                <label>Количество мест:</label>
                <input type="text" name="package_count" value="<?= htmlspecialchars($data['package_count']) ?>">
            </div>
            <div class="form-group">
                <label>Порядковый номер:</label>
                <input type="text" name="item_number" value="<?= htmlspecialchars($data['item_number']) ?>">
            </div>
            <div class="form-group">
                <label>Масса брутто (кг):</label>
                <input type="text" name="gross_weight" value="<?= htmlspecialchars($data['gross_weight']) ?>">
            </div>
            <div class="form-group">
                <label>Масса нетто (кг):</label>
                <input type="text" name="net_weight" value="<?= htmlspecialchars($data['net_weight']) ?>">
            </div>
            <div class="form-group">
                <label>Размеры:</label>
                <input type="text" name="dimensions" value="<?= htmlspecialchars($data['dimensions']) ?>">
            </div>
            <div class="form-group">
                <label>Данные штрих-кода:</label>
                <input type="text" name="barcode_data" value="<?= htmlspecialchars($data['barcode_data']) ?>">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="sign_fragile" <?= $fragile ? 'checked' : '' ?>> Хрупкое</label>
                <label><input type="checkbox" name="sign_keep_dry" <?= $keep_dry ? 'checked' : '' ?>> Беречь от влаги</label>
                <label><input type="checkbox" name="sign_this_way_up" <?= $this_way_up ? 'checked' : '' ?>> Верх</label>
            </div>
            <button type="submit">Обновить превью</button>
            <button type="button" onclick="window.print()">Печать</button>
        </form>

        <div class="label-preview" id="label">
            <!-- Основные надписи -->
            <div class="label-section">
                <div class="group-header">Основные надписи</div>
                <div class="label-label">Получатель:</div>
                <div class="label-value"><?= htmlspecialchars($formattedData['consignee']) ?></div>
                <div class="label-label">Пункт назначения:</div>
                <div class="label-value"><?= htmlspecialchars($formattedData['destination']) ?></div>
            </div>

            <div class="label-section">
                <div class="label-row">
                    <div class="label-label">Кол-во мест:</div>
                    <div class="label-value"><?= htmlspecialchars($formattedData['package_count']) ?></div>
                </div>
                <div class="label-row">
                    <div class="label-label">Порядковый номер:</div>
                    <div class="label-value"><?= htmlspecialchars($formattedData['item_number']) ?></div>
                </div>
            </div>

            <!-- Информационные надписи -->
            <div class="label-section">
                <div class="group-header">Информационные надписи</div>
                <div class="label-row">
                    <div class="label-label">Брутто:</div>
                    <div class="label-value"><?= htmlspecialchars($formattedData['gross_weight']) ?></div>
                </div>
                <div class="label-row">
                    <div class="label-label">Нетто:</div>
                    <div class="label-value"><?= htmlspecialchars($formattedData['net_weight']) ?></div>
                </div>
                <div class="label-row">
                    <div class="label-label">Размеры:</div>
                    <div class="label-value"><?= htmlspecialchars($formattedData['dimensions']) ?></div>
                </div>
            </div>

            <div class="signs-container">
                <?php if ($fragile): ?>
                    <div class="sign-item">
                        <?php include __DIR__ . '/assets/signs/fragile.svg'; ?>
                    </div>
                <?php endif; ?>
                <?php if ($keep_dry): ?>
                    <div class="sign-item">
                        <?php include __DIR__ . '/assets/signs/keep_dry.svg'; ?>
                    </div>
                <?php endif; ?>
                <?php if ($this_way_up): ?>
                    <div class="sign-item">
                        <?php include __DIR__ . '/assets/signs/this_way_up.svg'; ?>
                    </div>
                <?php endif; ?>
            </div>
            <div class="barcode-container">
                <?= $barcodeSvg ?>
                <div style="font-size: 8pt; margin-top: 2mm;"><?= htmlspecialchars($data['barcode_data']) ?></div>
            </div>
        </div>
    </div>
</body>
</html>
