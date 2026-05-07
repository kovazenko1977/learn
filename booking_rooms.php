<?php
require_once __DIR__ . '/core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Helpers\TextBlockManager;

$store = new JsonStore(__DIR__ . '/data');
$textManager = new TextBlockManager($store);
$intro = $textManager->getBySlug('booking_intro');

$config = json_decode(file_get_contents(__DIR__ . '/data/form_config.json'), true);
$fields = $config['fields'];
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($config['general']['title']); ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
    <style>
        .form-row { display: flex; gap: 10px; margin-bottom: 10px; flex-wrap: wrap; }
        .form-row > div { flex: 1; min-width: 200px; }
    </style>
</head>
<body>
    <div class="container">
        <h1><?php echo htmlspecialchars($config['general']['title']); ?></h1>
        <?php if ($intro): ?>
            <div class="intro-text" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #0078d4;">
                <?php echo $intro; ?>
            </div>
        <?php endif; ?>

        <div id="success-msg-template" style="display:none;">
            <?php echo $textManager->getBySlug('booking_success'); ?>
        </div>

        <div style="margin-bottom: 30px; text-align: center; display: flex; gap: 10px; justify-content: center;">
            <a href="booking_rooms.php" class="btn-primary" style="text-decoration: none;">Бронирование номеров</a>
            <a href="booking_sauna.php" class="btn-secondary" style="text-decoration: none; background: #6264a7; color: white;">Бронирование сауны (по часам)</a>
        </div>

        <form id="booking-form">
            <div class="form-row">
                <div class="form-group">
                    <label>Дата и время заезда</label>
                    <input type="datetime-local" name="check_in" id="check_in" required>
                </div>
                <div class="form-group">
                    <label>Дата и время выезда</label>
                    <input type="datetime-local" name="check_out" id="check_out" required>
                </div>
            </div>

            <?php if ($fields['persons']['enabled']): ?>
            <div class="form-group">
                <label><?php echo $fields['persons']['label']; ?></label>
                <input type="number" name="persons" id="persons" value="1" min="1" <?php echo $fields['persons']['required'] ? 'required' : ''; ?>>
            </div>
            <?php endif; ?>

            <button type="button" id="find-rooms">Найти свободные ресурсы</button>

            <div id="rooms-selection" style="display:none;">
                <h3>Выберите номер или услугу</h3>
                <div id="rooms-list"></div>
            </div>

            <div id="extra-options" style="display:none;">
                <h3>Дополнительно</h3>
                <?php if ($fields['package_id']['enabled']): ?>
                <div class="form-group">
                    <label><?php echo $fields['package_id']['label']; ?></label>
                    <select name="package_id" id="package_id">
                        <option value="">Без пакета</option>
                    </select>
                </div>
                <?php endif; ?>

                <div class="form-row">
                    <?php if ($fields['procedures']['enabled']): ?>
                    <div class="form-group">
                        <label><?php echo $fields['procedures']['label']; ?></label>
                        <div id="procedures-list"></div>
                    </div>
                    <?php endif; ?>

                    <?php if ($fields['services']['enabled']): ?>
                    <div class="form-group">
                        <label><?php echo $fields['services']['label']; ?></label>
                        <div id="services-list"></div>
                    </div>
                    <?php endif; ?>
                </div>
            </div>

            <div class="form-row">
                <?php if ($fields['client_name']['enabled']): ?>
                <div class="form-group">
                    <label><?php echo $fields['client_name']['label']; ?></label>
                    <input type="text" name="client_name" id="client_name" <?php echo $fields['client_name']['required'] ? 'required' : ''; ?>>
                </div>
                <?php endif; ?>

                <?php if ($fields['phone']['enabled']): ?>
                <div class="form-group">
                    <label><?php echo $fields['phone']['label']; ?></label>
                    <input type="tel" name="phone" id="phone" <?php echo $fields['phone']['required'] ? 'required' : ''; ?> placeholder="+...">
                </div>
                <?php endif; ?>
            </div>

            <div class="form-row">
                <?php if ($fields['citizenship']['enabled']): ?>
                <div class="form-group">
                    <label><?php echo $fields['citizenship']['label']; ?></label>
                    <input type="text" name="citizenship" id="citizenship" <?php echo $fields['citizenship']['required'] ? 'required' : ''; ?> placeholder="РФ, РБ и т.д.">
                </div>
                <?php endif; ?>

                <?php if ($fields['address']['enabled']): ?>
                <div class="form-group">
                    <label><?php echo $fields['address']['label']; ?></label>
                    <input type="text" name="address" id="address" <?php echo $fields['address']['required'] ? 'required' : ''; ?>>
                </div>
                <?php endif; ?>
            </div>

            <div id="price-display">
                Итоговая стоимость: <span id="total-price">0</span> руб.
            </div>

            <button type="submit" id="submit-booking" class="btn-primary">Забронировать</button>
        </form>
    </div>

    <script src="assets/js/booking.js"></script>
</body>
</html>
