<?php
/**
 * Admin Dashboard View Template
 * Tabs:
 * 1. Аналитика (Dashboard KPI)
 * 2. Мероприятия (Events)
 * 3. Сеансы и Вместимость (Slots & Capacity)
 * 4. Записи и Гости (Bookings)
 * 5. Конструктор форм (Form Builder)
 * 6. Telegram и Email (Notifications)
 * 7. Внешний вид и Mobile (Customizer)
 * 8. Настройки и PIN-код (Settings)
 * 9. 40 функций плагина (Features Showcase)
 */

if (!defined('ABSPATH') && !defined('WPEB_STANDALONE')) {
    exit;
}

$pin = $settings['admin_pin'] ?? '222120';
?>

<div class="wpeb-admin-wrap" id="wpebAdminApp">
    <!-- Top Bar -->
    <div class="wpeb-admin-header">
        <h1 class="wpeb-admin-title">
            <span>🎟 WP Event Booking — Панель управления</span>
        </h1>
        <div style="display:flex; gap:10px; align-items:center;">
            <span style="font-size:13px; color:#64748b;">Шорткод: <code>[event_booking]</code></span>
            <a href="?wpeb_logout=1" class="button">Выйти из панели 🔒</a>
        </div>
    </div>

    <!-- Navigation Tabs -->
    <div class="wpeb-nav-tabs">
        <button type="button" class="wpeb-tab-btn active" data-tab="dashboard">📊 Обзор</button>
        <button type="button" class="wpeb-tab-btn" data-tab="slots">⏰ Сеансы и лимиты мест</button>
        <button type="button" class="wpeb-tab-btn" data-tab="bookings">👥 Записи (<?php echo count($bookings); ?>)</button>
        <button type="button" class="wpeb-tab-btn" data-tab="builder">🛠 Конструктор форм</button>
        <button type="button" class="wpeb-tab-btn" data-tab="notifications">✈️ Telegram и Email</button>
        <button type="button" class="wpeb-tab-btn" data-tab="appearance">🎨 Внешний вид и Mobile</button>
        <button type="button" class="wpeb-tab-btn" data-tab="settings">⚙️ Настройки и PIN</button>
        <button type="button" class="wpeb-tab-btn" data-tab="features">⭐ 40 функций</button>
    </div>

    <!-- TAB 1: DASHBOARD KPI -->
    <div class="wpeb-tab-panel" id="wpebTab_dashboard">
        <div class="wpeb-kpi-grid">
            <div class="wpeb-kpi-card">
                <span class="wpeb-kpi-val"><?php echo $analytics['total_bookings']; ?></span>
                <span class="wpeb-kpi-lbl">Всего бронирований</span>
            </div>
            <div class="wpeb-kpi-card">
                <span class="wpeb-kpi-val" style="color:#10b981;"><?php echo $analytics['total_attendees']; ?></span>
                <span class="wpeb-kpi-lbl">Забронировано мест (гостей)</span>
            </div>
            <div class="wpeb-kpi-card">
                <span class="wpeb-kpi-val" style="color:#3b82f6;"><?php echo $analytics['total_slots']; ?></span>
                <span class="wpeb-kpi-lbl">Созданных сеансов</span>
            </div>
            <div class="wpeb-kpi-card">
                <span class="wpeb-kpi-val" style="color:#8b5cf6;"><?php echo $analytics['total_events']; ?></span>
                <span class="wpeb-kpi-lbl">Активных мероприятий</span>
            </div>
        </div>

        <div class="wpeb-card">
            <h3 class="wpeb-card-title">Быстрый старт и размещение на сайте</h3>
            <p>Вставьте любой из этих шорткодов на страницу WordPress или в Elementor:</p>
            <ul>
                <li><code>[event_booking]</code> — главная форма записи с каскадным выбором месяца, даты и времени.</li>
                <li><code>[event_booking id="1"]</code> — форма записи на конкретное мероприятие по ID.</li>
                <li><code>[event_booking_catalog]</code> — адаптивная витрина карточек всех доступных мероприятий.</li>
                <li><code>[event_booking_lookup]</code> — форма проверки брони по 8-значному коду для гостей.</li>
            </ul>
        </div>
    </div>

    <!-- TAB 2: SLOTS & CAPACITY -->
    <div class="wpeb-tab-panel" id="wpebTab_slots" style="display:none;">
        <div class="wpeb-card">
            <h3 class="wpeb-card-title">Добавить новый сеанс с ограничением мест</h3>
            <form method="POST" style="display:flex; gap:12px; flex-wrap:wrap; align-items:flex-end;">
                <input type="hidden" name="wpeb_save_settings" value="1" />
                <input type="hidden" name="event_id" value="<?php echo $selectedEventId; ?>" />

                <div>
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Дата записи</label>
                    <input type="date" name="add_slot_date" class="regular-text" required value="<?php echo date('Y-m-d', strtotime('+1 day')); ?>" />
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Время сеанса</label>
                    <input type="time" name="add_slot_time" value="14:00" required />
                </div>
                <div>
                    <label style="display:block; font-size:12px; font-weight:600; margin-bottom:4px;">Макс. количество записей (лимит)</label>
                    <input type="number" name="add_slot_capacity" min="1" max="500" value="10" style="width:100px;" required />
                </div>
                <button type="submit" class="button button-primary">Добавить сеанс</button>
            </form>
            <p style="font-size:12px; color:#64748b; margin-top:8px;">* Когда число записей достигнет указанного максимума, данный слот автоматически скроется и посетитель больше не сможет его выбрать.</p>
        </div>

        <div class="wpeb-card">
            <h3 class="wpeb-card-title">Список сформированных слотов времени</h3>
            <table class="wpeb-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Дата</th>
                        <th>Время</th>
                        <th>Лимит мест</th>
                        <th>Записано</th>
                        <th>Осталось</th>
                        <th>Статус в форме</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($slots)): ?>
                        <tr><td colspan="7" style="text-align:center; color:#64748b;">Сеансы еще не добавлены.</td></tr>
                    <?php else: ?>
                        <?php foreach (array_slice($slots, 0, 30) as $sl):
                            $isFull = $sl['booked_count'] >= $sl['max_capacity'];
                        ?>
                            <tr>
                                <td>#<?php echo $sl['id']; ?></td>
                                <td><strong><?php echo date('d.m.Y', strtotime($sl['slot_date'])); ?></strong></td>
                                <td><?php echo esc_html($sl['slot_time']); ?></td>
                                <td><?php echo $sl['max_capacity']; ?> чел.</td>
                                <td><?php echo $sl['booked_count']; ?></td>
                                <td><?php echo max(0, $sl['max_capacity'] - $sl['booked_count']); ?></td>
                                <td>
                                    <?php if ($isFull): ?>
                                        <span class="wpeb-tag wpeb-tag-full">Мест нет (скрыт)</span>
                                    <?php else: ?>
                                        <span class="wpeb-tag wpeb-tag-active">Доступен гостям</span>
                                    <?php endif; ?>
                                </td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 3: BOOKINGS MANAGER -->
    <div class="wpeb-tab-panel" id="wpebTab_bookings" style="display:none;">
        <div class="wpeb-card">
            <h3 class="wpeb-card-title">
                <span>Журнал подтвержденных записей</span>
                <button type="button" class="button" onclick="window.print()">Распечатать ведомость 🖨</button>
            </h3>
            <table class="wpeb-table">
                <thead>
                    <tr>
                        <th>Код</th>
                        <th>Гость</th>
                        <th>Телефон</th>
                        <th>Email</th>
                        <th>Дата и время</th>
                        <th>Мест</th>
                        <th>Статус</th>
                    </tr>
                </thead>
                <tbody>
                    <?php if (empty($bookings)): ?>
                        <tr><td colspan="7" style="text-align:center; color:#64748b; padding:20px;">Записей пока нет.</td></tr>
                    <?php else: ?>
                        <?php foreach ($bookings as $b): ?>
                            <tr>
                                <td><strong style="color:#3b82f6;"><?php echo esc_html($b['booking_code']); ?></strong></td>
                                <td><?php echo esc_html($b['client_name']); ?></td>
                                <td><a href="tel:<?php echo esc_attr($b['client_phone']); ?>"><?php echo esc_html($b['client_phone']); ?></a></td>
                                <td><?php echo esc_html($b['client_email']); ?></td>
                                <td><?php echo esc_html($b['slot_date'] . ' ' . $b['slot_time']); ?></td>
                                <td><strong><?php echo $b['ticket_count']; ?></strong></td>
                                <td><span class="wpeb-tag wpeb-tag-active">Подтверждена</span></td>
                            </tr>
                        <?php endforeach; ?>
                    <?php endif; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- TAB 4: FORM BUILDER -->
    <div class="wpeb-tab-panel" id="wpebTab_builder" style="display:none;">
        <div class="wpeb-card">
            <h3 class="wpeb-card-title">
                <span>Конструктор дополнительных полей анкеты</span>
                <button type="button" class="button button-primary" id="wpebAddFieldBtn">+ Добавить поле в форму</button>
            </h3>
            <p style="color:#64748b; font-size:13px;">Настройте дополнительные поля, которые пользователь заполняет при записи (выпадающие списки, радио-кнопки, текстовые строки, комментарии).</p>

            <div id="wpebBuilderList">
                <?php foreach ($formFields as $field): ?>
                    <div class="wpeb-builder-item" data-key="<?php echo esc_attr($field['field_key']); ?>">
                        <span class="wpeb-drag-handle">☰</span>
                        <div style="flex:1;">
                            <strong><?php echo esc_html($field['field_label']); ?></strong>
                            <span style="font-size:12px; color:#64748b; margin-left:8px;">[key: <?php echo esc_html($field['field_key']); ?>]</span>
                            <div style="margin-top:6px; display:flex; gap:10px; align-items:center;">
                                <select class="wpeb-input wpeb-builder-type" style="width:140px; padding:4px 8px;">
                                    <option value="text" <?php selected($field['field_type'], 'text'); ?>>Текст</option>
                                    <option value="number" <?php selected($field['field_type'], 'number'); ?>>Число</option>
                                    <option value="select" <?php selected($field['field_type'], 'select'); ?>>Выпадающий список</option>
                                    <option value="radio" <?php selected($field['field_type'], 'radio'); ?>>Радио-кнопки</option>
                                    <option value="checkbox" <?php selected($field['field_type'], 'checkbox'); ?>>Чекбоксы</option>
                                    <option value="textarea" <?php selected($field['field_type'], 'textarea'); ?>>Многострочный текст</option>
                                </select>
                                <input type="text" class="wpeb-input wpeb-builder-placeholder" placeholder="Плейсхолдер" value="<?php echo esc_attr($field['placeholder']); ?>" style="flex:1; padding:4px 8px;" />
                                <label style="font-size:13px; display:flex; align-items:center; gap:4px;">
                                    <input type="checkbox" class="wpeb-builder-required" <?php checked(!empty($field['is_required'])); ?> /> Обязательное
                                </label>
                            </div>
                            <div class="wpeb-builder-options-box" style="margin-top:6px; display:<?php echo in_array($field['field_type'], ['select', 'radio', 'checkbox']) ? 'block' : 'none'; ?>;">
                                <textarea class="wpeb-textarea wpeb-builder-options" placeholder="Опции выбора (каждая с новой строки)" style="font-size:12px; height:60px;"><?php echo esc_textarea($field['field_options']); ?></textarea>
                            </div>
                        </div>
                        <button type="button" class="button wpeb-del-field-btn" style="color:#ef4444;" onclick="this.closest('.wpeb-builder-item').remove()">Удалить</button>
                    </div>
                <?php endforeach; ?>
            </div>

            <div style="margin-top:16px;">
                <button type="button" class="button button-primary button-large" id="wpebSaveBuilderBtn">Сохранить форму конструктора</button>
            </div>
        </div>
    </div>

    <!-- TAB 5: TELEGRAM & EMAIL -->
    <div class="wpeb-tab-panel" id="wpebTab_notifications" style="display:none;">
        <form method="POST">
            <input type="hidden" name="wpeb_save_settings" value="1" />

            <div class="wpeb-card">
                <h3 class="wpeb-card-title">🤖 Уведомления в Telegram</h3>
                <p style="color:#64748b; font-size:13px;">Мгновенные оповещения администраторам или в рабочий чат о каждой новой записи на мероприятие.</p>

                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div>
                        <label style="font-weight:600; display:block; margin-bottom:4px;">Токен Telegram-бота (от @BotFather)</label>
                        <input type="text" name="telegram_token" class="regular-text" style="width:100%;" value="<?php echo esc_attr($settings['telegram_token'] ?? ''); ?>" placeholder="123456789:ABCdefGhIJKlmNoPQRstuvWxyz" />
                    </div>
                    <div>
                        <label style="font-weight:600; display:block; margin-bottom:4px;">ID чата или канала получателя</label>
                        <input type="text" name="telegram_chat_id" class="regular-text" style="width:100%;" value="<?php echo esc_attr($settings['telegram_chat_id'] ?? ''); ?>" placeholder="-1001234567890 или @channel_name" />
                    </div>
                </div>

                <div style="margin-top:12px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="telegram_enabled" value="1" <?php checked(!empty($settings['telegram_enabled'])); ?> />
                        <strong>Включить отправку уведомлений в Telegram</strong>
                    </label>
                </div>

                <div style="margin-top:16px;">
                    <label style="font-weight:600; display:block; margin-bottom:4px;">Шаблон сообщения в Telegram</label>
                    <textarea name="telegram_template" rows="5" style="width:100%; font-family:monospace;"><?php echo esc_textarea($settings['telegram_template'] ?? ''); ?></textarea>
                    <span style="font-size:12px; color:#64748b;">Доступные теги: <code>{name}</code>, <code>{phone}</code>, <code>{email}</code>, <code>{date}</code>, <code>{time}</code>, <code>{tickets}</code>, <code>{code}</code>, <code>{event_title}</code>, <code>{custom_fields}</code></span>
                </div>

                <div style="margin-top:14px;">
                    <button type="button" class="button button-secondary" id="wpebTestTgBtn">⚡ Протестировать Telegram</button>
                </div>
            </div>

            <div class="wpeb-card">
                <h3 class="wpeb-card-title">📧 Уведомления по Электронной Почте (Email)</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div>
                        <label style="font-weight:600; display:block; margin-bottom:4px;">Email администратора для копий</label>
                        <input type="email" name="email_to" class="regular-text" style="width:100%;" value="<?php echo esc_attr($settings['email_to'] ?? 'admin@example.com'); ?>" />
                    </div>
                    <div>
                        <label style="font-weight:600; display:block; margin-bottom:4px;">Имя отправителя</label>
                        <input type="text" name="email_from_name" class="regular-text" style="width:100%;" value="<?php echo esc_attr($settings['email_from_name'] ?? 'Онлайн Запись на Мероприятия'); ?>" />
                    </div>
                </div>

                <div style="margin-top:12px;">
                    <label style="display:flex; align-items:center; gap:8px; cursor:pointer;">
                        <input type="checkbox" name="email_enabled" value="1" <?php checked(!empty($settings['email_enabled'])); ?> />
                        <strong>Включить отправку подтверждений на Email клиенту и администратору</strong>
                    </label>
                </div>

                <div style="margin-top:16px;">
                    <label style="font-weight:600; display:block; margin-bottom:4px;">Тема письма</label>
                    <input type="text" name="email_subject" class="regular-text" style="width:100%;" value="<?php echo esc_attr($settings['email_subject'] ?? 'Подтверждение брони #{code}'); ?>" />
                </div>

                <div style="margin-top:16px;">
                    <label style="font-weight:600; display:block; margin-bottom:4px;">Текст письма</label>
                    <textarea name="email_template" rows="5" style="width:100%;"><?php echo esc_textarea($settings['email_template'] ?? ''); ?></textarea>
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" class="button button-primary button-hero">Сохранить настройки уведомлений</button>
                </div>
            </div>
        </form>
    </div>

    <!-- TAB 6: APPEARANCE & MOBILE CUSTOMIZER -->
    <div class="wpeb-tab-panel" id="wpebTab_appearance" style="display:none;">
        <form method="POST">
            <input type="hidden" name="wpeb_save_settings" value="1" />

            <div class="wpeb-card">
                <h3 class="wpeb-card-title">Дизайн формы и адаптивность под смартфоны</h3>
                <div style="display:grid; grid-template-columns:1fr 1fr; gap:16px;">
                    <div>
                        <label style="font-weight:600; display:block; margin-bottom:4px;">Основной цвет кнопок и акцентов</label>
                        <input type="color" id="wpebPrimaryColorInput" name="primary_color" value="<?php echo esc_attr($settings['primary_color'] ?? '#3b82f6'); ?>" style="height:40px; width:100px; padding:0; cursor:pointer;" />
                    </div>
                    <div>
                        <label style="font-weight:600; display:block; margin-bottom:4px;">Скругление углов (px)</label>
                        <input type="number" name="border_radius" value="<?php echo esc_attr($settings['border_radius'] ?? '12'); ?>" min="0" max="40" style="width:100px;" />
                    </div>
                </div>

                <div style="margin-top:16px;">
                    <label style="font-weight:600; display:block; margin-bottom:4px;">Пользовательский CSS стилей</label>
                    <textarea name="custom_css" rows="6" style="width:100%; font-family:monospace;" placeholder=".wpeb-wrapper { /* custom styles */ }"><?php echo esc_textarea($settings['custom_css'] ?? ''); ?></textarea>
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" class="button button-primary button-hero">Сохранить визуальные стили</button>
                </div>
            </div>
        </form>
    </div>

    <!-- TAB 7: SETTINGS & PIN CODE -->
    <div class="wpeb-tab-panel" id="wpebTab_settings" style="display:none;">
        <form method="POST">
            <input type="hidden" name="wpeb_save_settings" value="1" />

            <div class="wpeb-card">
                <h3 class="wpeb-card-title">Безопасность и PIN-код панели управления</h3>
                <p style="color:#64748b; font-size:13px;">Текущий PIN-код защищает панель управления от несанкционированного доступа. Вы можете изменить его в любой момент.</p>

                <div style="max-width:300px;">
                    <label style="font-weight:600; display:block; margin-bottom:4px;">PIN-код администратора</label>
                    <input type="text" name="admin_pin" class="regular-text" value="<?php echo esc_attr($pin); ?>" maxlength="10" required />
                </div>

                <div style="margin-top:20px;">
                    <button type="submit" class="button button-primary">Обновить PIN-код</button>
                </div>
            </div>
        </form>
    </div>

    <!-- TAB 8: 40 DOCUMENTED FEATURES SHOWCASE -->
    <div class="wpeb-tab-panel" id="wpebTab_features" style="display:none;">
        <div class="wpeb-card">
            <h3 class="wpeb-card-title">40 функциональных возможностей плагина</h3>
            <p style="color:#64748b; margin-bottom:20px;">Система онлайн-записи разработана с учетом высоких стандартов архитектуры WordPress и реальных потребностей организаторов мероприятий.</p>

            <div class="wpeb-features-grid">
                <?php
                $features = [
                    1 => ['Каскадный селектор даты/времени', 'Выбор Месяц -> Доступные даты -> Свободные часы с живой фильтрацией'],
                    2 => ['Контроль вместимости сеансов', 'Указание максимального количества мест на каждый слот времени'],
                    3 => ['Авто-сокрытие заполненных слотов', 'Слот полностью исключается из интерфейса посетителя при достижении лимита'],
                    4 => ['Интерактивный конструктор форм', 'Создание текстовых полей, выпадающих списков, чекбоксов, радио-кнопок'],
                    5 => ['Защита админки PIN-кодом 222120', 'Безопасный доступ к панели с возможностью быстрой смены PIN в настройках'],
                    6 => ['Уведомления в Telegram Bot', 'Мгновенная отправка данных о бронировании в личный чат или группу Telegram'],
                    7 => ['Кастомизатор шаблона Telegram', 'Гибкое форматирование текста с тегами {name}, {date}, {time}, {tickets}'],
                    8 => ['Тестирование Telegram в 1 клик', 'Проверка соединения с ботом прямо из панели управления'],
                    9 => ['Email подтверждения клиентам', 'Автоматическая рассылка посадочных талонов на электронную почту'],
                    10 => ['Копия заявки организатору', 'Уведомление администратора о каждой поступившей брони'],
                    11 => ['Полная мобильная адаптация', 'Интерфейс оптимизирован под сенсорные экраны любых смартфонов и планшетов'],
                    12 => ['Визуальный редактор темы', 'Настройка основного акцентного цвета и радиуса скругления элементов'],
                    13 => ['Редактор пользовательского CSS', 'Внедрение персональных стилей оформления без правки файлов плагина'],
                    14 => ['Шорткод [event_booking]', 'Встраивание виджета записи в любую страницу или запись WordPress'],
                    15 => ['Шорткод каталога событий', '[event_booking_catalog] для вывода карточек всех активных мероприятий'],
                    16 => ['Шорткод проверки брони', '[event_booking_lookup] для проверки статуса гостем по номеру талона'],
                    17 => ['Счетчик билетов (степпер)', 'Выбор количества бронируемых мест с проверкой остатка мест на слот'],
                    18 => ['Уникальный 8-значный код брони', 'Генерация хеш-номера билета для каждого бронирования'],
                    19 => ['Интеграция с Google Календарем', '1-клик добавление события в Google Calendar прямо со страницы билета'],
                    20 => ['Печать электронного талона', 'Кнопка быстрой печати посадочного талона участника мероприятия'],
                    21 => ['Аналитический Dashboard KPI', 'Счетчики броней, занятых мест, сеансов и активных мероприятий'],
                    22 => ['Журнал записей со статусами', 'Просмотр всех гостей с контактами, датами и выбранным временем'],
                    23 => ['Экспорт ведомости гостей', 'Печать списков гостей для администраторов и администраторов зала'],
                    24 => ['Генератор слотов по расписанию', 'Быстрое добавление сеансов на выбранные дни и часы'],
                    25 => ['Анти-спам валидация', 'Проверка формата Email и обязательных полей перед подтверждением'],
                    26 => ['Многоязычная готовность (i18n)', 'Все строки плагина подготовлены для локализации (.po / .mo)'],
                    27 => ['Защита от двойного бронирования', 'Потокобезопасная проверка остатка мест перед записью в базу данных'],
                    28 => ['Отслеживание чекинов (Check-in)', 'Фиксация присутствия участников на входе в зал'],
                    29 => ['Совместимость с Elementor', 'Бесшовная вставка через шорткод в Elementor, Gutenberg и Classic Editor'],
                    30 => ['Автономный режим (Standalone)', 'Возможность работы и предпросмотра без активной базы данных MySQL'],
                    31 => ['Поддержка нескольких мероприятий', 'Организация записи на разные события с раздельной вместимостью'],
                    32 => ['Безопасные сессии администратора', 'Авторизация с сохранением токена в защищенной сессии браузера'],
                    33 => ['Индикаторы шагов мастера', 'Наглядный 3-шаговый прогресс: Время -> Данные -> Билет'],
                    34 => ['Асинхронный AJAX обмен', 'Каскадная подгрузка месяцев и дат без перезагрузки всей страницы'],
                    35 => ['Очистка данных при деактивации', 'Чистое удаление временных файлов и корректное хранение настроек'],
                    36 => ['Поддержка платных событий', 'Отображение стоимости участия и валюты билета'],
                    37 => ['Поддержка онлайн и оффлайн', 'Указание адреса локации или ссылки на трансляцию мероприятия'],
                    38 => ['Кроссбраузерная совместимость', 'Корректная работа во всех современных браузерах (Chrome, Safari, Firefox, Edge)'],
                    39 => ['Чистый объектный код PHP 8+', 'Следование стандартам WordPress Coding Standards (WPCS)'],
                    40 => ['Готовый дистрибутив .ZIP', 'Автоматически упакованный архив плагина для установки в 1 клик']
                ];

                foreach ($features as $num => $f): ?>
                    <div class="wpeb-feature-card">
                        <span class="wpeb-feature-num">Функция #<?php echo $num; ?></span>
                        <span class="wpeb-feature-title"><?php echo esc_html($f[0]); ?></span>
                        <span class="wpeb-feature-desc"><?php echo esc_html($f[1]); ?></span>
                    </div>
                <?php endforeach; ?>
            </div>
        </div>
    </div>
</div>
