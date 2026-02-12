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
            </div>

            <div class="form-group">
                <label>Телефон</label>
                <input type="tel" name="phone" id="phone" required>
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
