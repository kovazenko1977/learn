<?php
require_once __DIR__ . '/includes/SanatoriumManager.php';
require_once __DIR__ . '/includes/ProgramManager.php';

$info = SanatoriumManager::getInfo();
$programs = ProgramManager::getAll();
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?= htmlspecialchars($info['name'] ?? 'Система бронирования') ?></title>
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>
    <div id="booking-app">
        <header>
            <h1><?= htmlspecialchars($info['name'] ?? 'Бронирование путевки') ?></h1>
            <p><?= htmlspecialchars($info['address'] ?? 'Санаторий и оздоровительный центр') ?></p>
        </header>

        <main id="steps-container">
            <!-- Step 1: Program and Dates -->
            <section id="step-1" class="step active">
                <h2>Выберите программу и даты</h2>
                <div class="form-group">
                    <label>Лечебная программа</label>
                    <div id="program-selector" class="grid-selector">
                        <?php foreach($programs as $p): ?>
                            <div class="card-item" data-id="<?= $p['id'] ?>">
                                <strong><?= htmlspecialchars($p['name']) ?></strong>
                                <div class="small"><?= $p['duration'] ?> дней</div>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>Дата заезда</label>
                    <input type="date" id="check-in" min="<?= date('Y-m-d') ?>">
                </div>
                <div class="nav-buttons">
                    <span></span>
                    <button class="btn btn-primary next-step" data-next="2">Далее</button>
                </div>
            </section>

            <!-- Step 2: Rooms -->
            <section id="step-2" class="step">
                <h2>Выберите тип размещения</h2>
                <div id="room-selector" class="grid-selector">
                    <!-- Loaded dynamically -->
                </div>
                <div class="nav-buttons">
                    <button class="btn prev-step" data-prev="1">Назад</button>
                    <button class="btn btn-primary next-step" data-next="3">Далее</button>
                </div>
            </section>

            <!-- Step 3: Guest Details -->
            <section id="step-3" class="step">
                <h2>Данные гостя</h2>
                <div class="form-group">
                    <label>ФИО</label>
                    <input type="text" id="guest-name" placeholder="Иванов Иван Иванович">
                </div>
                <div class="form-group">
                    <label>Email</label>
                    <input type="email" id="guest-email" placeholder="example@mail.ru">
                </div>
                <div class="form-group">
                    <label>Телефон</label>
                    <input type="tel" id="guest-phone" placeholder="+7 (999) 000-00-00">
                </div>
                <div class="form-group">
                    <label>Медицинские показания (кратко)</label>
                    <textarea id="guest-medical" rows="3"></textarea>
                </div>
                <div class="nav-buttons">
                    <button class="btn prev-step" data-prev="2">Назад</button>
                    <button class="btn btn-primary next-step" data-next="4">Итог</button>
                </div>
            </section>

            <!-- Step 4: Summary -->
            <section id="step-4" class="step">
                <h2>Подтверждение бронирования</h2>
                <div id="booking-summary">
                    <!-- Loaded dynamically -->
                </div>
                <div class="nav-buttons">
                    <button class="btn prev-step" data-prev="3">Назад</button>
                    <button class="btn btn-primary" id="confirm-booking">Подтвердить и оплатить</button>
                </div>
            </section>
        </main>
    </div>

    <script src="assets/js/booking-widget.js"></script>
</body>
</html>
