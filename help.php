<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="animation: slideDown 0.5s ease-out;">
        <h1>Справка и руководство</h1>
        <p style="color:var(--win-text-secondary);">Как работать с информационной системой ХОП</p>
    </div>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 24px; animation: slideUp 0.6s ease-out;">

        <section class="card mica">
            <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                <i data-lucide="users" style="color:var(--win-accent);"></i> Роли пользователей
            </h2>
            <ul style="padding-left:20px; font-size:14px; line-height:1.6;">
                <li><strong>Администратор:</strong> Полный доступ ко всем настройкам, справочникам и управлению персоналом.</li>
                <li><strong>Инициатор:</strong> Сотрудник, создающий заявки. Видит только свои обращения.</li>
                <li><strong>Исполнитель:</strong> Технический специалист. Работает со списком назначенных ему задач.</li>
                <li><strong>Ответственный (Lead):</strong> Начальник службы. Распределяет новые заявки между исполнителями своей службы.</li>
                <li><strong>Контролер:</strong> Проверяет качество выполненных работ перед закрытием заявки.</li>
            </ul>
        </section>

        <section class="card mica">
            <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                <i data-lucide="git-pull-request" style="color:var(--win-accent);"></i> Жизненный цикл заявки
            </h2>
            <ol style="padding-left:20px; font-size:14px; line-height:1.6;">
                <li><strong>Новая:</strong> Создана инициатором, ожидает распределения.</li>
                <li><strong>Назначена:</strong> Выбран исполнитель.</li>
                <li><strong>В работе:</strong> Исполнитель приступил к выполнению.</li>
                <li><strong>Проверка:</strong> Работа выполнена, ожидает подтверждения инициатором или контролером.</li>
                <li><strong>Выполнена/Закрыта:</strong> Работа принята и ушла в архив.</li>
                <li><strong>Доработка:</strong> Если работа не принята, она возвращается исполнителю.</li>
            </ol>
        </section>

        <section class="card mica">
            <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                <i data-lucide="bell-ring" style="color:var(--win-accent);"></i> Настройка Telegram
            </h2>
            <div style="font-size:14px; line-height:1.6;">
                <p>Для работы уведомлений необходимо:</p>
                <ol>
                    <li>Создать бота через <strong>@BotFather</strong> и получить Token.</li>
                    <li>Добавить бота в группу или написать ему в ЛС, чтобы получить Chat ID.</li>
                    <li>Ввести Token и Глобальный Chat ID в настройках системы.</li>
                    <li>Для персональных уведомлений укажите Telegram ID сотрудника в его профиле (раздел "Персонал").</li>
                </ol>
                <div style="background:rgba(0,120,212,0.05); padding:10px; border-radius:8px; font-size:12px; border-left:4px solid var(--win-accent);">
                    <strong>Совет:</strong> Используйте кнопку "Отправить тест" в настройках для проверки связи.
                </div>
            </div>
        </section>

        <section class="card mica">
            <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                <i data-lucide="database" style="color:var(--win-accent);"></i> Бэкап и Архивация
            </h2>
            <div style="font-size:14px; line-height:1.6;">
                <p>Все данные системы (заявки, пользователи, фото) хранятся в JSON-файлах. Для безопасности:</p>
                <ul>
                    <li>Регулярно скачивайте ZIP-архив в разделе "Настройки".</li>
                    <li>При сбое используйте функцию "Восстановить", загрузив актуальный архив.</li>
                    <li>Система поддерживает полную очистку (Reset) только при вводе пароля.</li>
                </ul>
            </div>
        </section>

        <section class="card mica">
            <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                <i data-lucide="smartphone" style="color:var(--win-accent);"></i> Мобильное использование
            </h2>
            <p style="font-size:14px; line-height:1.6;">
                Программа спроектирована как PWA (Progressive Web App). Вы можете добавить её на главный экран смартфона через меню браузера ("Добавить на экран 'Домой'"). Это позволит работать в полноэкранном режиме, как с обычным приложением.
            </p>
        </section>

        <section class="card mica">
            <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                <i data-lucide="help-circle" style="color:var(--win-accent);"></i> Поддержка
            </h2>
            <p style="font-size:14px; line-height:1.6;">
                Разработано: <strong>wes.by Коваженко С.Б.</strong><br>
                По техническим вопросам и предложениям обращайтесь к администратору вашей системы.
            </p>
        </section>

    </div>
</div>

<?php include 'includes/footer.php'; ?>
