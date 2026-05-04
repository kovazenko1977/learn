<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>HOP MOBILE</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <script>
        // Emergency Splash Screen Removal
        (function() {
            function hide() {
                var s = document.getElementById('splash-screen');
                if (s) {
                    s.classList.add('hidden');
                    setTimeout(function() { if(s.parentNode) s.parentNode.removeChild(s); }, 500);
                }
            }
            window.addEventListener('error', hide);
            setTimeout(hide, 7000);
        })();
    </script>
    <style>
        /* Loading Screen */
        #splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #6366f1;
            z-index: 9999;
            display: flex;
            flex-direction: column;
            align-items: center;
            justify-content: center;
            transition: opacity 0.5s ease-out, visibility 0.5s;
        }
        #splash-screen.hidden {
            opacity: 0;
            visibility: hidden;
        }
        .splash-logo {
            font-size: 3rem;
            color: white;
            font-weight: 800;
            margin-bottom: 1.5rem;
            animation: pulse 2s infinite;
        }
        .splash-loader {
            width: 150px;
            height: 4px;
            background: rgba(255,255,255,0.2);
            border-radius: 10px;
            overflow: hidden;
            position: relative;
        }
        .splash-loader::after {
            content: '';
            position: absolute;
            top: 0;
            left: 0;
            height: 100%;
            width: 50%;
            background: white;
            border-radius: 10px;
            animation: loading 1.5s infinite ease-in-out;
        }
        @keyframes pulse {
            0% { transform: scale(1); opacity: 1; }
            50% { transform: scale(1.1); opacity: 0.8; }
            100% { transform: scale(1); opacity: 1; }
        }
        @keyframes loading {
            0% { left: -50%; }
            100% { left: 100%; }
        }
    </style>
</head>
<body>
    <!-- Splash Screen -->
    <div id="splash-screen">
        <div class="splash-logo">HOP</div>
        <div class="splash-loader"></div>
        <div class="mt-3 text-white" style="font-size: 0.7rem; font-weight: 800; margin-top: 15px; opacity: 0.7; letter-spacing: 1px;">ЗАГРУЗКА...</div>
    </div>

    <div id="auth-screen">
        <div class="header">HOP</div>
        <div class="content">
            <div style="text-align:center; margin-bottom: 30px;">
                <h2 style="font-weight: 800; color: var(--text-main);">С возвращением!</h2>
                <p style="color: var(--text-muted); font-size: 0.9rem;">Авторизуйтесь для продолжения</p>
            </div>
            <form id="login-form">
                <div class="label">ЛОГИН</div>
                <input type="text" id="login" placeholder="Введите логин" required autocomplete="off">
                <div class="label">ПАРОЛЬ</div>
                <input type="password" id="password" placeholder="••••••••" required>
                <button type="submit">ВОЙТИ В СИСТЕМУ</button>
                <div id="login-error" class="error hidden">Неверные данные для входа</div>
            </form>
        </div>
        <div class="footer">РАЗРАБОТКА: WES.BY</div>
    </div>

    <div id="app-screen" class="hidden">
        <div class="header">
            <span id="view-title" class="org-name-header">ЗАЯВКИ</span>
            <button id="logout-btn"><i class="bi bi-box-arrow-right"></i></button>
        </div>

        <div id="main-content">
            <!-- Dynamic content here -->
        </div>

        <div class="nav-bar">
            <button class="nav-btn active" data-view="dashboard"><i class="bi bi-list-task"></i></button>
            <button class="nav-btn" data-view="tasks" id="nav-tasks-btn"><i class="bi bi-check2-square"></i></button>
            <button class="nav-btn" data-view="create"><i class="bi bi-plus-lg"></i></button>
            <button class="nav-btn" data-view="chat" id="nav-chat-btn"><i class="bi bi-chat-dots"></i></button>
            <button class="nav-btn" data-view="department"><i class="bi bi-people"></i></button>
            <button class="nav-btn" data-view="reports" id="nav-rep-btn"><i class="bi bi-bar-chart"></i></button>
            <button class="nav-btn" data-view="profile"><i class="bi bi-person"></i></button>
        </div>
    </div>

    <!-- Modal for details -->
    <div id="modal-overlay" class="hidden">
        <div class="modal">
            <div class="modal-header">
                <span class="modal-title">ДЕТАЛИ ЗАЯВКИ</span>
                <button id="modal-close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div id="modal-body"></div>
        </div>
    </div>

    <script src="app.js"></script>
</body>
</html>
