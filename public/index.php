<?php
require_once __DIR__ . '/../vendor/autoload.php';

use App\LabelGenerator;

$generator = new LabelGenerator();

$examples = [
    'chinazes' => [
        'type' => 'product',
        'product_name' => 'НАПИТОК СЛАБОАЛКОГОЛЬНЫЙ НАТУРАЛЬНЫЙ ГАЗИРОВАННЫЙ «ЧИНАЗЕС СО ВКУСОМ ГРЕЙПФРУТА И ГУАВЫ»',
        'regulatory' => 'СТБ 1122-2010, РЦ BY 690277551.004-2026',
        'composition' => 'медовое сброженное сусло, вода питьевая, сахар, вкусоароматическая добавка "Розовый грейпфрут" и (или) "Грейпфрут", регулятор кислотности - лимонная кислота, натуральные ароматизаторы, замутнитель эмульсионный для напитков и (или) эмульсия нейтральная, концентрат (экстракт) сока черной моркови, пищевые добавки: антиокислитель Е224, консервант Е202.',
        'nutrition' => 'углеводы – 7,0 г/100 мл.',
        'energy' => '60 ккал/100 мл (250 кДж/100 мл).',
        'storage' => 'Хранить в затемненных вентилируемых помещениях, не имеющих посторонних запахов, при температуре от 0 °С до 25 °С. После подключения ПЭТ-КЕГ к оборудованию для розлива, напитки следует хранить под давлением двуокиси углерода в течение 10 суток при температуре от 0 °С до 25 °С. Допускается наличие взвесей или осадка частиц используемого сырья.',
        'shelf_life' => '12 месяцев с даты розлива.',
        'manufacturer' => 'ОАО «Пищевой комбинат «Веселово», 222132, Республика Беларусь, Минская обл., Борисовский р-н, д.Веселово, ул.Заводская, 24 Тел.: (0177)933-400, е-mail:info@alco.by, www.alco.by',
        'warning' => 'Алкоголь противопоказан детям и подросткам до 18 лет, беременным и кормящим женщинам, лицам с заболеваниями нервной системы и внутренних органов. ЧРЕЗМЕРНОЕ УПОТРЕБЛЕНИЕ АЛКОГОЛЯ ВРЕДИТ ВАШЕМУ ЗДОРОВЬЮ',
        'params' => 'СПИРТ 5% | САХАР 70 г/л | ОБЪЕМ 30 Л',
        'barcode_data' => '4811173002809',
        'sign_keep_dry' => 'on',
        'sign_this_way_up' => 'on',
    ],
    'keg' => [
        'type' => 'transport',
        'consignee' => 'ООО "Пивоварня Север"',
        'destination' => 'г. Новосибирск, ул. Промышленная, 45',
        'package_count' => '50',
        'item_number' => '1/50',
        'gross_weight' => '62.5',
        'net_weight' => '50.0',
        'dimensions' => '40x40x60 см',
        'barcode_data' => 'KEG-50L-001',
        'sign_keep_dry' => 'on',
        'sign_this_way_up' => 'on',
    ],
];

$selectedData = $_POST;
if (isset($_GET['example']) && isset($examples[$_GET['example']])) {
    $selectedData = $examples[$_GET['example']];
}

$selectedType = $selectedData['type'] ?? $_POST['type'] ?? 'transport';

$formattedData = $generator->formatData($selectedData);
$barcodeSvg = $generator->generateBarcode($selectedData['barcode_data'] ?? '');

$fragile = isset($selectedData['sign_fragile']);
$keep_dry = isset($selectedData['sign_keep_dry']);
$this_way_up = isset($selectedData['sign_this_way_up']);

?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Печать этикеток ГОСТ 14192-96</title>
    <link rel="stylesheet" href="assets/css/label.css">
</head>
<body>
    <div class="container">
        <h1>Генератор этикеток</h1>

        <div class="example-buttons">
            Загрузить пример:
            <a href="?example=chinazes">Пиво "ЧИНАЗЕС" (Кега)</a>
            <a href="?example=keg">Транспортная (Кега)</a>
        </div>

        <form method="POST" action="index.php">
            <div class="form-group">
                <label>Тип этикетки:</label>
                <select name="type" onchange="this.form.submit()">
                    <option value="transport" <?= $selectedType === 'transport' ? 'selected' : '' ?>>Транспортная (ГОСТ 14192-96)</option>
                    <option value="product" <?= $selectedType === 'product' ? 'selected' : '' ?>>Товарная (Этикетка на кегу)</option>
                </select>
            </div>

            <?php if ($selectedType === 'transport'): ?>
                <div class="form-group">
                    <label>Получатель:</label>
                    <input type="text" name="consignee" value="<?= htmlspecialchars($selectedData['consignee'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Пункт назначения:</label>
                    <input type="text" name="destination" value="<?= htmlspecialchars($selectedData['destination'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Количество мест:</label>
                    <input type="text" name="package_count" value="<?= htmlspecialchars($selectedData['package_count'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Порядковый номер:</label>
                    <input type="text" name="item_number" value="<?= htmlspecialchars($selectedData['item_number'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Масса брутто (кг):</label>
                    <input type="text" name="gross_weight" value="<?= htmlspecialchars($selectedData['gross_weight'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Масса нетто (кг):</label>
                    <input type="text" name="net_weight" value="<?= htmlspecialchars($selectedData['net_weight'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Размеры:</label>
                    <input type="text" name="dimensions" value="<?= htmlspecialchars($selectedData['dimensions'] ?? '') ?>">
                </div>
            <?php else: ?>
                <div class="form-group">
                    <label>Наименование продукта:</label>
                    <textarea name="product_name"><?= htmlspecialchars($selectedData['product_name'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Нормативные документы:</label>
                    <input type="text" name="regulatory" value="<?= htmlspecialchars($selectedData['regulatory'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Состав:</label>
                    <textarea name="composition"><?= htmlspecialchars($selectedData['composition'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Пищевая ценность:</label>
                    <input type="text" name="nutrition" value="<?= htmlspecialchars($selectedData['nutrition'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Энергетическая ценность:</label>
                    <input type="text" name="energy" value="<?= htmlspecialchars($selectedData['energy'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Условия хранения:</label>
                    <textarea name="storage"><?= htmlspecialchars($selectedData['storage'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Срок годности:</label>
                    <input type="text" name="shelf_life" value="<?= htmlspecialchars($selectedData['shelf_life'] ?? '') ?>">
                </div>
                <div class="form-group">
                    <label>Изготовитель:</label>
                    <textarea name="manufacturer"><?= htmlspecialchars($selectedData['manufacturer'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Предупреждения:</label>
                    <textarea name="warning"><?= htmlspecialchars($selectedData['warning'] ?? '') ?></textarea>
                </div>
                <div class="form-group">
                    <label>Параметры (Спирт/Сахар/Объем):</label>
                    <input type="text" name="params" value="<?= htmlspecialchars($selectedData['params'] ?? '') ?>">
                </div>
            <?php endif; ?>

            <div class="form-group">
                <label>Данные штрих-кода:</label>
                <input type="text" name="barcode_data" value="<?= htmlspecialchars($selectedData['barcode_data'] ?? '') ?>">
            </div>
            <div class="form-group">
                <label><input type="checkbox" name="sign_fragile" <?= $fragile ? 'checked' : '' ?>> Хрупкое</label>
                <label><input type="checkbox" name="sign_keep_dry" <?= $keep_dry ? 'checked' : '' ?>> Беречь от влаги</label>
                <label><input type="checkbox" name="sign_this_way_up" <?= $this_way_up ? 'checked' : '' ?>> Верх</label>
            </div>
            <button type="submit">Обновить превью</button>
            <button type="button" onclick="window.print()">Печать</button>
        </form>

        <div class="label-preview <?= $selectedType === 'product' ? 'product-label' : '' ?>" id="label">
            <?php if ($selectedType === 'transport'): ?>
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
            <?php else: ?>
                <div class="product-name"><?= nl2br(htmlspecialchars($selectedData['product_name'] ?? '')) ?></div>
                <div class="regulatory-docs"><?= htmlspecialchars($selectedData['regulatory'] ?? '') ?></div>

                <div class="composition-box">
                    <strong>Состав:</strong> <?= htmlspecialchars($selectedData['composition'] ?? '') ?>
                </div>

                <div class="nutrition-box">
                    <strong>Пищевая ценность:</strong> <?= htmlspecialchars($selectedData['nutrition'] ?? '') ?><br>
                    <strong>Энергетическая ценность:</strong> <?= htmlspecialchars($selectedData['energy'] ?? '') ?>
                </div>

                <div class="storage-box">
                    <?= htmlspecialchars($selectedData['storage'] ?? '') ?><br>
                    <strong>Срок годности:</strong> <?= htmlspecialchars($selectedData['shelf_life'] ?? '') ?>
                </div>

                <div class="manufacturer-box">
                    <strong>Изготовитель:</strong> <?= htmlspecialchars($selectedData['manufacturer'] ?? '') ?>
                </div>

                <div class="warning-box">
                    <?= nl2br(htmlspecialchars($selectedData['warning'] ?? '')) ?>
                </div>

                <div class="params-box">
                    <?= htmlspecialchars($selectedData['params'] ?? '') ?>
                </div>
            <?php endif; ?>

            <div class="signs-container">
                <?php if ($fragile): ?>
                    <div class="sign-item"><?php include __DIR__ . '/assets/signs/fragile.svg'; ?></div>
                <?php endif; ?>
                <?php if ($keep_dry): ?>
                    <div class="sign-item"><?php include __DIR__ . '/assets/signs/keep_dry.svg'; ?></div>
                <?php endif; ?>
                <?php if ($this_way_up): ?>
                    <div class="sign-item"><?php include __DIR__ . '/assets/signs/this_way_up.svg'; ?></div>
                <?php endif; ?>
            </div>
            <div class="barcode-container">
                <?= $barcodeSvg ?>
                <div style="font-size: 8pt; margin-top: 1mm;"><?= htmlspecialchars($selectedData['barcode_data'] ?? '') ?></div>
            </div>
        </div>
    </div>
</body>
</html>
