<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Моя Семья - Профессиональное управление домом</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link href="https://fonts.googleapis.com/css2?family=Montserrat:wght@400;600;700;900&display=swap" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#0078d4">
    <link rel="apple-touch-icon" href="assets/img/icon-192.png">
</head>
<body class="theme-dark">
    <div id="app">
        <!-- Login Screen -->
        <div id="login-screen" class="screen active">
            <div class="login-card">
                <div class="logo">
                    <i class="fas fa-circle-nodes"></i>
                    <span>FAMILY NODE</span>
                </div>
                <h2>Привет!</h2>
                <p>Введи 6-значный код доступа</p>
                <div class="passcode-input">
                    <input type="password" id="passcode" maxlength="6" inputmode="numeric" placeholder="••••••">
                </div>
                <button id="login-btn">Продолжить</button>
                <div id="login-error" class="error-msg"></div>
            </div>
        </div>

        <!-- Main App Screen -->
        <div id="main-screen" class="screen">
            <div id="global-preloader" class="preloader">
                <div class="spinner"></div>
            </div>

            <aside class="sidebar">
                <div class="sidebar-header">
                    <div class="logo-mini">
                        <i class="fas fa-circle-nodes"></i>
                    </div>
                    <div class="user-profile-mini">
                        <div class="avatar" id="current-avatar"></div>
                        <div class="user-meta">
                            <span id="current-username"></span>
                        </div>
                    </div>
                </div>
                <nav class="nav-items">
                    <div class="nav-item active" data-view="chat">
                        <i class="fas fa-bubble-message"></i>
                        <span class="nav-label">Сообщения</span>
                    </div>
                    <div class="nav-item" data-view="tasks">
                        <i class="fas fa-layer-group"></i>
                        <span class="nav-label">Проекты</span>
                    </div>
                    <div class="nav-item" data-view="achievements">
                        <i class="fas fa-bolt-lightning"></i>
                        <span class="nav-label">Рейтинг</span>
                    </div>
                    <div class="nav-item" data-view="shopping">
                        <i class="fas fa-bag-shopping"></i>
                        <span class="nav-label">Покупки</span>
                    </div>
                    <div class="nav-item" data-view="settings">
                        <i class="fas fa-sliders"></i>
                        <span class="nav-label">Опции</span>
                    </div>
                </nav>
                <div class="sidebar-footer">
                    <button id="logout-btn-sidebar" class="btn-ghost"><i class="fas fa-arrow-right-from-bracket"></i></button>
                </div>
            </aside>

            <main class="content">
                <header class="top-bar">
                    <h1 id="view-title">Сообщения</h1>
                    <div class="top-actions">
                        <!-- Contextual actions will appear here -->
                    </div>
                </header>

                <!-- Chat View -->
                <section id="chat-view" class="view active">
                    <div id="chat-messages"></div>
                    <div class="chat-input-area">
                        <div class="input-wrapper">
                            <label for="image-upload" class="upload-btn-icon">
                                <i class="fas fa-paperclip"></i>
                                <input type="file" id="image-upload" accept="image/*" hidden>
                            </label>
                            <input type="text" id="chat-input" placeholder="Напишите что-нибудь..." autocomplete="off">
                            <button id="send-chat-btn"><i class="fas fa-paper-plane"></i></button>
                        </div>
                    </div>
                </section>

                <!-- Tasks View -->
                <section id="tasks-view" class="view">
                    <div id="task-list" class="task-grid"></div>
                    <button id="new-task-btn" class="fab"><i class="fas fa-plus"></i></button>
                </section>

                <!-- Achievements View -->
                <section id="achievements-view" class="view">
                    <div class="view-header-description">
                        <h2>Рейтинг активности</h2>
                        <p>Выполняйте задачи, чтобы получать баллы и открывать новые уровни.</p>
                    </div>
                    <div id="achievements-list" class="achievements-grid"></div>
                </section>

                <!-- Shopping View -->
                <section id="shopping-view" class="view">
                    <div id="shopping-list" class="shopping-container"></div>
                    <div class="shopping-fab-area">
                        <div class="input-wrapper">
                            <input type="text" id="shopping-input" placeholder="Что нужно купить?">
                            <button id="add-shopping-btn" class="btn-icon-accent"><i class="fas fa-plus"></i></button>
                        </div>
                    </div>
                </section>

                <!-- Settings View -->
                <section id="settings-view" class="view">
                    <div class="settings-container">
                        <div class="setting-group">
                            <h3>Профиль</h3>
                            <div class="setting-row">
                                <label>Ваше имя</label>
                                <input type="text" id="setting-my-name" placeholder="Имя">
                            </div>
                            <button id="save-profile-btn" class="btn-secondary">Обновить данные</button>
                        </div>

                        <div class="setting-group">
                            <h3>Внешний вид</h3>
                            <div class="setting-row">
                                <label>Тема оформления</label>
                                <select id="setting-theme">
                                    <option value="dark">Темная (Deep)</option>
                                    <option value="light">Светлая (Clean)</option>
                                </select>
                            </div>
                            <div class="setting-row">
                                <label>Акцентный цвет</label>
                                <input type="color" id="setting-accent" value="#3b82f6">
                            </div>
                        </div>

                        <div class="setting-group">
                            <h3>Текст</h3>
                            <div class="setting-row">
                                <label>Шрифт</label>
                                <select id="setting-font">
                                    <option value="'Inter', sans-serif">Inter (Modern)</option>
                                    <option value="'Montserrat', sans-serif">Montserrat</option>
                                    <option value="'Roboto', sans-serif">Roboto</option>
                                </select>
                            </div>
                            <div class="setting-row">
                                <label>Размер</label>
                                <select id="setting-font-size">
                                    <option value="14px">Компактный</option>
                                    <option value="16px">Стандарт</option>
                                    <option value="18px">Крупный</option>
                                </select>
                            </div>
                        </div>

                        <div class="setting-group">
                            <h3>Семья</h3>
                            <div id="user-management-list" class="user-list"></div>
                            <div class="add-user-section">
                                <h4>Добавить участника</h4>
                                <div class="setting-row">
                                    <input type="text" id="add-user-name" placeholder="Имя">
                                    <input type="password" id="add-user-passcode" maxlength="6" placeholder="Код (6 цифр)">
                                </div>
                                <button id="add-user-btn" class="btn-secondary">Добавить в систему</button>
                            </div>
                        </div>

                        <div class="setting-group">
                            <h3>Система</h3>
                            <div class="setting-row">
                                <label>Звуки уведомлений</label>
                                <input type="checkbox" id="setting-sounds" checked>
                            </div>
                            <div class="setting-row">
                                <label>Push-уведомления</label>
                                <input type="checkbox" id="setting-push">
                            </div>
                        </div>

                        <button id="save-settings-btn" class="btn-primary">Применить настройки</button>

                        <button id="logout-btn" class="btn-danger">Выйти из аккаунта</button>

                        <div id="install-section" class="install-prompt" style="display:none">
                            <p>Установите приложение для быстрого доступа</p>
                            <button id="install-pwa-btn" class="btn-accent-soft">Установить</button>
                        </div>
                    </div>
                </section>
            </main>
        </div>

        <!-- Task Modal -->
        <div id="task-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <div id="task-detail-content"></div>
            </div>
        </div>

        <!-- New Task Modal -->
        <div id="new-task-modal" class="modal">
            <div class="modal-content">
                <span class="close-modal">&times;</span>
                <h2>Создать задачу</h2>
                <input type="text" id="new-task-title" placeholder="Заголовок задачи">
                <textarea id="new-task-desc" placeholder="Описание задачи"></textarea>
                <button id="confirm-new-task" class="btn-primary">Создать</button>
            </div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
    <script>
        if ('serviceWorker' in navigator) {
            navigator.serviceWorker.register('sw.js');
        }
    </script>
</body>
</html>
