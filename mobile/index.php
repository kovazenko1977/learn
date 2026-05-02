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
        <div class="header">HOP MOBILE</div>
        <div class="content">
            <form id="login-form">
                <input type="text" id="login" placeholder="ЛОГИН" required autocomplete="off">
                <input type="password" id="password" placeholder="ПАРОЛЬ" required>
                <button type="submit">ВХОД</button>
                <div id="login-error" class="error hidden">ОШИБКА АВТОРИЗАЦИИ</div>
            </form>
        </div>
        <div class="footer">WES.BY</div>
    </div>

    <div id="app-screen" class="hidden">
        <div class="header">
            <span id="view-title">ЗАЯВКИ</span>
            <button id="logout-btn"><i class="bi bi-x-lg"></i></button>
        </div>

        <div id="main-content">
            <!-- Dynamic content here -->
        </div>

        <div class="nav-bar">
            <button class="nav-btn active" data-view="dashboard"><i class="bi bi-list-task"></i></button>
            <button class="nav-btn" data-view="create"><i class="bi bi-plus-lg"></i></button>
            <button class="nav-btn" data-view="department"><i class="bi bi-people"></i></button>
            <button class="nav-btn" data-view="profile"><i class="bi bi-person"></i></button>
        </div>
    </div>

    <!-- Modal for details -->
    <div id="modal-overlay" class="hidden">
        <div class="modal">
            <div class="modal-header">
                <span id="modal-title">ДЕТАЛИ</span>
                <button id="modal-close"><i class="bi bi-x-lg"></i></button>
            </div>
            <div id="modal-body"></div>
        </div>
    </div>

    <script src="app.js"></script>
</body>
</html>
