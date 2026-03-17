<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Symphony - Совместная работа</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body class="theme-dark">
    <div id="app">
        <!-- Login Screen -->
        <div id="login-screen" class="screen active">
            <div class="login-card">
                <div class="logo">
                    <i class="fas fa-layer-group"></i>
                    <span>SYMPHONY</span>
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
            <nav class="sidebar">
                <div class="logo-mini">
                    <i class="fas fa-layer-group"></i>
                </div>
                <div class="nav-items">
                    <div class="nav-item active" data-view="chat" title="Чат">
                        <i class="fas fa-comments"></i>
                    </div>
                    <div class="nav-item" data-view="tasks" title="Задачи">
                        <i class="fas fa-tasks"></i>
                    </div>
                    <div class="nav-item" data-view="settings" title="Настройки">
                        <i class="fas fa-cog"></i>
                    </div>
                </div>
                <div class="nav-bottom">
                    <div class="nav-item" id="logout-btn" title="Выйти">
                        <i class="fas fa-sign-out-alt"></i>
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

                <!-- Settings View -->
                <section id="settings-view" class="view">
                    <div class="settings-container">
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
</body>
</html>
