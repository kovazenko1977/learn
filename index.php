<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>ПожОхранКонтроль — PWA Инспекция ТО</title>

    <!-- PWA Meta Tags & Manifest -->
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#121824">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <meta name="apple-mobile-web-app-title" content="ТО Сигнализация">
    <link rel="apple-touch-icon" href="assets/icons/icon-192.png">

    <!-- Google Fonts & Style -->
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@400;500;600;700;800&family=JetBrains+Mono:wght@500;700&display=swap" rel="stylesheet">
    <link rel="stylesheet" href="assets/css/style.css">
</head>
<body>

    <!-- Fullscreen Tech Preloader -->
    <div id="appPreloader" class="preloader-overlay">
        <div class="preloader-content">
            <div class="pulse-ring"></div>
            <div class="shield-icon">
                <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                    <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                    <path d="M12 8v4"/>
                    <path d="M12 16h.01"/>
                </svg>
            </div>
            <h1 class="preloader-title">ПОЖ-ОХРАН ТО</h1>
            <p class="preloader-subtitle">Инициализация системы и синхронизация точек...</p>
            <div class="preloader-progress-bar">
                <div id="preloaderProgress" class="preloader-progress-fill"></div>
            </div>
            <div id="preloaderStatusText" class="preloader-status">Загрузка данных...</div>
        </div>
    </div>

    <!-- PWA Install Banner Overlay (Automatic Offer if not installed) -->
    <div id="pwaInstallBanner" class="pwa-install-banner hidden">
        <div class="pwa-banner-content">
            <div class="pwa-banner-icon">📱</div>
            <div class="pwa-banner-text">
                <strong>Установить приложение PWA?</strong>
                <span>Быстрый доступ без интернета на вашем телефоне.</span>
            </div>
            <button id="pwaBannerInstallBtn" class="btn primary-btn small">Установить</button>
            <button id="pwaBannerCloseBtn" class="pwa-banner-close">&times;</button>
        </div>
    </div>

    <!-- App Container -->
    <div id="appContainer" class="app-container hidden">

        <!-- Header -->
        <header class="app-header">
            <div class="header-top">
                <div class="brand">
                    <div class="brand-badge">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" class="brand-icon">
                            <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                            <polyline points="9 12 11 14 15 10"/>
                        </svg>
                    </div>
                    <div>
                        <h1 class="app-title">
                            <span class="full-title">ПОЖ-ОХРАН <span>ТО</span></span>
                            <span class="short-title">ПОТ</span>
                        </h1>
                        <div class="technician-badge" id="technicianNameDisplay">Инженер ТО</div>
                    </div>
                </div>
                <div class="header-actions">
                    <button class="icon-btn voice-report-btn" id="voiceTodayBtn" title="Голосовой отчет о визитах за сегодня">
                        🔊 <span class="btn-voice-label">Что сегодня?</span>
                    </button>
                    <button class="icon-btn" id="compactViewToggleBtn" title="Компактный мобильный вид (Скрыть детали для вместимости)">
                        📱
                    </button>
                    <button class="icon-btn" id="themeToggleBtn" title="Сменить тему">
                        <svg class="sun-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="5"/><line x1="12" y1="1" x2="12" y2="3"/><line x1="12" y1="21" x2="12" y2="23"/><line x1="4.22" y1="4.22" x2="5.64" y2="5.64"/><line x1="18.36" y1="18.36" x2="19.78" y2="19.78"/><line x1="1" y1="12" x2="3" y2="12"/><line x1="21" y1="12" x2="23" y2="12"/><line x1="4.22" y1="19.78" x2="5.64" y2="18.36"/><line x1="18.36" y1="5.64" x2="19.78" y2="4.22"/></svg>
                        <svg class="moon-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M21 12.79A9 9 0 1 1 11.21 3 7 7 0 0 0 21 12.79z"/></svg>
                    </button>
                    <button class="icon-btn primary" id="addPointBtn" title="Добавить новую точку">
                        <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><line x1="12" y1="5" x2="12" y2="19"/><line x1="5" y1="12" x2="19" y2="12"/></svg>
                    </button>
                </div>
            </div>

            <!-- Stats Bar -->
            <div class="stats-card">
                <div class="stat-box urgent" id="pendingStatBox">
                    <div class="stat-number" id="pendingCount">0</div>
                    <div class="stat-label">Требуют ТО</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-box done" id="doneStatBox">
                    <div class="stat-number" id="completedCount">0</div>
                    <div class="stat-label">Посещено</div>
                </div>
                <div class="stat-divider"></div>
                <div class="stat-box info">
                    <div class="stat-number" id="intervalDisplay">30 дн.</div>
                    <div class="stat-label">Интервал цикла</div>
                </div>
            </div>
        </header>

        <!-- Search & Control Bar -->
        <section class="controls-bar">
            <div class="search-box">
                <svg class="search-icon" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="11" cy="11" r="8"/><line x1="21" y1="21" x2="16.65" y2="16.65"/></svg>
                <input type="text" id="searchInput" placeholder="Поиск по названию, адресу или SIM..." autocomplete="off">
                <button class="voice-dictate-btn" data-target="searchInput" title="Голосовой ввод поиска">🎤</button>
                <button class="clear-search-btn hidden" id="clearSearchBtn">&times;</button>
            </div>
        </section>

        <!-- Tab Navigation -->
        <nav class="main-tabs">
            <button class="tab-btn active" data-tab="unvisited">
                <span class="tab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                </span>
                <span class="tab-text">Не посещены</span>
                <span class="badge badge-warning" id="unvisitedBadge">0</span>
            </button>

            <button class="tab-btn" data-tab="visited">
                <span class="tab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                </span>
                <span class="tab-text">Посещенные</span>
                <span class="badge badge-success" id="visitedBadge">0</span>
            </button>

            <button class="tab-btn" data-tab="all">
                <span class="tab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><line x1="8" y1="6" x2="21" y2="6"/><line x1="8" y1="12" x2="21" y2="12"/><line x1="8" y1="18" x2="21" y2="18"/><line x1="3" y1="6" x2="3.01" y2="6"/><line x1="3" y1="12" x2="3.01" y2="12"/><line x1="3" y1="18" x2="3.01" y2="18"/></svg>
                </span>
                <span class="tab-text">Все объекты</span>
            </button>

            <button class="tab-btn" data-tab="history">
                <span class="tab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M12 8v4l3 3"/><path d="M3.05 11a9 9 0 1 1 .5 4m-.5 5v-5h5"/></svg>
                </span>
                <span class="tab-text">Журнал</span>
            </button>

            <button class="tab-btn" data-tab="settings">
                <span class="tab-icon">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="3"/><path d="M19.4 15a1.65 1.65 0 0 0 .33 1.82l.06.06a2 2 0 0 1 0 2.83 2 2 0 0 1-2.83 0l-.06-.06a1.65 1.65 0 0 0-1.82-.33 1.65 1.65 0 0 0-1 1.51V21a2 2 0 0 1-2 2 2 2 0 0 1-2-2v-.09A1.65 1.65 0 0 0 9 19.4a1.65 1.65 0 0 0-1.82.33l-.06.06a2 2 0 0 1-2.83 0 2 2 0 0 1 0-2.83l.06-.06a1.65 1.65 0 0 0 .33-1.82 1.65 1.65 0 0 0-1.51-1H3a2 2 0 0 1-2-2 2 2 0 0 1 2-2h.09A1.65 1.65 0 0 0 4.6 9a1.65 1.65 0 0 0-.33-1.82l-.06-.06a2 2 0 0 1 0-2.83 2 2 0 0 1 2.83 0l.06.06a1.65 1.65 0 0 0 1.82.33H9a1.65 1.65 0 0 0 1-1.51V3a2 2 0 0 1 2-2 2 2 0 0 1 2 2v.09a1.65 1.65 0 0 0 1 1.51 1.65 1.65 0 0 0 1.82-.33l.06-.06a2 2 0 0 1 2.83 0 2 2 0 0 1 0 2.83l-.06.06a1.65 1.65 0 0 0-.33 1.82V9a1.65 1.65 0 0 0 1.51 1H21a2 2 0 0 1 2 2 2 2 0 0 1-2 2h-.09a1.65 1.65 0 0 0-1.51 1z"/></svg>
                </span>
                <span class="tab-text">Настройки</span>
            </button>
        </nav>

        <!-- Main Content Area -->
        <main class="main-content">

            <!-- Unvisited Points List -->
            <section id="tabUnvisited" class="tab-panel active">
                <div class="section-banner warning-banner">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <span>Точки, требующие обслуживания в текущем интервале. Выберите точку для отметки посещения.</span>
                </div>
                <div id="unvisitedList" class="cards-grid"></div>
            </section>

            <!-- Visited Points List -->
            <section id="tabVisited" class="tab-panel">
                <div class="section-banner success-banner">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><path d="M22 11.08V12a10 10 0 1 1-5.93-9.14"/><polyline points="22 4 12 14.01 9 11.01"/></svg>
                    <span>Точки, уже обслуженные в этом интервале. По истечении интервала они автоматически вернутся в список.</span>
                </div>
                <div id="visitedList" class="cards-grid"></div>
            </section>

            <!-- All Points Directory -->
            <section id="tabAll" class="tab-panel">
                <div class="section-banner info-banner">
                    <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2"><circle cx="12" cy="12" r="10"/><line x1="12" y1="16" x2="12" y2="12"/><line x1="12" y1="8" x2="12.01" y2="8"/></svg>
                    <span>Полный реестр обслуживаемых объектов. Здесь можно добавлять и редактировать точки.</span>
                </div>
                <div id="allList" class="cards-grid"></div>
            </section>

            <!-- Visit History Log -->
            <section id="tabHistory" class="tab-panel">
                <div class="history-controls">
                    <div>
                        <h3>Журнал проверок и заметок</h3>
                        <p class="history-subtitle">Выберите дату в календаре для просмотра посещений за день</p>
                    </div>
                    <div class="history-date-picker-box">
                        <button type="button" class="btn primary-btn small" id="openCalendarModalBtn">📆 Графический календарь</button>
                        <label for="historyDatePicker">📅 Выбор даты:</label>
                        <input type="date" id="historyDatePicker" class="form-control date-picker-input">
                        <button type="button" class="btn secondary-btn small" id="historyTodayBtn">Сегодня</button>
                        <button type="button" class="btn secondary-btn small" id="historyResetDateBtn">Все даты</button>
                    </div>
                </div>
                <div class="filter-pills">
                    <button class="pill-btn active" data-filter="all">Все записи</button>
                    <button class="pill-btn" data-filter="defects">⚠️ С недостатками</button>
                </div>
                <div id="historyList" class="history-timeline"></div>
            </section>

            <!-- Settings View -->
            <section id="tabSettings" class="tab-panel">
                <div class="settings-card">
                    <h2>⚙️ Настройки сервисного обслуживания</h2>
                    <p class="settings-intro">Управляйте интервалом посещения точек, ФИО техника и режимом работы.</p>

                    <form id="settingsForm">
                        <div class="form-group">
                            <label for="intervalModeSelect">Режим расчета интервала</label>
                            <select id="intervalModeSelect" class="form-control">
                                <option value="days">Фиксированное количество дней</option>
                                <option value="calendar_month">Календарный месяц (Сброс 1-го числа каждого месяца)</option>
                            </select>
                        </div>

                        <div class="form-group" id="intervalDaysGroup">
                            <label for="intervalDaysInput">Интервал посещений (в днях)</label>
                            <input type="number" id="intervalDaysInput" class="form-control" min="1" max="365" value="30">
                            <small class="form-help">Точка считается не посещенной, если с момента последнего визита прошло больше этого количества дней.</small>
                        </div>

                        <div class="form-group">
                            <label for="technicianNameInput">ФИО Сервисного инженера (Техника)</label>
                            <input type="text" id="technicianNameInput" class="form-control" placeholder="Например: Иванов А.П.">
                        </div>

                        <div class="form-group">
                            <label for="companyNameInput">Наименование обслуживающей организации</label>
                            <input type="text" id="companyNameInput" class="form-control" placeholder="Например: ООО «СпецПожСервис»">
                        </div>

                        <div class="form-actions" style="flex-wrap: wrap;">
                            <button type="submit" class="btn primary-btn">Сохранить настройки</button>
                            <!-- Force refresh button to sync directly from server, bypassing local cache -->
                            <button type="button" class="btn secondary-btn" id="forceRefreshBtn" title="Принудительно обновить данные с сервера, не взирая на локальный кэш">
                                🔄 Принудительно обновить данные
                            </button>
                            <!-- Red button to reset all point intervals -->
                            <button type="button" class="btn danger-btn-solid" id="resetIntervalsBtn">
                                🔄 Сбросить все интервалы
                            </button>
                        </div>
                    </form>
                </div>

                <div class="pwa-info-card">
                    <h3>📱 Установка PWA на устройство</h3>
                    <p>Это приложение работает автономно. Вы можете добавить его на главный экран смартфона или планшета для удобного доступа во время выездов.</p>
                    <button id="pwaInstallBtn" class="btn primary-btn hidden">Установить PWA на экран</button>
                </div>
            </section>

        </main>
    </div>

    <!-- Modal: Point History & Defects -->
    <div id="pointHistoryModal" class="modal-backdrop hidden">
        <div class="modal-dialog modal-lg">
            <div class="modal-header">
                <h3>📜 История проверок и замечаний</h3>
                <button class="modal-close" id="closePointHistoryModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="point-summary-box">
                    <h4 id="pointHistoryModalName">Название организации</h4>
                    <p id="pointHistoryModalAddress" class="summary-address">Адрес объекта</p>
                    <div class="sim-badge-container">
                        <span class="sim-badge" id="pointHistoryModalSim">SIM: +7 (900) 000-00-00</span>
                    </div>
                </div>

                <div class="history-modal-stats">
                    <div class="stat-pill"><span id="pointHistoryModalTotalVisits">0</span> посещений</div>
                    <div class="stat-pill urgent"><span id="pointHistoryModalTotalDefects">0</span> замечаний</div>
                </div>

                <div id="pointHistoryModalTimeline" class="history-timeline" style="margin-top: 16px;"></div>

                <div class="modal-actions" style="margin-top: 20px;">
                    <button type="button" class="btn secondary-btn" id="closePointHistoryModalBottomBtn">Закрыть</button>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Mark Visit -->
    <div id="visitModal" class="modal-backdrop hidden">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3>Отметка посещения объекта</h3>
                <button class="modal-close" id="closeVisitModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="point-summary-box">
                    <h4 id="visitModalPointName">Название объекта</h4>
                    <p id="visitModalPointAddress" class="summary-address">Адрес объекта</p>
                    <div class="sim-badge-container">
                        <span class="sim-chip">
                            <span id="visitModalSimNumber">SIM: +7 (900) 000-00-00</span>
                        </span>
                    </div>
                </div>

                <form id="visitForm">
                    <input type="hidden" id="visitPointId">

                    <div class="form-group checkbox-group">
                        <label class="custom-checkbox">
                            <input type="checkbox" id="visitDefectsCheckbox">
                            <span class="checkmark"></span>
                            <span class="label-text">⚠️ Обнаружены недостатки / Неисправности</span>
                        </label>
                    </div>

                    <div class="form-group hidden" id="defectsDescriptionGroup">
                        <div class="label-with-voice">
                            <label for="visitDefectsText">Описание недостатков / Замечания</label>
                            <button type="button" class="voice-dictate-btn inline" data-target="visitDefectsText" title="Надиктовать голосом">🎤 Голос</button>
                        </div>
                        <textarea id="visitDefectsText" class="form-control" rows="3" placeholder="Укажите, что именно требует ремонта или замены (например: сел АКБ в РИП, запылен шлейф №3)..."></textarea>
                    </div>

                    <div class="form-group">
                        <div class="label-with-voice">
                            <label for="visitNotesText">Дополнительная информация / Проведенные работы</label>
                            <button type="button" class="voice-dictate-btn inline" data-target="visitNotesText" title="Надиктовать голосом">🎤 Голос</button>
                        </div>
                        <textarea id="visitNotesText" class="form-control" rows="2" placeholder="Например: Проведена продувка извещателей, сработка в норме..."></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn secondary-btn" id="cancelVisitBtn">Отмена</button>
                        <button type="submit" class="btn success-btn">
                            <svg viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5"><polyline points="20 6 9 17 4 12"/></svg>
                            Подтвердить посещение
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Modal: Graphical Calendar -->
    <div id="calendarModal" class="modal-backdrop hidden">
        <div class="modal-dialog modal-lg">
            <div class="modal-header">
                <h3>📆 Графический календарь посещений</h3>
                <button class="modal-close" id="closeCalendarModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <div class="calendar-header-nav">
                    <button type="button" class="btn secondary-btn small" id="calPrevMonthBtn">&larr; Предыдущий</button>
                    <h4 id="calMonthYearTitle" class="calendar-month-title">Март 2025</h4>
                    <button type="button" class="btn secondary-btn small" id="calNextMonthBtn">Следующий &rarr;</button>
                </div>
                <div class="calendar-grid-container">
                    <div class="calendar-weekdays">
                        <div>Пн</div><div>Вт</div><div>Ср</div><div>Чт</div><div>Пт</div><div>Сб</div><div>Вс</div>
                    </div>
                    <div id="calendarDaysGrid" class="calendar-days-grid"></div>
                </div>
                <div class="calendar-legend">
                    <span class="legend-item"><span class="dot dot-success"></span> Есть посещения</span>
                    <span class="legend-item"><span class="dot dot-danger"></span> Были недостатки</span>
                    <span class="legend-item"><span class="dot dot-today"></span> Сегодня</span>
                </div>
            </div>
        </div>
    </div>

    <!-- Modal: Add / Edit Point -->
    <div id="pointModal" class="modal-backdrop hidden">
        <div class="modal-dialog">
            <div class="modal-header">
                <h3 id="pointModalTitle">Добавление объекта</h3>
                <button class="modal-close" id="closePointModalBtn">&times;</button>
            </div>
            <div class="modal-body">
                <form id="pointForm">
                    <input type="hidden" id="pointFormId">

                    <div class="form-group">
                        <div class="label-with-voice">
                            <label for="pointFormName">Наименование точки / объекта *</label>
                            <button type="button" class="voice-dictate-btn inline" data-target="pointFormName">🎤 Голос</button>
                        </div>
                        <input type="text" id="pointFormName" class="form-control" required placeholder="Например: ТЦ «Гранит» — Главный вент узел">
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="pointFormSim">Номер SIM-карты *</label>
                            <div class="input-with-icon">
                                <span class="input-icon">📱</span>
                                <input type="text" id="pointFormSim" class="form-control" required placeholder="+7 (901) 123-45-67">
                            </div>
                        </div>
                        <div class="form-group col-6">
                            <label for="pointFormContract">Номер договора</label>
                            <input type="text" id="pointFormContract" class="form-control" placeholder="Д-2025/01-А">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="label-with-voice">
                            <label for="pointFormAddress">Адрес расположения *</label>
                            <button type="button" class="voice-dictate-btn inline" data-target="pointFormAddress">🎤 Голос</button>
                        </div>
                        <input type="text" id="pointFormAddress" class="form-control" required placeholder="ул. Ленина, д. 45, этаж 2">
                    </div>

                    <div class="form-group">
                        <label for="pointFormEquipment">Тип оборудования / Сигнализации</label>
                        <input type="text" id="pointFormEquipment" class="form-control" placeholder="Пожарная сигнализация, ОПС, КТС, Вiсп...">
                    </div>

                    <div class="form-row">
                        <div class="form-group col-6">
                            <label for="pointFormContactPerson">Контактное лицо</label>
                            <input type="text" id="pointFormContactPerson" class="form-control" placeholder="Ответственный на объекте">
                        </div>
                        <div class="form-group col-6">
                            <label for="pointFormContactPhone">Телефон</label>
                            <input type="text" id="pointFormContactPhone" class="form-control" placeholder="+7 (900) 000-00-00">
                        </div>
                    </div>

                    <div class="form-group">
                        <div class="label-with-voice">
                            <label for="pointFormNotes">Примечание / Инструкция доступа</label>
                            <button type="button" class="voice-dictate-btn inline" data-target="pointFormNotes">🎤 Голос</button>
                        </div>
                        <textarea id="pointFormNotes" class="form-control" rows="2" placeholder="Ключи у охраны, правила прохода..."></textarea>
                    </div>

                    <div class="modal-actions">
                        <button type="button" class="btn danger-btn hidden" id="deletePointBtn">Удалить точку</button>
                        <button type="button" class="btn secondary-btn" id="cancelPointBtn">Отмена</button>
                        <button type="submit" class="btn primary-btn" id="savePointBtn">Сохранить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Notification Toast -->
    <div id="toastContainer" class="toast-container"></div>

    <script src="assets/js/app.js"></script>
</body>
</html>
