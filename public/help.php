<?php require_once "auth.php";
$pageTitle = 'Подробная справка и руководство';
include 'includes/header.php';
?>

<div class="mica-card help-content" style="max-width: 1200px; margin: 0 auto; padding: 50px;">
    <!-- Заголовок -->
    <div style="text-align: center; margin-bottom: 60px;">
        <h1 style="font-size: 3.2rem; margin-bottom: 15px; background: linear-gradient(90deg, #0078d4, #00b7ff); -webkit-background-clip: text; -webkit-text-fill-color: transparent; font-weight: 800;">
            wesbooking Pro
        </h1>
        <p style="font-size: 1.4rem; color: #475569; max-width: 800px; margin: 0 auto; line-height: 1.5;">
            Профессиональная экосистема для автоматизации бронирования ресурсов, управления номерным фондом и почасовыми услугами.
        </p>

        <div style="margin-top: 30px; display: inline-block; background: #fffbeb; border: 1px solid #fef3c7; padding: 15px 30px; border-radius: 16px; text-align: left;">
            <strong style="color: #92400e; display: block; margin-bottom: 5px;">🔑 Данные для входа (по умолчанию):</strong>
            <code style="font-size: 1.1rem; color: #b45309;">Логин: admin / Пароль: admin</code>
        </div>
    </div>

    <!-- 01. О программе -->
    <section class="help-section">
        <div class="section-badge">01</div>
        <h2>О программе: Для чего она создана?</h2>
        <div class="grid-2 gap-30">
            <div>
                <p><strong>wesbooking Pro</strong> — это не просто календарь занятости. Это комплексное решение для бизнеса, который ценит время своих сотрудников и комфорт своих клиентов. Программа разработана для автоматизации процессов в санаториях, гостиницах, базах отдыха, а также в комплексах, предоставляющих почасовые услуги (сауны, бильярд, конференц-залы).</p>
                <p>Основная миссия системы — свести к минимуму "человеческий фактор", исключить ошибки при записи (овербукинг) и предоставить руководству прозрачную аналитику в режиме реального времени.</p>
            </div>
            <div class="necessity-box">
                <h4>Почему она необходима?</h4>
                <ul>
                    <li><strong>Уход от бумаги и Excel:</strong> Данные больше не теряются в таблицах или блокнотах.</li>
                    <li><strong>Централизация:</strong> Все бронирования (онлайн и ручные) в единой базе.</li>
                    <li><strong>Доступность 24/7:</strong> Управление из любой точки мира с любого устройства.</li>
                </ul>
            </div>
        </div>
    </section>

    <!-- 02. Преимущества -->
    <section class="help-section">
        <div class="section-badge">02</div>
        <h2>Ключевые преимущества</h2>
        <div class="benefits-grid">
            <div class="benefit-card">
                <div class="benefit-icon">🚀</div>
                <h3>Скорость работы</h3>
                <p>Оформление брони занимает менее 30 секунд. Быстрый поиск по базе гостей и моментальная проверка доступности номеров.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">💎</div>
                <h3>Гибридный режим</h3>
                <p>Единственная система, эффективно совмещающая посуточный учет (номера) и почасовой график (сауны, услуги) в одном окне.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">📱</div>
                <h3>Мобильность (PWA)</h3>
                <p>Полноценная работа на смартфонах без установки из AppStore/Google Play. Идеально для горничных и администраторов на выезде.</p>
            </div>
            <div class="benefit-card">
                <div class="benefit-icon">🛠</div>
                <h3>Конструктор форм</h3>
                <p>Создавайте формы бронирования под свой стиль и вставляйте их на любой сайт за 2 минуты через готовые шорт-коды.</p>
            </div>
        </div>
    </section>

    <!-- 03. Рабочий стол -->
    <section class="help-section">
        <div class="section-badge">03</div>
        <h2>Рабочий стол (Dashboard)</h2>
        <p>Это "центр управления полетами". Здесь вы видите моментальный срез состояния вашего бизнеса на текущий день:</p>
        <ul class="feature-list">
            <li><strong>Оперативные списки:</strong> Кто заезжает сегодня, кто выезжает, кто уже проживает в фонде.</li>
            <li><strong>Финансовый пульс:</strong> Расчетный доход за текущие сутки на основе активных бронирований.</li>
            <li><strong>Задачи персонала:</strong> Список дел и планов с приоритетами. Выполнение задачи мгновенно синхронизируется между всеми сотрудниками.</li>
            <li><strong>Индикатор загрузки:</strong> Наглядная визуализация процента занятости номерного фонда.</li>
        </ul>
    </section>

    <!-- 04. Визуальные графики -->
    <section class="help-section">
        <div class="section-badge">04</div>
        <h2>Шахматка и Почасовой график</h2>
        <p>Визуализация — залог отсутствия ошибок. Система предлагает два фундаментальных инструмента:</p>
        <div class="grid-2 gap-30">
            <div class="info-block">
                <h4>🗓 Шахматка (Посуточная)</h4>
                <p>Классическая сетка для управления номерами. Позволяет видеть картину на недели вперед. Поддерживает быстрое бронирование кликом по ячейке и просмотр деталей гостя без перезагрузки страницы.</p>
            </div>
            <div class="info-block">
                <h4>🕰 Почасовой график</h4>
                <p>Специализированная сетка для ресурсов с высокой оборачиваемостью. Отображает расписание дня по часам. Идеально для саун, бассейнов и процедурных кабинетов.</p>
            </div>
        </div>
    </section>

    <!-- 05. Ресурсы -->
    <section class="help-section">
        <div class="section-badge">05</div>
        <h2>Управление ресурсами</h2>
        <p>Система полностью настраиваема под ваш объект. Вы можете создавать неограниченное количество категорий и сущностей:</p>
        <table class="help-table">
            <tr>
                <th>Объекты и Классы</th>
                <td>Определяйте типы проживания, цены за сутки/час, вместимость и описания.</td>
            </tr>
            <tr>
                <th>Процедуры и Услуги</th>
                <td>Ведение прайс-листа дополнительных услуг, которые можно добавлять в счет гостя.</td>
            </tr>
            <tr>
                <th>Пакеты (Путевки)</th>
                <td>Создание готовых программ с фиксированной длительностью и набором услуг для ускорения продаж.</td>
            </tr>
        </table>
    </section>

    <!-- 06. Конструктор -->
    <section class="help-section">
        <div class="section-badge">06</div>
        <h2>Интеграция: Конструктор форм</h2>
        <p>Ваш сайт — основной источник бронирований. <strong>wesbooking Pro</strong> предоставляет мощный инструмент для получения прямых заявок:</p>
        <ul class="feature-list">
            <li><strong>Настройка полей:</strong> Выбирайте, какие данные (ФИО, телефон, адрес) запрашивать у клиента.</li>
            <li><strong>Шорт-коды:</strong> Получайте готовый код (Iframe или JS-скрипт) для вставки в любую CMS (WordPress, Tilda, Bitrix).</li>
            <li><strong>Авто-синхронизация:</strong> Бронирование с сайта мгновенно появляется в вашей шахматке и уведомляет администратора.</li>
        </ul>
    </section>

    <!-- 07. Мобильность -->
    <section class="help-section">
        <div class="section-badge">07</div>
        <h2>Мобильная версия и PWA</h2>
        <p>Мы используем технологию <strong>Progressive Web App</strong>. Это современный стандарт, позволяющий использовать веб-сайт как приложение:</p>
        <div class="tip-box">
            <strong>Как установить:</strong> Зайдите в раздел "Настройки" -> "Установка" или воспользуйтесь кнопкой "Установить", которая появится в верхней части браузера.
        </div>
        <p>Мобильная версия специально упрощена для работы "на ходу": крупные кнопки, оптимизированные формы ввода и быстрый доступ к задачам горничных и техников.</p>
    </section>

    <!-- 08. Безопасность -->
    <section class="help-section">
        <div class="section-badge">08</div>
        <h2>Обслуживание и Безопасность</h2>
        <p>Ваши данные — ваша собственность. Система обеспечивает их сохранность:</p>
        <ul class="feature-list">
            <li><strong>Локальное хранение:</strong> Все данные хранятся на вашем сервере в папке <code>/data</code>. Никаких сторонних облаков.</li>
            <li><strong>Резервное копирование:</strong> Функция скачивания полного архива базы данных в один клик.</li>
            <li><strong>Права доступа (RBAC):</strong> Создавайте учетные записи для сотрудников с ограниченными правами (например, горничная не увидит финансовую аналитику).</li>
            <li><strong>Целостность:</strong> Встроенные инструменты проверки прав доступа и здоровья системы.</li>
        </ul>
    </section>

    <!-- Разработчик -->
    <div style="margin-top: 80px; padding: 50px; border-radius: 32px; background: #f8fafc; border: 1px solid #e2e8f0; text-align: center; position: relative; overflow: hidden;">
        <div style="position: absolute; top: 0; left: 0; width: 100%; height: 5px; background: var(--primary-color);"></div>
        <h2 style="margin-bottom: 25px; font-size: 1.8rem; color: #1e293b;">Техническая поддержка и разработка</h2>
        <p style="color: #64748b; max-width: 700px; margin: 0 auto 40px; font-size: 1.1rem; line-height: 1.6;">
            Система <strong>wesbooking Pro</strong> постоянно совершенствуется. Мы открыты к вашим предложениям по внедрению нового функционала и адаптации программы под специфику вашего бизнеса.
        </p>

        <div class="dev-contacts">
            <div class="contact-item">
                <span class="label">Разработчик</span>
                <span class="value">Коваженко Жанна Людвиговна</span>
            </div>
            <div class="contact-item">
                <span class="label">Компания / Бренд</span>
                <span class="value" style="color: var(--primary-color); font-weight: 800;">WES.BY</span>
            </div>
            <div class="contact-item">
                <span class="label">Телефон / WhatsApp</span>
                <a href="tel:+375333533971" class="value">+375 (33) 353-39-71</a>
            </div>
            <div class="contact-item">
                <span class="label">Электронная почта</span>
                <a href="mailto:7578453@gmail.com" class="value">7578453@gmail.com</a>
            </div>
        </div>

        <div style="margin-top: 40px; font-size: 0.9rem; color: #94a3b8;">
            © 2024 Все права защищены. wesbooking Pro — ваш надежный партнер в гостеприимстве.
        </div>
    </div>
</div>

<style>
    .help-content {
        line-height: 1.7;
        color: #334155;
    }
    .help-section {
        position: relative;
        padding-left: 60px;
        margin-bottom: 80px;
    }
    .section-badge {
        position: absolute;
        left: 0;
        top: 0;
        width: 40px;
        height: 40px;
        background: #f1f5f9;
        color: #0078d4;
        border-radius: 12px;
        display: flex;
        align-items: center;
        justify-content: center;
        font-weight: 800;
        font-size: 1rem;
        border: 1px solid #e2e8f0;
    }
    .help-section h2 {
        font-size: 1.8rem;
        margin-bottom: 25px;
        color: #0f172a;
        font-weight: 700;
    }
    .help-section p {
        margin-bottom: 20px;
        font-size: 1.05rem;
    }
    .grid-2 { display: grid; grid-template-columns: 1fr 1fr; gap: 20px; }
    .gap-30 { gap: 40px; }

    .necessity-box {
        background: #fffbeb;
        border: 1px solid #fef3c7;
        padding: 25px;
        border-radius: 16px;
    }
    .necessity-box h4 { color: #92400e; margin-top: 0; margin-bottom: 15px; font-size: 1.1rem; }
    .necessity-box ul { padding-left: 20px; color: #b45309; }
    .necessity-box li { margin-bottom: 10px; }

    .benefits-grid {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 25px;
        margin-top: 30px;
    }
    .benefit-card {
        background: #fff;
        border: 1px solid #e2e8f0;
        padding: 30px;
        border-radius: 20px;
        transition: 0.3s;
    }
    .benefit-card:hover { transform: translateY(-5px); border-color: #0078d4; box-shadow: 0 10px 25px rgba(0,120,212,0.1); }
    .benefit-icon { font-size: 2rem; margin-bottom: 20px; }
    .benefit-card h3 { font-size: 1.2rem; margin-bottom: 15px; color: #1e293b; }
    .benefit-card p { font-size: 0.95rem; color: #64748b; margin-bottom: 0; }

    .feature-list { list-style: none; padding: 0; margin-top: 20px; }
    .feature-list li {
        position: relative;
        padding-left: 30px;
        margin-bottom: 15px;
        font-size: 1.05rem;
    }
    .feature-list li::before {
        content: '✓';
        position: absolute;
        left: 0;
        color: #10b981;
        font-weight: 900;
    }

    .info-block {
        background: #f8fafc;
        padding: 25px;
        border-radius: 16px;
        border: 1px solid #e2e8f0;
    }
    .info-block h4 { margin-top: 0; color: #1e293b; font-size: 1.15rem; margin-bottom: 15px; }
    .info-block p { font-size: 0.95rem; margin-bottom: 0; color: #475569; }

    .help-table { width: 100%; border-collapse: collapse; margin-top: 20px; }
    .help-table th { text-align: left; padding: 15px; background: #f1f5f9; border-bottom: 2px solid #e2e8f0; width: 30%; }
    .help-table td { padding: 15px; border-bottom: 1px solid #e2e8f0; }

    .tip-box {
        background: rgba(0, 120, 212, 0.05);
        border-left: 5px solid #0078d4;
        padding: 20px;
        border-radius: 4px 12px 12px 4px;
        margin: 25px 0;
        color: #005a9e;
        font-weight: 500;
    }

    .dev-contacts {
        display: grid;
        grid-template-columns: repeat(auto-fit, minmax(250px, 1fr));
        gap: 30px;
        text-align: left;
    }
    .contact-item { display: flex; flex-direction: column; gap: 8px; }
    .contact-item .label { font-size: 0.8rem; color: #94a3b8; text-transform: uppercase; letter-spacing: 1px; font-weight: 700; }
    .contact-item .value { font-size: 1.1rem; color: #1e293b; font-weight: 600; text-decoration: none; }
    .contact-item a.value:hover { color: #0078d4; }

    @media (max-width: 768px) {
        .help-content { padding: 25px; }
        .grid-2 { grid-template-columns: 1fr; }
        .help-section { padding-left: 0; }
        .section-badge { position: relative; margin-bottom: 15px; }
        .help-section h1 { font-size: 2.2rem; }
    }
</style>

<?php include 'includes/footer.php'; ?>
