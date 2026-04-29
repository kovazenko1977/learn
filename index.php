<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM ХОП (PHP)</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        .login-container { max-width: 400px; margin-top: 100px; }
        .sidebar { min-height: 100vh; background: #f8f9fa; }
        .nav-link.active { background: #e9ecef; }
        #app-content { padding: 20px; }
        .hidden { display: none !important; }
    </style>
</head>
<body>
    <div id="app">
        <!-- Login Screen -->
        <div id="login-screen" class="container login-container hidden">
            <div class="card shadow">
                <div class="card-body">
                    <h3 class="card-title text-center mb-4">🔧 CRM ХОП</h3>
                    <form id="login-form">
                        <div class="mb-3">
                            <label class="form-label">Логин</label>
                            <input type="text" id="login-username" class="form-control" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Пароль</label>
                            <input type="password" id="login-password" class="form-control" required>
                        </div>
                        <button type="submit" class="btn btn-primary w-100">Войти</button>
                    </form>
                    <div id="login-error" class="alert alert-danger mt-3 hidden"></div>
                </div>
            </div>
        </div>

        <!-- Main Application Layout -->
        <div id="main-layout" class="container-fluid hidden">
            <div class="row">
                <nav class="col-md-2 d-none d-md-block sidebar shadow-sm">
                    <div class="position-sticky pt-3">
                        <h5 class="px-3 mb-4">CRM ХОП (PHP)</h5>
                        <ul class="nav flex-column" id="main-nav">
                            <li class="nav-item"><a class="nav-link" href="#" data-view="dashboard">Мои заявки</a></li>
                            <li class="nav-item" id="nav-department"><a class="nav-link" href="#" data-view="department">Заявки отдела</a></li>
                            <li class="nav-item" id="nav-create"><a class="nav-link" href="#" data-view="create">Создать заявку</a></li>
                            <li class="nav-item" id="nav-admin"><a class="nav-link" href="#" data-view="admin">Администрирование</a></li>
                            <li class="nav-item" id="nav-reports"><a class="nav-link" href="#" data-view="reports">Отчеты</a></li>
                        </ul>
                        <hr>
                        <div class="px-3">
                            <div id="user-info" class="small mb-2"></div>
                            <button id="logout-btn" class="btn btn-sm btn-outline-danger w-100">Выйти</button>
                        </div>
                    </div>
                </nav>

                <main class="col-md-10 ms-sm-auto px-md-4">
                    <div id="app-content">
                        <!-- Views will be rendered here -->
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="requestModal" tabindex="-1">
        <div class="modal-dialog modal-lg">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title">Детали заявки</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body" id="modal-content"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js"></script>
</body>
</html>
