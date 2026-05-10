<?php require_once "auth.php";
$pageTitle = 'Пошаговое руководство';
include 'includes/header.php';
?>

<div class="mica-card help-content" style="max-width: 1100px; margin: 0 auto; padding: 40px;">
    <div style="text-align: center; margin-bottom: 50px;">
        <h1 style="font-size: 2.8rem; margin-bottom: 10px; background: linear-gradient(90deg, var(--primary-color), #00b7ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent;">🚀 Руководство Sanatorium Pro</h1>
        <p style="font-size: 1.2rem; color: #64748b;">Полный цикл управления: от первой настройки до глубокой аналитики</p>
    </div>

    <div class="step-container">
        <!-- Введение -->
        <div class="step-item">
            <div class="step-number">01</div>
            <div class="step-body">
                <h3>Вход и первый запуск</h3>
                <p>Ваша система по умолчанию настроена на "Открытый доступ". Это значит, что при первом запуске пароль не требуется.</p>
                <div class="tip-box">
                    <strong>Совет:</strong> Если вы планируете работать через интернет, первым делом зайдите в <strong>"Настройки"</strong> и включите <strong>"Защиту паролем"</strong>. Данные администратора по умолчанию: <code>admin / admin123</code>.
                </div>
            </div>
        </div>

        <!-- Настройка -->
        <div class="step-item">
            <div class="step-number">02</div>
            <div class="step-body">
                <h3>Настройка ресурсов</h3>
                <p>Перейдите в блок меню <strong>"Ресурсы"</strong>. Здесь вы строите фундамент вашей системы:</p>
                <ul>
                    <li><strong>Классы номеров:</strong> Определите типы (Эконом, Стандарт, Сауна, VIP).</li>
                    <li><strong>Номера:</strong> Добавьте конкретные комнаты.
                        <ul>
                            <li>Для жилых номеров указывайте цену за сутки.</li>
                            <li>Для саун обязательно укажите <strong>"Цену за час"</strong>.</li>
                        </ul>
                    </li>
                    <li><strong>Процедуры и услуги:</strong> Наполните прайс-лист услугами (Массаж, Трансфер, Завтрак).</li>
                </ul>
            </div>
        </div>

        <!-- Бронирование -->
        <div class="step-item">
            <div class="step-number">03</div>
            <div class="step-body">
                <h3>Работа с бронированиями</h3>
                <p>Система предлагает 3 способа управления бронью:</p>
                <ul>
                    <li><strong>Шахматка (Календарь):</strong> Наглядная сетка для номеров. Клик на пустую дату — быстрая бронь. Клик на занятую — детали и редактирование.</li>
                    <li><strong>График Сауны:</strong> Детальное почасовое расписание. Здесь можно "двигать" записи по часам и видеть окна в расписании.</li>
                    <li><strong>Список бронирований:</strong> Таблица с поиском и фильтрами. Нажмите на <strong>ID (# номер)</strong> любой брони, чтобы открыть её полное редактирование.</li>
                </ul>
                <div class="tip-box" style="background: rgba(216, 59, 1, 0.05); border-left-color: #d83b01; color: #d83b01;">
                    <strong>Важно:</strong> Вы можете полностью <strong>перенести</strong> бронь (сменить номер или даты). Система сама проверит, не занято ли новое время.
                </div>
            </div>
        </div>

        <!-- Конструктор -->
        <div class="step-item">
            <div class="step-number">04</div>
            <div class="step-body">
                <h3>Конструктор и сайт</h3>
                <p>Чтобы ваши клиенты могли бронировать сами:</p>
                <ol>
                    <li>Зайдите в <strong>"Конструктор форм"</strong>.</li>
                    <li>Настройте, какие поля (имя, телефон и т.д.) будут обязательными.</li>
                    <li>Внизу страницы найдите <strong>"Шорт-код"</strong>.</li>
                    <li>Скопируйте его и вставьте на ваш основной сайт.</li>
                </ol>
            </div>
        </div>

        <!-- Мобильная версия -->
        <div class="step-item">
            <div class="step-number">05</div>
            <div class="step-body">
                <h3>Мобильный интерфейс</h3>
                <p>Ваши администраторы и персонал могут работать с телефонов. Мобильная версия оптимизирована для быстрой отметки заезда/выезда и выполнения задач.</p>
                <div style="display: flex; gap: 10px; margin-top: 10px;">
                    <a href="mobile/index.php" class="btn btn-outline btn-sm">Открыть мобильную версию</a>
                </div>
            </div>
        </div>

        <!-- Обслуживание -->
        <div class="step-item">
            <div class="step-number">06</div>
            <div class="step-body">
                <h3>Данные и Безопасность</h3>
                <p>В разделе <strong>"Настройки"</strong> доступны инструменты обслуживания:</p>
                <ul>
                    <li><strong>Импорт:</strong> Если у вас есть список номеров в Excel, сохраните его как CSV и загрузите одним файлом.</li>
                    <li><strong>Бэкап:</strong> Нажимайте "Скачать копию" раз в неделю. Это ваша страховка.</li>
                    <li><strong>Здоровье:</strong> Проверяйте права доступа к файлам, чтобы система работала без ошибок.</li>
                </ul>
            </div>
        </div>
    </div>

    <div style="margin-top: 60px; padding: 40px; border-radius: 24px; background: #f8fafc; border: 1px solid #e2e8f0; text-align: center;">
        <h2 style="margin-bottom: 20px;">Нужна помощь?</h2>
        <p style="color: #64748b; max-width: 600px; margin: 0 auto 30px;">Система построена на принципах максимальной простоты. Все данные хранятся в папке <code>/data</code>. Для переноса программы на другой сервер достаточно просто скопировать все файлы.</p>
        <div style="font-weight: 700; color: var(--primary-color);">Разработка и поддержка: WES.BY</div>
        <p style="margin-top: 10px; font-size: 0.9rem; color: #64748b;">
            Разработчик: Коваженко Жанна Людвиговна<br>
            Тел: +375333533971 | Email: 7578453@gmail.com
        </p>
    </div>
</div>

<style>
    .step-container {
        position: relative;
        padding-left: 40px;
    }
    .step-container::before {
        content: '';
        position: absolute;
        left: 14px;
        top: 0;
        bottom: 0;
        width: 2px;
        background: linear-gradient(180deg, var(--primary-color) 0%, #e2e8f0 100%);
    }
    .step-item {
        position: relative;
        margin-bottom: 50px;
    }
    .step-number {
        position: absolute;
        left: -54px;
        top: 0;
        width: 30px;
        height: 30px;
        background: var(--primary-color);
        color: white;
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        font-size: 0.8rem;
        font-weight: 800;
        border: 4px solid #fff;
        box-shadow: 0 4px 10px rgba(0,120,212,0.2);
        z-index: 2;
    }
    .step-body h3 {
        margin-top: 0;
        font-size: 1.4rem;
        margin-bottom: 12px;
        color: #1e293b;
    }
    .step-body p {
        color: #475569;
        line-height: 1.6;
        margin-bottom: 15px;
    }
    .step-body ul, .step-body ol {
        margin-bottom: 15px;
        color: #475569;
    }
    .step-body li {
        margin-bottom: 8px;
    }
    .tip-box {
        background: rgba(0, 120, 212, 0.05);
        border-left: 4px solid var(--primary-color);
        padding: 15px;
        border-radius: 0 8px 8px 0;
        font-size: 0.95rem;
        color: #005a9e;
    }
    .help-content li strong {
        color: #1e293b;
    }
</style>

<?php include 'includes/footer.php'; ?>
