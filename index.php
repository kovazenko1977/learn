<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, viewport-fit=cover, maximum-scale=1.0, user-scalable=no">
    <title>Жанна — Семейный Помощник</title>
    <link rel="manifest" href="manifest.json">
    <link rel="stylesheet" href="assets/css/style.css">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
</head>
<body>
    <div id="app">
        <!-- Auth View -->
        <div id="auth-view" class="view active">
            <div class="auth-container">
                <h1>Жанна</h1>
                <div class="tabs">
                    <button id="login-tab" class="active">Вход</button>
                    <button id="register-tab">Регистрация</button>
                </div>
                <div class="form-group">
                    <input type="text" id="username" placeholder="Имя пользователя">
                </div>
                <div class="form-group">
                    <input type="password" id="password" placeholder="Пароль">
                </div>
                <button id="auth-btn">Войти</button>
            </div>
        </div>

        <!-- Main Content View -->
        <div id="main-view" class="view">
            <header>
                <h2 id="view-title">Чаты</h2>
                <button id="logout-btn" class="icon-btn">🚪</button>
            </header>

            <main id="view-container">
                <!-- Content injected by JS -->
            </main>

            <nav>
                <button class="nav-item active" data-view="chats">
                    <span class="icon">💬</span>
                    <span class="label">Чаты</span>
                </button>
                <button class="nav-item" data-view="tasks">
                    <span class="icon">📋</span>
                    <span class="label">Задачи</span>
                </button>
                <button class="nav-item" data-view="achievements">
                    <span class="icon">🏆</span>
                    <span class="label">Награды</span>
                </button>
                <button class="nav-item" data-view="events">
                    <span class="icon">📅</span>
                    <span class="label">События</span>
                </button>
                <button class="nav-item" data-view="shopping">
                    <span class="icon">🛒</span>
                    <span class="label">Покупки</span>
                </button>
                <button class="nav-item" data-view="settings">
                    <span class="icon">⚙️</span>
                    <span class="label">Настройки</span>
                </button>
                <button class="nav-item" data-view="about">
                    <span class="icon">❤️</span>
                    <span class="label">О Жанне</span>
                </button>
                <button class="nav-item hidden" data-view="admin" id="nav-admin">
                    <span class="icon">🛡️</span>
                    <span class="label">Админ</span>
                </button>
            </nav>
        </div>
    </div>

    <!-- Modals -->
    <div id="modal-overlay" class="hidden">
        <div id="modal-content"></div>
    </div>

    <script src="assets/js/pwa.js"></script>
    <script src="assets/js/app.js"></script>
</body>
</html>
