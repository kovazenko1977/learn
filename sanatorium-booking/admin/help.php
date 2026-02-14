<?php require_once "auth.php";
$pageTitle = 'Справка и руководство';
include 'includes/header.php';
?>

<div class="mica-card help-content">
    <section class="help-section">
        <h2>🚀 Обзор системы</h2>
        <p><strong>Sanatorium Booking System</strong> — это комплексное решение для автоматизации процессов бронирования, управления номерным фондом и работы с гостями в санатории.</p>
    </section>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
        <section class="help-section mica-card" style="background: rgba(255,255,255,0.4);">
            <h3>📅 Календарь и Бронирование</h3>
            <ul>
                <li><strong>Сетка занятости:</strong> Наглядный календарь всех номеров.
                    <br><span style="color: #d4a017;">● Желтый</span> — бронь подтверждена (Reserved).
                    <br><span style="color: #a4262c;">● Красный</span> — гость уже проживает (Occupied/Checked-in).
                </li>
                <li><strong>Быстрое бронирование:</strong> Нажмите на свободную ячейку в календаре, чтобы создать бронь на выбранную дату.</li>
                <li><strong>Детали бронирования:</strong> Нажмите на занятую ячейку, чтобы увидеть данные гостя, сроки проживания и заметки администратора.</li>
            </ul>
        </section>

        <section class="help-section mica-card" style="background: rgba(255,255,255,0.4);">
            <h3>⚡ Оперативное управление</h3>
            <ul>
                <li><strong>Страница «Сегодня»:</strong> Основной хаб для администратора. Список заездов и выездов на текущий день, а также общая загрузка санатория.</li>
                <li><strong>Заселение и Выезд:</strong> Кнопки быстрого изменения статуса гостя доступны прямо на главной и на странице «Сегодня».</li>
                <li><strong>Планирование:</strong> Создание и контроль ежедневных административных задач (уборка, подготовка номеров, закупки).</li>
            </ul>
        </section>

        <section class="help-section mica-card" style="background: rgba(255,255,255,0.4);">
            <h3>🏨 Справочники (Ресурсы)</h3>
            <ul>
                <li><strong>Номера:</strong> Управление конкретными комнатами, их вместимостью и ценой.</li>
                <li><strong>Классы номеров:</strong> Категории (Люкс, Стандарт и т.д.) для удобной группировки.</li>
                <li><strong>Процедуры и Услуги:</strong> Каталог медицинских процедур и доп. сервисов (бассейн, трансфер) с указанием цен.</li>
                <li><strong>Пакеты (Путёвки):</strong> Готовые наборы услуг с фиксированной длительностью и ценой.</li>
            </ul>
        </section>

        <section class="help-section mica-card" style="background: rgba(255,255,255,0.4);">
            <h3>📊 Аналитика и Отчеты</h3>
            <ul>
                <li><strong>Дашборд:</strong> Оперативная статистика по доходам, популярности услуг и загрузке.</li>
                <li><strong>Экспорт в CSV:</strong> Выгрузка списка бронирований для работы в Excel.</li>
                <li><strong>Статусы:</strong> Система отслеживает жизненный цикл заявки: Новое → Подтверждено (Reserved) → Заселен (Booked) → Выехал (Confirmed).</li>
            </ul>
        </section>
    </div>

    <section class="help-section" style="margin-top: 30px; padding: 20px; border-radius: 12px; background: rgba(52, 152, 219, 0.1);">
        <h3>🌐 Интеграция на сайт</h3>
        <p>Для вывода формы бронирования на вашем сайте используйте следующий код:</p>
        <pre style="background: #f8f9fa; padding: 15px; border-radius: 8px; border: 1px solid #ddd; overflow-x: auto;">
&lt;script src="path/to/embed/booking-loader.js"
        data-api-url="https://your-domain.com/api/v1.php"&gt;&lt;/script&gt;
&lt;div id="sanatorium-booking-root"&gt;&lt;/div&gt;</pre>
    </section>

    <footer style="margin-top: 40px; text-align: center; color: #888; font-size: 0.9rem;">
        Sanatorium Booking System v8.0 | Разработано для корпоративного использования
    </footer>
</div>

<style>
.help-section h2, .help-section h3 {
    margin-top: 0;
    color: #2c3e50;
}
.help-section ul {
    padding-left: 20px;
}
.help-section li {
    margin-bottom: 10px;
}
.help-content p {
    line-height: 1.6;
}
</style>

<?php include 'includes/footer.php'; ?>
