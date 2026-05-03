<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no">
    <title>HOP MOBILE</title>
    <link rel="stylesheet" href="style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
</head>
<body>
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
