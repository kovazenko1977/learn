<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';

include 'includes/header.php';
?>

<div class="container" style="max-width: 1200px;">
    <div class="page-header" style="animation: slideDown 0.5s ease-out; margin-bottom: 40px;">
        <div style="display: flex; align-items: center; gap: 16px;">
            <div style="width: 56px; height: 56px; background: var(--win-accent); border-radius: 14px; display: flex; align-items: center; justify-content: center; color: white;">
                <i data-lucide="help-circle" style="width: 32px; height: 32px;"></i>
            </div>
            <div>
                <h1 style="margin:0; font-size: 32px;">Центр поддержки ХОП</h1>
                <p style="color:var(--win-text-secondary); margin-top: 4px;">Полное руководство по работе с системой Хозяйственно-Оперативных Поручений</p>
            </div>
        </div>
    </div>

    <!-- Quick Navigation -->
    <div class="card mica" style="padding: 12px; margin-bottom: 32px; display: flex; gap: 8px; overflow-x: auto; scrollbar-width: none;">
        <a href="#roles" class="btn-secondary" style="font-size: 13px; text-decoration: none; white-space: nowrap;">Роли и права</a>
        <a href="#lifecycle" class="btn-secondary" style="font-size: 13px; text-decoration: none; white-space: nowrap;">Жизненный цикл</a>
        <a href="#initiator" class="btn-secondary" style="font-size: 13px; text-decoration: none; white-space: nowrap;">Для Инициатора</a>
        <a href="#performer" class="btn-secondary" style="font-size: 13px; text-decoration: none; white-space: nowrap;">Для Исполнителя</a>
        <a href="#admin" class="btn-secondary" style="font-size: 13px; text-decoration: none; white-space: nowrap;">Администрирование</a>
        <a href="#analytics" class="btn-secondary" style="font-size: 13px; text-decoration: none; white-space: nowrap;">Рейтинги и КПД</a>
        <a href="#telegram" class="btn-secondary" style="font-size: 13px; text-decoration: none; white-space: nowrap;">Настройка Telegram</a>
        <a href="#pwa" class="btn-secondary" style="font-size: 13px; text-decoration: none; white-space: nowrap;">Мобильная версия</a>
        <a href="#tech" class="btn-secondary" style="font-size: 13px; text-decoration: none; white-space: nowrap;">Технические детали</a>
    </div>

    <div style="display: grid; grid-template-columns: 1fr; gap: 40px; animation: slideUp 0.6s ease-out;">

        <!-- SECTION: ROLES -->
        <section id="roles">
            <h2 style="font-size: 24px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                <i data-lucide="users" style="color:var(--win-accent);"></i> Роли пользователей и полномочия
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
                <div class="card mica" style="padding: 20px;">
                    <h3 style="margin-top:0; font-size: 16px; color: var(--win-accent);">Инициатор</h3>
                    <p style="font-size: 13px; color: var(--win-text-secondary);">Сотрудник больницы (медсестра, врач), обнаруживший проблему.</p>
                    <ul style="font-size: 13px; padding-left: 18px; line-height: 1.6;">
                        <li>Создание новых заявок с фото</li>
                        <li>Отслеживание статуса своих заявок</li>
                        <li>Общение в чате заявки</li>
                        <li>Приемка работы (Оценка 0-5 звезд)</li>
                    </ul>
                </div>
                <div class="card mica" style="padding: 20px;">
                    <h3 style="margin-top:0; font-size: 16px; color: var(--status-working);">Исполнитель</h3>
                    <p style="font-size: 13px; color: var(--win-text-secondary);">Технический специалист (сантехник, электрик), выполняющий работу.</p>
                    <ul style="font-size: 13px; padding-left: 18px; line-height: 1.6;">
                        <li>Просмотр только своих задач</li>
                        <li>Перевод заявки в статус "Принято"</li>
                        <li>Загрузка фото результата</li>
                        <li>Отправка на проверку</li>
                    </ul>
                </div>
                <div class="card mica" style="padding: 20px;">
                    <h3 style="margin-top:0; font-size: 16px; color: var(--status-checking);">Ответственный службы (Lead)</h3>
                    <p style="font-size: 13px; color: var(--win-text-secondary);">Начальник технического подразделения.</p>
                    <ul style="font-size: 13px; padding-left: 18px; line-height: 1.6;">
                        <li>Видит все заявки своей службы</li>
                        <li>Назначает конкретных исполнителей</li>
                        <li>Контролирует сроки выполнения (SLA)</li>
                        <li>Переназначение задач</li>
                    </ul>
                </div>
            </div>
        </section>

        <!-- SECTION: LIFECYCLE -->
        <section id="lifecycle" class="card mica" style="padding: 32px;">
            <h2 style="margin-top:0; font-size: 24px; margin-bottom: 24px; display: flex; align-items: center; gap: 12px;">
                <i data-lucide="git-pull-request" style="color:var(--win-accent);"></i> Жизненный цикл заявки (Маршрутизация)
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px;">
                <div style="background: rgba(0,0,0,0.02); padding: 16px; border-radius: 12px; border: 1px solid var(--win-border);">
                    <span class="badge status-new" style="margin-bottom:8px; display:inline-block;">НОВАЯ</span>
                    <p style="font-size:12px; margin:0;">Только что создана. Ожидает, пока начальник службы назначит мастера.</p>
                </div>
                <div style="background: rgba(0,0,0,0.02); padding: 16px; border-radius: 12px; border: 1px solid var(--win-border);">
                    <span class="badge status-assigned" style="margin-bottom:8px; display:inline-block;">НАЗНАЧЕНА</span>
                    <p style="font-size:12px; margin:0;">Мастер выбран, он получил уведомление, но еще не нажал кнопку "В работу".</p>
                </div>
                <div style="background: rgba(0,0,0,0.02); padding: 16px; border-radius: 12px; border: 1px solid var(--win-border);">
                    <span class="badge status-working" style="margin-bottom:8px; display:inline-block;">ПРИНЯТО</span>
                    <p style="font-size:12px; margin:0;">Исполнитель подтвердил, что выехал на место или приступил к задаче.</p>
                </div>
                <div style="background: rgba(0,0,0,0.02); padding: 16px; border-radius: 12px; border: 1px solid var(--win-border);">
                    <span class="badge status-checking" style="margin-bottom:8px; display:inline-block;">ПРОВЕРКА</span>
                    <p style="font-size:12px; margin:0;">Работа готова. Инициатор должен нажать "Принять" или "Вернуть".</p>
                </div>
                <div style="background: rgba(0,0,0,0.02); padding: 16px; border-radius: 12px; border: 1px solid var(--win-border);">
                    <span class="badge status-completed" style="margin-bottom:8px; display:inline-block;">ВЫПОЛНЕНА</span>
                    <p style="font-size:12px; margin:0;">Работа принята, оценка поставлена. Заявка закрывается автоматически.</p>
                </div>
            </div>
        </section>

        <!-- SECTION: INITIATOR GUIDE -->
        <section id="initiator">
            <h2 style="font-size: 24px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                <i data-lucide="plus-circle" style="color:var(--win-accent);"></i> Инструкция для Инициатора
            </h2>
            <div class="card mica">
                <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                    <div>
                        <h4 style="margin-top:0; font-size:15px; border-bottom: 1px solid var(--win-border); pb: 8px;">1. Создание заявки</h4>
                        <p style="font-size:13px; line-height:1.6;">Нажмите кнопку "Новая заявка" на главной или внизу. Укажите подробное описание проблемы. <br><strong>Обязательно:</strong> выберите Корпус и Кабинет. <br><strong>Фото:</strong> Приложите фото неисправности — это ускорит понимание задачи исполнителем.</p>
                    </div>
                    <div>
                        <h4 style="margin-top:0; font-size:15px; border-bottom: 1px solid var(--win-border); pb: 8px;">2. Контроль выполнения</h4>
                        <p style="font-size:13px; line-height:1.6;">Вы будете получать уведомления в системе и в Telegram (если настроен). В карточке заявки виден статус и кто конкретно назначен исполнителем. Вы можете написать комментарий в чат заявки, если ситуация изменилась.</p>
                    </div>
                    <div>
                        <h4 style="margin-top:0; font-size:15px; border-bottom: 1px solid var(--win-border); pb: 8px;">3. Приемка работы</h4>
                        <p style="font-size:13px; line-height:1.6;">Когда работа готова, статус изменится на "Проверка". <br><strong>Вариант А:</strong> Нажмите "Принять работу" и поставьте оценку звездами. <br><strong>Вариант Б:</strong> Если работа не доделана, нажмите "Вернуть на доработку" и напишите причину.</p>
                    </div>
                </div>
            </div>
        </section>

        <!-- SECTION: ANALYTICS -->
        <section id="analytics" class="card mica" style="background: linear-gradient(135deg, rgba(255, 174, 0, 0.05) 0%, rgba(255,255,255,0.7) 100%);">
            <h2 style="margin-top:0; font-size: 24px; display: flex; align-items: center; gap: 12px;">
                <i data-lucide="trophy" style="color:#ffae00;"></i> Рейтинги и эффективность
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(280px, 1fr)); gap: 24px;">
                <div>
                    <h4 style="margin-top:0;">Оценки пользователей</h4>
                    <p style="font-size:13px; line-height:1.6;">После выполнения каждой заявки Инициатор выставляет оценку от 0 до 5 звезд. Эти оценки напрямую влияют на рейтинг сотрудника и всей службы.</p>
                </div>
                <div>
                    <h4 style="margin-top:0;">Алгоритм "Лучший сотрудник"</h4>
                    <p style="font-size:13px; line-height:1.6;">Система использует сбалансированную формулу: <code>Средний рейтинг × Логарифм выполненных задач</code>. Это позволяет поощрять как качество работы, так и большой объем выполненных поручений.</p>
                </div>
                <div>
                    <h4 style="margin-top:0;">Контроль SLA</h4>
                    <p style="font-size:13px; line-height:1.6;">Соблюдение сроков — критический фактор. Просроченные заявки негативно сказываются на КПД службы в общем отчете для руководства.</p>
                </div>
            </div>
        </section>

        <!-- SECTION: TECH -->
        <section id="tech">
            <h2 style="font-size: 24px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                <i data-lucide="cpu" style="color:var(--win-accent);"></i> Технические механизмы системы
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(300px, 1fr)); gap: 16px;">
                <div class="card mica">
                    <h3 style="margin-top:0; font-size: 16px;">Хранение данных (NoDB)</h3>
                    <p style="font-size: 13px; line-height: 1.6;">Система не требует установки MySQL или PostgreSQL. Все данные хранятся в формате <b>JSON</b> в папке <code>/data/</code>. Для предотвращения повреждения файлов при одновременном доступе используется механизм <b>flock</b> (файловая блокировка), что гарантирует целостность данных даже при высокой нагрузке.</p>
                </div>
                <div class="card mica">
                    <h3 style="margin-top:0; font-size: 16px;">Безопасность и Сессии</h3>
                    <p style="font-size: 13px; line-height: 1.6;">Пароли пользователей хранятся в виде защищенных хешей (BCRYPT). Все формы защищены от <b>CSRF-атак</b> уникальными токенами. Прямой доступ к файлам данных через браузер заблокирован файлом <code>.htaccess</code>.</p>
                </div>
                <div class="card mica">
                    <h3 style="margin-top:0; font-size: 16px;">Целостность данных</h3>
                    <p style="font-size: 13px; line-height: 1.6;">В разделе обслуживания (Настройки -> Maintenance) встроен инструмент диагностики. Он автоматически находит «сиротские» записи (заявки, привязанные к удаленным сотрудникам или службам) и исправляет их, предотвращая ошибки в работе интерфейса.</p>
                </div>
            </div>
        </section>

        <!-- SECTION: ADMIN -->
        <section id="admin">
            <h2 style="font-size: 24px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                <i data-lucide="shield" style="color:var(--priority-critical);"></i> Панель Администратора и настройки
            </h2>
            <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(400px, 1fr)); gap: 20px;">
                <div class="card mica">
                    <h3 style="margin-top:0; font-size: 16px;">Управление персоналом и SLA</h3>
                    <p style="font-size: 13px; line-height: 1.6;">
                        <strong>Персонал:</strong> Доступ в систему осуществляется по <b>Логину и Паролю</b>. При создании "Исполнителя" или "Ответственного" обязательно привязывайте его к конкретной <strong>Службе</strong> для корректной маршрутизации.<br>
                        <strong>SLA:</strong> В настройках укажите время в часах для каждого приоритета (Низкий, Средний, Высокий, Критический). Система будет подсвечивать просроченные задачи красным цветом.
                    </p>
                </div>
                <div class="card mica">
                    <h3 style="margin-top:0; font-size: 16px;">Обслуживание данных</h3>
                    <p style="font-size: 13px; line-height: 1.6;">
                        <strong>Бэкап:</strong> Система хранит все в JSON-файлах. Раз в неделю скачивайте ZIP-архив из настроек. В случае поломки сервера вы сможете восстановить всё за 1 минуту.<br>
                        <strong>Сброс:</strong> Для полной очистки базы перед новым сезоном используйте "Сброс" с паролем 12345. Это удалит заявки, но сохранит настройки и пользователей.
                    </p>
                </div>
            </div>
        </section>

        <!-- SECTION: TELEGRAM -->
        <section id="telegram" class="card mica" style="background: linear-gradient(135deg, rgba(0,136,204,0.05) 0%, rgba(255,255,255,0.7) 100%);">
            <div style="display: grid; grid-template-columns: 1fr 350px; gap: 32px;">
                <div>
                    <h2 style="margin-top:0; font-size: 24px; display: flex; align-items: center; gap: 12px;">
                        <i data-lucide="send" style="color:#0088cc;"></i> Интеграция с Telegram
                    </h2>
                    <p style="font-size:14px; line-height:1.6;">Уведомления в Telegram позволяют сотрудникам не держать вкладку браузера открытой постоянно. Бот сам напишет, когда придет новая задача или изменится статус.</p>

                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-top: 20px;">
                        <div>
                            <h4 style="margin-top:0; font-size:14px; color:var(--win-accent);">Глобальный чат (Для Lead)</h4>
                            <p style="font-size:12px; color:var(--win-text-secondary);">В настройках системы укажите ID группы больницы. Туда бот будет кидать <strong>все</strong> новые заявки для их быстрого распределения начальниками служб.</p>
                        </div>
                        <div>
                            <h4 style="margin-top:0; font-size:14px; color:var(--win-accent);">Личные уведомления</h4>
                            <p style="font-size:12px; color:var(--win-text-secondary);">Каждый сотрудник может указать свой Telegram ID в профиле. Тогда бот будет писать ему <strong>лично</strong> только о его задачах.</p>
                        </div>
                    </div>
                </div>
                <div style="background: rgba(255,255,255,0.4); padding: 20px; border-radius: 16px; border: 1px solid rgba(0,136,204,0.2);">
                    <h4 style="margin-top:0;">Как настроить бота?</h4>
                    <ol style="font-size:12px; padding-left:16px; line-height:1.8;">
                        <li>Напишите <b>@BotFather</b> в Telegram.</li>
                        <li>Команда <code>/newbot</code> -> Имя -> Юзернейм.</li>
                        <li>Скопируйте <b>HTTP API Token</b> в настройки ХОП.</li>
                        <li>Найдите <b>@userinfobot</b> и узнайте свой ID (цифры).</li>
                        <li>Вставьте ID в "Target Chat ID" и нажмите "Тест".</li>
                    </ol>
                </div>
            </div>
        </section>

        <!-- SECTION: INTERFACE -->
        <section id="interface">
            <h2 style="font-size: 24px; margin-bottom: 20px; display: flex; align-items: center; gap: 12px;">
                <i data-lucide="layout" style="color:var(--win-accent);"></i> Интерфейс и удобство работы
            </h2>
            <div class="card mica">
                <h4 style="margin-top:0;">Сворачиваемое боковое меню</h4>
                <p style="font-size: 13px; line-height: 1.6;">Для экономии места на широких экранах вы можете свернуть левую панель навигации, нажав на иконку «бургер» (три полоски) в левом верхнем углу шапки. В свернутом состоянии остаются только иконки, что позволяет максимально расширить рабочую область для таблиц и графиков аналитики.</p>

                <h4 style="margin-top:20px;">Глобальный поиск</h4>
                <p style="font-size: 13px; line-height: 1.6;">В верхней части экрана расположен умный поиск. Вы можете вводить как номер заявки (например, <code>42</code>), так и текст из описания. Поиск работает мгновенно и показывает результаты без перезагрузки страницы.</p>
            </div>
        </section>

        <!-- SECTION: PWA -->
        <section id="pwa" class="card mica" style="border-left: 6px solid var(--win-accent);">
            <div style="display: flex; gap: 32px; align-items: center; flex-wrap: wrap;">
                <div style="flex: 1; min-width: 300px;">
                    <h2 style="margin-top:0; font-size: 24px; display: flex; align-items: center; gap: 12px;">
                        <i data-lucide="smartphone" style="color:var(--win-accent);"></i> Мобильное приложение (PWA)
                    </h2>
                    <p style="font-size:14px; line-height:1.6;">Вам не нужно искать ХОП в AppStore или Google Play. Система работает как Progressive Web App. Это экономит место и работает быстрее.</p>

                    <div style="display: flex; gap: 24px; margin-top: 16px;">
                        <div style="flex:1;">
                            <h4 style="margin-top:0; font-size:13px;"><i data-lucide="apple" style="width:14px;"></i> iOS (iPhone)</h4>
                            <p style="font-size:11px; color:var(--win-text-secondary);">Открыть Safari -> Кнопка "Поделиться" -> "На экран Домой".</p>
                        </div>
                        <div style="flex:1;">
                            <h4 style="margin-top:0; font-size:13px;"><i data-lucide="android" style="width:14px;"></i> Android</h4>
                            <p style="font-size:11px; color:var(--win-text-secondary);">Открыть Chrome -> Три точки -> "Установить приложение".</p>
                        </div>
                    </div>
                </div>
                <div style="width:180px; height:180px; background:rgba(0,0,0,0.03); border-radius:30px; display:flex; flex-direction:column; align-items:center; justify-content:center; text-align:center; padding: 20px;">
                    <i data-lucide="layout-grid" style="width:48px; height:48px; color:var(--win-accent); margin-bottom:12px;"></i>
                    <span style="font-size:11px; font-weight:700;">Иконка появится на рабочем столе</span>
                </div>
            </div>
        </section>

        <!-- FOOTER INFO -->
        <div style="text-align: center; color: var(--win-text-secondary); font-size: 12px; margin-top: 20px; border-top: 1px solid var(--win-border); pt: 40px; pb: 40px;">
            <p>© 2024 Информационная система «ХОП» — Разработано специально для медицинских учреждений.</p>
            <p>Техническая поддержка: <b>wes.by Коваженко С.Б.</b></p>
        </div>

    </div>
</div>

<style>
section { scroll-margin-top: 100px; }
.btn-secondary:hover { background: var(--win-accent); color: white; border-color: var(--win-accent); }
strong { color: var(--win-text); }
h4 { color: var(--win-text); font-weight: 700; margin-bottom: 8px; }
</style>

<?php include 'includes/footer.php'; ?>
