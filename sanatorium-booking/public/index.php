<?php
require_once __DIR__ . '/../core/autoload.php';
use Sanatorium\Core\Database\JsonStore;
use Sanatorium\Core\Helpers\TextBlockManager;

$store = new JsonStore(__DIR__ . '/../data');
$textManager = new TextBlockManager($store);
$intro = $textManager->getBySlug('booking_intro');
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <title>Бронирование номеров</title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div class="container">
        <h1>Онлайн-бронирование</h1>
        <?php if ($intro): ?>
            <div class="intro-text" style="margin-bottom: 20px; padding: 15px; background: #f8f9fa; border-radius: 8px; border-left: 4px solid #0078d4;">
                <?php echo $intro; ?>
            </div>
        <?php endif; ?>

        <div id="success-msg-template" style="display:none;">
            <?php echo $textManager->getBySlug('booking_success'); ?>
        </div>

        <form id="booking-form">
            <div class="form-group">
                <label>Дата заезда</label>
                <input type="date" name="check_in" id="check_in" required>
            </div>
            <div class="form-group">
                <label>Дата выезда</label>
                <input type="date" name="check_out" id="check_out" required>
            </div>
            <div class="form-group">
                <label>Количество человек</label>
                <input type="number" name="persons" id="persons" value="1" min="1" required>
            </div>
            <button type="button" id="find-rooms">Найти номера</button>

            <div id="rooms-selection" style="display:none;">
                <h3>Выберите номер</h3>
                <div id="rooms-list"></div>
            </div>

            <div id="extra-options" style="display:none;">
                <h3>Дополнительные услуги и пакеты</h3>
                <div class="form-group">
                    <label>Пакет (путевка)</label>
                    <select name="package_id" id="package_id">
                        <option value="">Без пакета</option>
                    </select>
                </div>
                <div class="form-group">
                    <label>Процедуры</label>
                    <div id="procedures-list"></div>
                </div>
                <div class="form-group">
                    <label>Дополнительные услуги</label>
                    <div id="services-list"></div>
                </div>
            </div>

            <div class="form-group">
                <label>Ваше имя</label>
                <input type="text" name="client_name" id="client_name" required>
            </div>

            <div class="form-group">
                <label>Телефон</label>
                <input type="tel" name="phone" id="phone" required placeholder="+...">
            </div>

            <div class="form-group">
                <label>Гражданство</label>
                <input type="text" name="citizenship" id="citizenship" placeholder="РФ, РБ и т.д.">
            </div>

            <div class="form-group">
                <label>Адрес проживания</label>
                <input type="text" name="address" id="address">
            </div>

            <div id="price-display">
                Итоговая стоимость: <span id="total-price">0</span> руб.
            </div>

            <button type="submit" id="submit-booking">Забронировать</button>
        </form>
    </div>

    <script src="assets/js/booking.js"></script>
</body>
</html>
