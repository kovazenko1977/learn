<?php require_once "auth.php";
$pageTitle = 'Полное руководство пользователя';
include 'includes/header.php';
?>

<div class="mica-card help-content">
    <header style="margin-bottom: 30px; border-bottom: 1px solid rgba(0,0,0,0.1); padding-bottom: 20px;">
        <h1 style="margin-bottom: 10px;">🚀 Sanatorium Booking System v8.0</h1>
        <p style="font-size: 1.1rem; color: #555;">Профессиональная система управления санаторием: от бронирования до аналитики.</p>
        <div style="background: rgba(52, 152, 219, 0.1); padding: 10px 15px; border-radius: 8px; display: inline-block; font-weight: 600; color: #2980b9;">
            Разработчик: wes.by Коваженко С.Б.
        </div>
    </header>

    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 30px;">

        <section class="help-section">
            <h3>🏠 Рабочий стол (Дашборд)</h3>
            <p>Центральный узел управления, отображающий ключевые показатели:</p>
            <ul>
                <li><strong>Финансовые показатели:</strong> Суммарный доход и прогноз на текущий месяц.</li>
                <li><strong>Загрузка номеров:</strong> Процент занятых номеров в реальном времени.</li>
                <li><strong>Популярность услуг:</strong> Рейтинг самых востребованных процедур и пакетов.</li>
                <li><strong>Быстрые действия:</strong> Переход к созданию бронирования в один клик.</li>
            </ul>
        </section>

        <section class="help-section">
            <h3>📅 Календарь занятости</h3>
            <p>Визуальная интерактивная сетка для контроля номерного фонда:</p>
            <ul>
                <li><strong>Цветовая индикация:</strong>
                    <span style="display:inline-block; width:12px; height:12px; background:#fff3cd; border:1px solid #ffeeba; border-radius:3px;"></span> Желтый (Резерв),
                    <span style="display:inline-block; width:12px; height:12px; background:#fde2e1; border:1px solid #f8d7da; border-radius:3px;"></span> Красный (Заселен).
                </li>
                <li><strong>Быстрое бронирование:</strong> Клик по пустой ячейке открывает форму предзаполненного бронирования на выбранную дату и номер.</li>
                <li><strong>Просмотр деталей:</strong> Клик по занятой ячейке вызывает окно с данными гостя, списком услуг и возможностью редактирования заметок администратора.</li>
            </ul>
        </section>

        <section class="help-section">
            <h3>🛎️ Оперативная работа (Сегодня)</h3>
            <p>Ежедневный список задач для администратора службы приема и размещения:</p>
            <ul>
                <li><strong>Заезды:</strong> Список гостей, прибывающих сегодня. Кнопка «Заселить» переводит бронь в статус «Проживает».</li>
                <li><strong>Выезды:</strong> Список гостей, покидающих санаторий. Позволяет быстро завершить бронирование.</li>
                <li><strong>Проживающие:</strong> Полный список текущих гостей с указанием номеров и контактных данных.</li>
            </ul>
        </section>

        <section class="help-section">
            <h3>📝 Планирование и Задачи</h3>
            <p>Внутренняя система управления поручениями для персонала:</p>
            <ul>
                <li><strong>Создание задач:</strong> Уборка номеров, техническое обслуживание, подготовка процедурных кабинетов.</li>
                <li><strong>Приоритеты:</strong> Разделение задач на «Низкий», «Средний» и «Высокий» уровни важности.</li>
                <li><strong>Статусы:</strong> Контроль выполнения текущих дел в рамках рабочего дня.</li>
            </ul>
        </section>

        <section class="help-section">
            <h3>👥 Управление гостями (Директория)</h3>
            <p>База данных всех посетителей с историей взаимодействий:</p>
            <ul>
                <li><strong>Профили:</strong> Хранение ФИО, телефона, гражданства и адреса проживания.</li>
                <li><strong>История посещений:</strong> Автоматическое связывание всех прошлых бронирований с профилем гостя по номеру телефона.</li>
                <li><strong>Статус лояльности:</strong> Отслеживание частоты посещений и предпочтений гостя.</li>
            </ul>
        </section>

        <section class="help-section">
            <h3>📊 Аналитика и Отчетность</h3>
            <p>Глубокий анализ эффективности работы учреждения:</p>
            <ul>
                <li><strong>Экспорт данных:</strong> Выгрузка всех бронирований в формат CSV для последующего анализа в Excel.</li>
                <li><strong>Динамика доходов:</strong> Графики поступлений денежных средств по периодам.</li>
                <li><strong>Эффективность услуг:</strong> Анализ рентабельности отдельных процедур и сервисов.</li>
            </ul>
        </section>

        <section class="help-section">
            <h3>⚙️ Ресурсы и Справочники</h3>
            <p>Настройка базовых элементов системы:</p>
            <ul>
                <li><strong>Номерной фонд:</strong> Добавление номеров с указанием их класса, цены и вместимости.</li>
                <li><strong>Медицина:</strong> Редактирование списка лечебных процедур и их стоимости.</li>
                <li><strong>Путёвки:</strong> Создание комплексных предложений (пакетов), включающих проживание и набор процедур.</li>
                <li><strong>Доп. услуги:</strong> Управление сервисами (парковка, трансфер, аренда оборудования).</li>
            </ul>
        </section>

        <section class="help-section">
            <h3>👥 Пользователи и Доступ</h3>
            <p>Система разграничения прав для сотрудников санатория:</p>
            <ul>
                <li><strong>Роли:</strong> Администратор (полный доступ) и Пользователь (ограниченный доступ).</li>
                <li><strong>Гранулярные права:</strong> Для каждого пользователя можно выбрать конкретные разделы, которые он может видеть или редактировать.</li>
                <li><strong>API Токены:</strong> Каждому пользователю автоматически присваивается уникальный ключ для доступа через мобильное приложение.</li>
            </ul>
        </section>

        <section class="help-section">
            <h3>🌐 Интеграция и API</h3>
            <p>Технические возможности расширения системы:</p>
            <ul>
                <li><strong>REST API:</strong> Современный программный интерфейс для связи с внешними сервисами и приложениями.</li>
                <li><strong>Текстовые блоки:</strong> Управление контентом на публичных страницах бронирования.</li>
                <li><strong>Импорт :</strong> Интеллектуальный парсинг данных с вашего существующего сайта.</li>
                <li><strong>Мобильное приложение:</strong> Система полностью готова к работе с мобильными клиентами через защищенные API-токены.</li>
            </ul>
        </section>
    </div>

    <section class="help-section" style="margin-top: 40px; padding: 25px; border-radius: 12px; background: linear-gradient(135deg, rgba(52, 152, 219, 0.1), rgba(155, 89, 182, 0.1)); border: 1px solid rgba(52, 152, 219, 0.2);">
        <h3>🛠️ Инструкция по внедрению</h3>
        <p>Для отображения формы онлайн-бронирования на вашем основном сайте, вставьте следующий фрагмент кода в нужное место страницы:</p>
        <pre style="background: #1e1e1e; color: #dcdcdc; padding: 20px; border-radius: 8px; font-family: 'Consolas', monospace; overflow-x: auto; font-size: 0.9rem; line-height: 1.5;">
&lt;!-- Подключение скрипта загрузчика --&gt;
&lt;script src="https://your-server.com/sanatorium-booking/embed/booking-loader.js"
        data-api-url="https://your-server.com/sanatorium-booking/api/v1.php"&gt;&lt;/script&gt;

&lt;!-- Контейнер для формы --&gt;
&lt;div id="sanatorium-booking-root"&gt;&lt;/div&gt;</pre>
        <p style="margin-top: 15px; font-size: 0.9rem; color: #666;">* Не забудьте заменить <code>your-server.com</code> на реальный адрес вашей установки.</p>
    </section>

    <footer style="margin-top: 50px; text-align: center; border-top: 1px solid rgba(0,0,0,0.05); padding-top: 30px;">
        <p style="color: #999; font-size: 0.9rem;">
            © 2024 Sanatorium Booking System. Все права защищены.<br>
            Техническая поддержка и разработка: <a href="https://wes.by" target="_blank" style="color: #3498db; text-decoration: none;">wes.by</a>
        </p>
    </footer>
</div>

<style>
.help-content h3 {
    color: #2c3e50;
    margin-top: 0;
    margin-bottom: 15px;
    display: flex;
    align-items: center;
    gap: 10px;
}
.help-section {
    background: rgba(255, 255, 255, 0.5);
    padding: 20px;
    border-radius: 12px;
    border: 1px solid rgba(0,0,0,0.05);
}
.help-section ul {
    margin: 0;
    padding-left: 20px;
}
.help-section li {
    margin-bottom: 8px;
    color: #444;
}
.help-content p {
    line-height: 1.6;
    margin-bottom: 15px;
}
</style>

<?php include 'includes/footer.php'; ?>
