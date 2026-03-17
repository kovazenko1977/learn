<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Моя Семья - Профессиональное управление домом</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
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
                    <i class="fas fa-house-user"></i>
                    <span>МОЯ СЕМЬЯ</span>
                </div>
                <h2>Авторизация</h2>
                <p>Введите ваш шестизначный код доступа</p>
                <div class="passcode-input">
                    <input type="password" id="passcode" maxlength="6" placeholder="000000">
                </div>
                <button id="login-btn">Войти</button>
                <div id="login-error" class="error-msg"></div>
            </div>
        </div>

        <!-- Main App Screen -->
        <div id="main-screen" class="screen">
            <div id="global-preloader" class="preloader">
                <div class="spinner"></div>
            </div>
            <nav class="sidebar">
                <div class="logo-mini">
                    <i class="fas fa-house-user"></i>
                </div>
                <div class="nav-items">
                    <div class="nav-item active" data-view="chat" title="Чат">
                        <i class="fas fa-comments"></i>
                        <span class="nav-label">Чат</span>
                    </div>
                    <div class="nav-item" data-view="tasks" title="Дела">
                        <i class="fas fa-tasks"></i>
                        <span class="nav-label">Дела</span>
                    </div>
                    <div class="nav-item" data-view="achievements" title="Успехи">
                        <i class="fas fa-award"></i>
                        <span class="nav-label">Успехи</span>
                    </div>
                    <div class="nav-item" data-view="shopping" title="Купить">
                        <i class="fas fa-shopping-cart"></i>
                        <span class="nav-label">Купить</span>
                    </div>
                    <div class="nav-item" data-view="settings" title="Настройки">
                        <i class="fas fa-cog"></i>
                        <span class="nav-label">Настройки</span>
                    </div>
                </div>
                <div class="nav-bottom">
                    <div class="nav-item" id="logout-btn" title="Выйти">
                        <i class="fas fa-sign-out-alt"></i>
                        <span class="nav-label">Выход</span>
                    </div>
                </div>
            </nav>

            <main class="content">
                <header class="top-bar">
                    <h1 id="view-title">Чат</h1>
                    <div class="user-info">
                        <span id="current-username"></span>
                        <div class="avatar" id="current-avatar"></div>
                    </div>
                </header>

                <!-- Chat View -->
                <section id="chat-view" class="view active">
                    <div id="chat-messages" class="messages-container"></div>
                    <div class="chat-input-area">
                        <label for="image-upload" class="upload-btn">
                            <i class="fas fa-paperclip"></i>
                            <input type="file" id="image-upload" accept="image/*" hidden>
                        </label>
                        <input type="text" id="chat-input" placeholder="Введите сообщение...">
                        <button id="send-chat-btn"><i class="fas fa-paper-plane"></i></button>
                    </div>
                </section>

                <!-- Tasks View -->
                <section id="tasks-view" class="view">
                    <div class="task-controls">
                        <button id="new-task-btn" class="btn-primary"><i class="fas fa-plus"></i> Новая задача</button>
                    </div>
                    <div id="task-list" class="task-grid"></div>
                </section>

                <!-- Achievements View -->
                <section id="achievements-view" class="view">
                    <div class="achievements-header">
                        <h2>Наши достижения</h2>
                        <p>Накапливайте очки за выполненные дела!</p>
                    </div>
                    <div id="achievements-list" class="achievements-grid">
                        <!-- Will be populated by JS -->
                    </div>
                </section>

                <!-- Shopping View -->
                <section id="shopping-view" class="view">
                    <div class="shopping-controls">
                        <input type="text" id="shopping-input" placeholder="Что нужно купить?">
                        <button id="add-shopping-btn" class="btn-primary"><i class="fas fa-plus"></i></button>
                    </div>
                    <div id="shopping-list" class="shopping-container">
                        <!-- Will be populated by JS -->
                    </div>
                </section>

                <!-- Settings View -->
                <section id="settings-view" class="view">
                    <div class="settings-container">
                        <div class="setting-group">
                            <h3>Мой профиль</h3>
                            <div class="setting-row">
                                <label>Имя в семье</label>
                                <input type="text" id="setting-my-name" placeholder="Как вас зовут?">
                            </div>
                            <button id="save-profile-btn" class="btn-primary" style="margin-top:10px">Обновить профиль</button>
                        </div>
                        <div class="setting-group">
                            <h3>Тема</h3>
                            <div class="setting-row">
                                <label>Световая схема</label>
                                <select id="setting-theme">
                                    <option value="dark">Темная</option>
                                    <option value="light">Светлая</option>
                                    <option value="glass">Стеклянная</option>
                                </select>
                            </div>
                            <div class="setting-row">
                                <label>Акцентный цвет</label>
                                <input type="color" id="setting-accent" value="#0078d4">
                            </div>
                        </div>
                        <div class="setting-group">
                            <h3>Шрифт</h3>
                            <div class="setting-row">
                                <label>Семейство шрифтов</label>
                                <select id="setting-font">
                                    <option value="'Segoe UI', sans-serif">Segoe UI</option>
                                    <option value="'Roboto', sans-serif">Roboto</option>
                                    <option value="'Montserrat', sans-serif">Montserrat</option>
                                    <option value="'Courier New', monospace">Courier New</option>
                                </select>
                            </div>
                            <div class="setting-row">
                                <label>Размер шрифта</label>
                                <select id="setting-font-size">
                                    <option value="12px">Маленький</option>
                                    <option value="14px">Средний</option>
                                    <option value="16px">Крупный</option>
                                    <option value="18px">Очень крупный</option>
                                </select>
                            </div>
                        </div>
                        <div class="setting-group">
                            <h3>Пользователи</h3>
                            <div id="user-management-list" class="user-list">
                                <!-- List of users with roles -->
                            </div>
                            <div class="add-user-form" style="margin-top:20px; padding-top:20px; border-top:1px solid var(--border-color)">
                                <h4>Добавить участника</h4>
                                <div class="setting-row">
                                    <label>Имя</label>
                                    <input type="text" id="add-user-name" placeholder="Имя">
                                </div>
                                <div class="setting-row">
                                    <label>Код (6 цифр)</label>
                                    <input type="password" id="add-user-passcode" maxlength="6" placeholder="000000">
                                </div>
                                <button id="add-user-btn" class="btn-primary" style="margin-top:10px">Добавить в семью</button>
                            </div>
                        </div>
                        <div class="setting-group">
                            <h3>Уведомления</h3>
                            <div class="setting-row">
                                <label>Звуковые уведомления</label>
                                <input type="checkbox" id="setting-sounds" checked>
                            </div>
                            <div class="setting-row">
                                <label>Push-уведомления</label>
                                <input type="checkbox" id="setting-push">
                            </div>
                        </div>
                        <button id="save-settings-btn" class="btn-primary">Сохранить изменения</button>
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
