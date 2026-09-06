<!DOCTYPE html>
<html lang="ru" data-theme="dark">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <title>Voice Organizer & Notes</title>
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="assets/css/app.css">
    <!-- Font Awesome for sleek modern mobile icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div id="app" class="app-container">

        <!-- Top Header Bar -->
        <header class="app-header">
            <div class="header-left">
                <div class="brand-logo"><i class="fa-solid fa-microphone-lines"></i></div>
                <div class="header-title-wrap">
                    <h1 id="headerTitle">Задачи и Заметки</h1>
                    <span id="headerSubTitle" class="header-subtitle">Заполнение голосом</span>
                </div>
            </div>
            <div class="header-actions">
                <button id="btnToggleTheme" class="icon-btn" title="Сменить тему">
                    <i class="fa-solid fa-moon"></i>
                </button>
                <button id="btnQuickSearch" class="icon-btn" title="Поиск">
                    <i class="fa-solid fa-magnifying-glass"></i>
                </button>
            </div>
        </header>

        <!-- Search Bar (Collapsible) -->
        <div id="searchBar" class="search-bar-wrap hidden">
            <div class="search-input-box">
                <i class="fa-solid fa-magnifying-glass search-icon"></i>
                <input type="text" id="globalSearchInput" placeholder="Поиск по задачам, заметкам и делам...">
                <button id="btnCloseSearch" class="close-search-btn"><i class="fa-solid fa-xmark"></i></button>
            </div>
        </div>

        <!-- Main View Container -->
        <main class="app-content">

            <!-- TAB 1: TASKS & REMINDERS VIEW -->
            <section id="viewTasks" class="view-section active">
                <div class="section-top-bar">
                    <div class="category-pills" id="taskCategoryPills">
                        <button class="pill active" data-category="all">Все</button>
                        <button class="pill" data-category="Личное">Личное</button>
                        <button class="pill" data-category="Работа">Работа</button>
                        <button class="pill" data-category="Покупки">Покупки</button>
                        <button class="pill" data-category="Здоровье">Здоровье</button>
                    </div>
                </div>

                <div class="quick-add-card">
                    <div class="input-with-voice">
                        <input type="text" id="taskTitleInput" placeholder="Новая задача (или нажми 🎙️)...">
                        <button id="btnVoiceInputTask" class="mic-inline-btn" title="Голосовой ввод">
                            <i class="fa-solid fa-microphone"></i>
                        </button>
                    </div>
                    <div class="quick-add-options hidden" id="taskOptions">
                        <div class="option-row">
                            <div class="option-item">
                                <i class="fa-regular fa-calendar-days"></i>
                                <input type="date" id="taskDueDateInput">
                            </div>
                            <div class="option-item">
                                <i class="fa-regular fa-clock"></i>
                                <input type="time" id="taskDueTimeInput">
                            </div>
                            <div class="option-item">
                                <i class="fa-solid fa-flag"></i>
                                <select id="taskPriorityInput">
                                    <option value="low">Низкий</option>
                                    <option value="medium" selected>Средний</option>
                                    <option value="high">Высокий</option>
                                    <option value="urgent">🔥 Срочный</option>
                                </select>
                            </div>
                        </div>
                        <div class="add-btn-wrap">
                            <button id="btnAddTask" class="btn-primary-mobile">
                                <i class="fa-solid fa-plus"></i> Добавить задачу
                            </button>
                        </div>
                    </div>
                </div>

                <div class="tasks-status-filter">
                    <button class="status-tab active" data-status="pending">В процессе (<span id="countPendingTasks">0</span>)</button>
                    <button class="status-tab" data-status="completed">Завершенные (<span id="countCompletedTasks">0</span>)</button>
                </div>

                <div id="taskList" class="task-cards-container">
                    <!-- Task items populated dynamically -->
                </div>
            </section>

            <!-- TAB 2: DAILY PLANNER & HABITS VIEW -->
            <section id="viewPlanner" class="view-section hidden">
                <div class="date-navigator">
                    <button id="btnPrevDay" class="date-nav-btn"><i class="fa-solid fa-chevron-left"></i></button>
                    <div class="current-date-wrap">
                        <input type="date" id="plannerDatePicker" class="hidden-date-input">
                        <span id="plannerDateDisplay" class="date-display-text">Сегодня, 15 Мая</span>
                    </div>
                    <button id="btnNextDay" class="date-nav-btn"><i class="fa-solid fa-chevron-right"></i></button>
                </div>

                <div class="habits-bar-card">
                    <div class="card-header">
                        <h3><i class="fa-solid fa-fire text-orange"></i> Ежедневные Трекеры И Привычки</h3>
                        <button id="btnAddHabit" class="text-btn">+ Привычка</button>
                    </div>
                    <div id="habitsList" class="habits-horizontal-scroll">
                        <!-- Habits dynamically rendered -->
                    </div>
                </div>

                <div class="planner-header-bar">
                    <h3><i class="fa-solid fa-calendar-day"></i> Расписание дня</h3>
                    <button id="btnAddPlannerEvent" class="btn-small-primary">+ Событие</button>
                </div>

                <div id="plannerTimeline" class="planner-timeline">
                    <!-- Timeline 00:00 - 23:00 dynamically populated -->
                </div>
            </section>

            <!-- TAB 3: NOTES & VOICE MEMOS VIEW -->
            <section id="viewNotes" class="view-section hidden">
                <div class="notes-controls">
                    <div class="notes-search-tags" id="noteTagsPills">
                        <button class="pill active" data-tag="all">Все заметки</button>
                        <button class="pill" data-tag="pinned">📌 Закрепленные</button>
                        <button class="pill" data-tag="audio">🎙️ Голосовые</button>
                        <button class="pill" data-tag="Идеи">Идеи</button>
                        <button class="pill" data-tag="Учеба">Учеба</button>
                    </div>
                    <button id="btnCreateNote" class="btn-primary-mobile">
                        <i class="fa-solid fa-pen-to-square"></i> Новая заметка
                    </button>
                </div>

                <div id="notesGrid" class="notes-grid-container">
                    <!-- Notes cards populated dynamically -->
                </div>
            </section>

            <!-- TAB 4: STATS & ANALYTICS VIEW -->
            <section id="viewStats" class="view-section hidden">
                <div class="stats-overview-grid">
                    <div class="stat-card">
                        <div class="stat-icon bg-indigo"><i class="fa-solid fa-check-double"></i></div>
                        <div class="stat-value" id="statTaskProgress">0%</div>
                        <div class="stat-label">Прогресс задач</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-emerald"><i class="fa-solid fa-circle-check"></i></div>
                        <div class="stat-value" id="statCompletedCount">0</div>
                        <div class="stat-label">Выполнено задач</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-purple"><i class="fa-solid fa-note-sticky"></i></div>
                        <div class="stat-value" id="statNotesCount">0</div>
                        <div class="stat-label">Всего заметок</div>
                    </div>
                    <div class="stat-card">
                        <div class="stat-icon bg-amber"><i class="fa-solid fa-fire"></i></div>
                        <div class="stat-value" id="statHabitsCount">0</div>
                        <div class="stat-label">Активные привычки</div>
                    </div>
                </div>

                <div class="chart-card">
                    <h3><i class="fa-solid fa-chart-line"></i> Статистика привычек и серий</h3>
                    <div id="habitStreaksList" class="habit-streaks-list">
                        <!-- Habit streak bars rendered here -->
                    </div>
                </div>
            </section>

            <!-- TAB 5: SETTINGS & BACKUP VIEW -->
            <section id="viewSettings" class="view-section hidden">
                <div class="settings-group">
                    <div class="settings-header">Настройки Голоса и Распознавания</div>
                    <div class="setting-item">
                        <div class="setting-text">
                            <div class="setting-title">Язык распознавания</div>
                            <div class="setting-desc">Выберите язык голосового ввода</div>
                        </div>
                        <select id="voiceLangSelect" class="setting-select">
                            <option value="ru-RU" selected>Русский (ru-RU)</option>
                            <option value="en-US">English (en-US)</option>
                        </select>
                    </div>
                    <div class="setting-item">
                        <div class="setting-text">
                            <div class="setting-title">Автоматический разбор дат</div>
                            <div class="setting-desc">Извлекать "завтра", "сегодня", время из речи</div>
                        </div>
                        <input type="checkbox" id="voiceParseDatesToggle" checked class="mobile-switch">
                    </div>
                </div>

                <div class="settings-group">
                    <div class="settings-header">Уведомления и Напоминания</div>
                    <div class="setting-item">
                        <div class="setting-text">
                            <div class="setting-title">Браузерные push-уведомления</div>
                            <div class="setting-desc">Напоминать о событиях и задачах</div>
                        </div>
                        <button id="btnEnableNotifications" class="btn-small-outline">Включить</button>
                    </div>
                </div>

                <div class="settings-group">
                    <div class="settings-header">Управление Данными</div>
                    <div class="setting-item">
                        <div class="setting-text">
                            <div class="setting-title">Экспорт резервной копии</div>
                            <div class="setting-desc">Скачать все задачи и заметки в JSON</div>
                        </div>
                        <button id="btnExportData" class="btn-small-outline"><i class="fa-solid fa-download"></i> Скачать</button>
                    </div>
                    <div class="setting-item">
                        <div class="setting-text">
                            <div class="setting-title">Импорт резервной копии</div>
                            <div class="setting-desc">Восстановить данные из файла</div>
                        </div>
                        <label for="importFileInput" class="btn-small-outline cursor-pointer">
                            <i class="fa-solid fa-upload"></i> Загрузить
                        </label>
                        <input type="file" id="importFileInput" accept=".json" class="hidden">
                    </div>
                </div>
            </section>

        </main>

        <!-- Floating Action Microphone Overlay Button -->
        <div class="fab-voice-container">
            <button id="fabMicBtn" class="fab-mic-btn" title="Записать голосом">
                <i class="fa-solid fa-microphone"></i>
                <span class="mic-pulse-ring"></span>
            </button>
        </div>

        <!-- Sticky Mobile Bottom Navigation Tab Bar -->
        <nav class="bottom-nav">
            <button class="nav-tab active" data-target="viewTasks">
                <i class="fa-solid fa-list-check"></i>
                <span>Задачи</span>
            </button>
            <button class="nav-tab" data-target="viewPlanner">
                <i class="fa-regular fa-calendar-check"></i>
                <span>Ежедневник</span>
            </button>
            <div class="nav-fab-spacer"></div> <!-- Placeholder spacing for centered FAB -->
            <button class="nav-tab" data-target="viewNotes">
                <i class="fa-regular fa-note-sticky"></i>
                <span>Блокнот</span>
            </button>
            <button class="nav-tab" data-target="viewStats">
                <i class="fa-solid fa-chart-pie"></i>
                <span>Обзор</span>
            </button>
            <button class="nav-tab" data-target="viewSettings">
                <i class="fa-solid fa-gear"></i>
                <span>Опции</span>
            </button>
        </nav>

        <!-- Voice Recognition Modal Overlay -->
        <div id="voiceModal" class="modal-overlay hidden">
            <div class="modal-card voice-modal-card">
                <button id="btnCloseVoiceModal" class="modal-close-btn"><i class="fa-solid fa-xmark"></i></button>
                <div class="voice-status-icon">
                    <i class="fa-solid fa-microphone mic-icon-anim"></i>
                </div>
                <h3 id="voiceModalStatus">Слушаю... Говорите!</h3>
                <p class="voice-hint">Например: "Напомнить купить молоко завтра в 18:00" или "Заметка идею для проекта"</p>

                <div id="voiceTranscriptBox" class="transcript-preview">
                    <span id="voiceTranscriptText">...</span>
                </div>

                <div class="voice-modal-actions">
                    <button id="btnStopVoiceRecord" class="btn-danger-mobile">
                        <i class="fa-solid fa-square"></i> Остановить
                    </button>
                    <button id="btnConfirmVoiceAction" class="btn-primary-mobile hidden">
                        <i class="fa-solid fa-check"></i> Сохранить
                    </button>
                </div>
            </div>
        </div>

        <!-- Create / Edit Note Modal -->
        <div id="noteModal" class="modal-overlay hidden">
            <div class="modal-card">
                <div class="modal-header">
                    <h3 id="noteModalTitle">Новая Заметка</h3>
                    <button id="btnCloseNoteModal" class="modal-close-btn"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <input type="hidden" id="noteIdInput">
                    <input type="text" id="noteTitleInput" placeholder="Заголовок заметки..." class="modal-input-title">
                    <textarea id="noteContentInput" placeholder="Текст заметки или надиктуйте голосом..." class="modal-textarea"></textarea>

                    <div class="note-options-row">
                        <div class="select-badge">
                            <i class="fa-solid fa-folder"></i>
                            <select id="noteCategorySelect">
                                <option value="Заметки">Заметки</option>
                                <option value="Идеи">Идеи</option>
                                <option value="Учеба">Учеба</option>
                                <option value="Личное">Личное</option>
                                <option value="Работа">Работа</option>
                            </select>
                        </div>
                        <label class="checkbox-label">
                            <input type="checkbox" id="notePinnedInput"> 📌 Закрепить
                        </label>
                    </div>

                    <!-- Voice memo recording box inside Note Modal -->
                    <div class="audio-record-box">
                        <div class="audio-rec-header">
                            <span><i class="fa-solid fa-microphone"></i> Голосовое аудио к заметке</span>
                            <button id="btnRecAudioNote" class="btn-rec-audio"><i class="fa-solid fa-circle"></i> Записать</button>
                        </div>
                        <div id="noteAudioPlayerContainer" class="audio-player-wrap hidden">
                            <audio id="noteAudioPlayer" controls></audio>
                            <button id="btnRemoveAudioNote" class="text-danger-btn"><i class="fa-solid fa-trash"></i> Удалить аудио</button>
                        </div>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="btnSaveNote" class="btn-primary-mobile"><i class="fa-solid fa-floppy-disk"></i> Сохранить заметку</button>
                </div>
            </div>
        </div>

        <!-- Habit Modal -->
        <div id="habitModal" class="modal-overlay hidden">
            <div class="modal-card">
                <div class="modal-header">
                    <h3>Новая Привычка</h3>
                    <button id="btnCloseHabitModal" class="modal-close-btn"><i class="fa-solid fa-xmark"></i></button>
                </div>
                <div class="modal-body">
                    <input type="text" id="habitTitleInput" placeholder="Название привычки (напр., Пей 2L воды)" class="modal-input-title">
                    <div class="icon-selector">
                        <span>Иконка:</span>
                        <select id="habitIconSelect">
                            <option value="⚡">⚡ Энергия</option>
                            <option value="💧">💧 Вода</option>
                            <option value="🏃">🏃 Спорт</option>
                            <option value="📚">📚 Чтение</option>
                            <option value="🧘">🧘 Медитация</option>
                            <option value="💊">💊 Витамины</option>
                        </select>
                    </div>
                </div>
                <div class="modal-footer">
                    <button id="btnSaveHabit" class="btn-primary-mobile">Сохранить</button>
                </div>
            </div>
        </div>

        <!-- Toast Notifications Container -->
        <div id="toastContainer" class="toast-container"></div>

    </div>

    <!-- Application Javascript Logic -->
    <script src="assets/js/voice.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
