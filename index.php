<!DOCTYPE html>
<html lang="ru">
<head>
    <script>
        if (/Android|webOS|iPhone|iPad|iPod|BlackBerry|IEMobile|Opera Mini/i.test(navigator.userAgent)) {
            if (!window.location.pathname.includes('/mobile/')) {
                window.location.href = 'mobile/';
            }
        }
    </script>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title id="page-title">HOP | CRM Система управления поддержкой</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <style>
        /* Loading Screen */
        #splash-screen {
            position: fixed;
            top: 0;
            left: 0;
            width: 100%;
            height: 100%;
            background: #4f46e5;
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
            font-size: 4rem;
            color: white;
            font-weight: 800;
            margin-bottom: 2rem;
            animation: pulse 2s infinite;
        }
        .splash-loader {
            width: 200px;
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

        .text-truncate-2 {
            display: -webkit-box;
            -webkit-line-clamp: 2;
            -webkit-box-orient: vertical;
            overflow: hidden;
        }

        :root {
            --primary: #4f46e5;
            --primary-hover: #4338ca;
            --secondary: #64748b;
            --success: #10b981;
            --danger: #ef4444;
            --warning: #f59e0b;
            --info: #06b6d4;
            --bg-body: #f8fafc;
            --bg-card: #ffffff;
            --text-main: #1e293b;
            --text-muted: #64748b;
            --sidebar-bg: #1e293b;
            --sidebar-text: #f8fafc;
            --sidebar-active: #4f46e5;
        }

        body {
            font-family: 'Inter', sans-serif;
            background-color: var(--bg-body);
            color: var(--text-main);
            overflow-x: hidden;
        }

        /* Login Screen */
        #login-screen {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            background: linear-gradient(135deg, #667eea 0%, #764ba2 100%);
            padding: 20px;
        }
        .login-card {
            max-width: 450px;
            width: 100%;
            border-radius: 1.5rem;
            backdrop-filter: blur(10px);
            background: rgba(255, 255, 255, 0.95);
            border: none;
            box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1), 0 10px 10px -5px rgba(0, 0, 0, 0.04);
        }
        .login-btn {
            background-color: var(--primary);
            border: none;
            padding: 0.75rem;
            font-weight: 600;
            border-radius: 0.75rem;
            transition: all 0.2s;
        }
        .login-btn:hover {
            background-color: var(--primary-hover);
            transform: translateY(-1px);
        }

        /* Sidebar */
        .sidebar {
            background-color: var(--sidebar-bg);
            color: var(--sidebar-text);
            min-height: 100vh;
            z-index: 1000;
            transition: all 0.3s;
        }
        .sidebar .nav-link {
            color: #ffffff !important;
            padding: 1.1rem 1.5rem;
            border-radius: 0.75rem;
            margin: 0.4rem 0.75rem;
            font-weight: 600;
            font-size: 1.05rem;
            transition: all 0.2s cubic-bezier(0.4, 0, 0.2, 1);
            display: flex;
            align-items: center;
            gap: 16px;
            cursor: pointer;
            text-decoration: none;
            border: 1px solid transparent;
        }
        .sidebar .nav-link:hover {
            color: #fff !important;
            background: rgba(255,255,255,0.1);
            transform: scale(1.02);
        }
        .sidebar .nav-link.active {
            color: #fff !important;
            background: linear-gradient(135deg, var(--primary) 0%, #6366f1 100%) !important;
            box-shadow: 0 8px 16px -4px rgba(79, 70, 229, 0.5);
            transform: scale(1.02);
            border: 1px solid rgba(255,255,255,0.2);
        }
        .sidebar-brand {
            padding: 2rem 1.5rem;
            font-weight: 700;
            font-size: 1.25rem;
            color: #fff;
            letter-spacing: -0.025em;
        }

        /* Main Content */
        #app-content {
            padding: 2rem;
            transition: all 0.3s ease;
        }
        .card {
            border: none;
            border-radius: 1rem;
            box-shadow: 0 1px 3px 0 rgba(0, 0, 0, 0.1), 0 1px 2px 0 rgba(0, 0, 0, 0.06);
            transition: transform 0.2s ease, box-shadow 0.2s ease;
        }
        .card:hover {
            transform: translateY(-2px);
            box-shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1);
        }

        .btn:active, .nav-link:active {
            opacity: 0.7;
            transform: scale(0.98);
        }
        .badge {
            font-weight: 600;
            padding: 0.5em 0.8em;
            border-radius: 0.5rem;
        }

        /* Mobile Native UI */
        .mobile-bottom-nav {
            display: none;
            position: fixed;
            bottom: 0;
            left: 0;
            right: 0;
            height: 65px;
            background: rgba(255, 255, 255, 0.9);
            backdrop-filter: blur(15px);
            border-top: 1px solid rgba(0, 0, 0, 0.05);
            z-index: 1040;
            justify-content: space-around;
            align-items: center;
            padding-bottom: env(safe-area-inset-bottom);
        }
        .mobile-bottom-nav .nav-link {
            color: var(--text-muted) !important;
            display: flex;
            flex-direction: column;
            align-items: center;
            font-size: 0.7rem;
            font-weight: 500;
            padding: 5px 0;
            gap: 4px;
            width: 20%;
            transition: all 0.2s;
        }
        .mobile-bottom-nav .nav-link i { font-size: 1.25rem; }
        .mobile-bottom-nav .nav-link.active { color: var(--primary) !important; }

        .mobile-fab {
            display: none;
            position: fixed;
            bottom: 80px;
            right: 20px;
            width: 60px;
            height: 60px;
            border-radius: 30px;
            background: var(--primary);
            color: white;
            box-shadow: 0 4px 15px rgba(79, 70, 229, 0.4);
            z-index: 1045;
            justify-content: center;
            align-items: center;
            font-size: 1.75rem;
            transition: all 0.3s;
        }
        .mobile-fab:active { transform: scale(0.9); }

        .mobile-header {
            display: none;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
            color: var(--text-main);
            padding: 0.75rem 1.25rem;
            position: sticky;
            top: 0;
            z-index: 1050;
            border-bottom: 1px solid #f1f5f9;
            height: 60px;
        }

        .rounded-top-5 { border-top-left-radius: 2.5rem !important; border-top-right-radius: 2.5rem !important; }
        .offcanvas-bottom { height: auto !important; max-height: 90vh; }

        @media (max-width: 767.98px) {
            #main-layout { background: #f8fafc; min-height: 100vh; }
            .modal-dialog {
                margin: 0;
                width: 100%;
                max-width: 100% !important;
            }
            .modal-content {
                height: 100vh;
                border-radius: 0 !important;
                display: flex;
                flex-direction: column;
            }
            .modal-body {
                flex-grow: 1;
                overflow-y: auto;
                padding: 1.5rem !important;
            }
            .modal-header {
                padding: 1.25rem 1.5rem !important;
                border-bottom: 1px solid #f1f5f9 !important;
            }

            nav.sidebar { display: none !important; }
            .mobile-header, .mobile-bottom-nav, .mobile-fab { display: flex; }
            #app-content {
                padding: 1.25rem;
                padding-bottom: 100px;
            }

            /* Mobile Native-like List Styles */
            .mobile-card-table thead { display: none; }
            .mobile-card-table tbody tr {
                display: block;
                margin-bottom: 0.75rem;
                padding: 1.25rem;
                background: #fff;
                border-radius: 1.25rem;
                border: 1px solid #f1f5f9;
                box-shadow: 0 1px 2px rgba(0,0,0,0.02);
            }
            .mobile-card-table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.4rem 0;
                border: none;
                font-size: 0.95rem;
            }
            .mobile-card-table tbody td::before {
                content: attr(data-label);
                font-weight: 600;
                color: var(--text-muted);
                text-align: left;
                margin-right: 1rem;
                font-size: 0.8rem;
                text-transform: uppercase;
            }
            .mobile-card-table tbody td:last-child {
                border-bottom: 0;
            }
            .mobile-card-table .ps-4, .mobile-card-table .pe-4 {
                padding-left: 0 !important;
                padding-right: 0 !important;
            }

            /* Form Optimization */
            .btn:not(.btn-sm):not(.rounded-circle) {
                width: 100%;
                margin-bottom: 0.5rem;
                padding: 0.75rem;
            }
            .input-group > .form-control {
                width: 100%;
            }
        }

        /* Custom Scrollbar */
        ::-webkit-scrollbar { width: 8px; }
        ::-webkit-scrollbar-track { background: #f1f1f1; }
        ::-webkit-scrollbar-thumb { background: #cbd5e1; border-radius: 10px; }
        ::-webkit-scrollbar-thumb:hover { background: #94a3b8; }

        .hidden { display: none !important; }

        /* PWA Install Prompt */
        #pwa-install-banner {
            position: fixed;
            bottom: 20px;
            left: 20px;
            right: 20px;
            z-index: 2000;
            background: var(--primary);
            color: white;
            border-radius: 1rem;
            padding: 1rem;
            display: flex;
            justify-content: space-between;
            align-items: center;
            box-shadow: 0 10px 25px rgba(0,0,0,0.2);
            animation: slideUp 0.5s ease-out;
        }
        @keyframes slideUp {
            from { transform: translateY(100px); opacity: 0; }
            to { transform: translateY(0); opacity: 1; }
        }

        .req-link {
            text-decoration: none;
            font-weight: 600;
            color: var(--primary);
        }
        .req-link:hover { text-decoration: underline; }

        /* Animation */
        .fade-in { animation: fadeIn 0.4s cubic-bezier(0.4, 0, 0.2, 1); }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(10px); }
            to { opacity: 1; transform: translateY(0); }
        }

        .slide-in { animation: slideIn 0.3s ease-out; }
        @keyframes slideIn {
            from { transform: translateX(-20px); opacity: 0; }
            to { transform: translateX(0); opacity: 1; }
        }

        .btn {
            transition: all 0.2s ease !important;
        }
        .btn:active {
            transform: scale(0.95);
        }
    </style>
</head>
<body>
    <!-- Splash Screen -->
    <div id="splash-screen">
        <div class="splash-logo">HOP</div>
        <div class="splash-loader"></div>
        <div class="mt-3 text-white-50 small fw-bold">ЗАГРУЗКА СИСТЕМЫ...</div>
    </div>

    <div id="app">
        <!-- Login Screen -->
        <div id="login-screen" class="hidden">
            <div class="card login-card p-4">
                <div class="card-body">
                    <div class="text-center mb-4">
                        <div class="bg-primary text-white d-inline-block p-3 rounded-circle mb-3 shadow">
                            <i class="bi bi-tools fs-3"></i>
                        </div>
                        <h2 class="fw-bold text-dark">HOP</h2>
                        <p class="text-muted">Войдите в систему управления</p>
                    </div>
                    <form id="login-form">
                        <div class="mb-3">
                            <label class="form-label fw-medium small text-uppercase">Логин</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-person text-muted"></i></span>
                                <input type="text" id="login-username" name="login" class="form-control border-start-0 ps-0" placeholder="Username" autocomplete="username" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label class="form-label fw-medium small text-uppercase">Пароль</label>
                            <div class="input-group">
                                <span class="input-group-text bg-white border-end-0"><i class="bi bi-lock text-muted"></i></span>
                                <input type="password" id="login-password" name="password" class="form-control border-start-0 ps-0" placeholder="••••••••" autocomplete="current-password" required>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-primary w-100 login-btn shadow-sm mb-3">Войти в CRM</button>
                    </form>
                    <div id="login-error" class="alert alert-danger mb-0 hidden"></div>
                    <div class="mt-4 text-center">
                            <div class="text-muted small mb-2">Разработчик: Коваженко С.Б. <a href="https://wes.by" target="_blank" class="text-decoration-none">wes.by</a></div>
                    </div>
                </div>
            </div>
        </div>

        <!-- PWA Install Banner -->
        <div id="pwa-install-banner" class="hidden">
            <div class="d-flex align-items-center gap-3">
                <i class="bi bi-download fs-4"></i>
                <div>
                    <div class="fw-bold">Установить HOP?</div>
                    <div class="small opacity-75">Добавьте на главный экран для быстрого доступа</div>
                </div>
            </div>
            <div class="d-flex gap-2">
                <button id="pwa-close" class="btn btn-sm btn-link text-white text-decoration-none">Позже</button>
                <button id="pwa-install-btn" class="btn btn-sm btn-light fw-bold rounded-pill px-3">Установить</button>
            </div>
        </div>

        <!-- Main Application Layout -->
        <div id="main-layout" class="container-fluid hidden p-0">
            <!-- Mobile Bottom Navigation -->
            <nav class="mobile-bottom-nav d-md-none">
                <a href="#" class="nav-link active" data-view="dashboard"><i class="bi bi-house-door"></i><span>Главная</span></a>
                <a href="#" class="nav-link" data-view="tasks" id="mob-nav-tasks"><i class="bi bi-check2-square"></i><span>Задачи</span></a>
                <a href="#" class="nav-link" data-view="chat" id="mob-nav-chat"><i class="bi bi-chat-dots"></i><span>Чат</span></a>
                <a href="#" class="nav-link" data-view="department" id="mob-nav-dept"><i class="bi bi-list-check"></i><span>Заявки</span></a>
                <a href="#" class="nav-link" data-view="reports" id="mob-nav-rep"><i class="bi bi-bar-chart"></i><span>Отчеты</span></a>
                <a href="#" class="nav-link" data-view="admin" id="mob-nav-admin"><i class="bi bi-gear"></i><span>Админ</span></a>
                <a href="#" class="nav-link" data-view="help"><i class="bi bi-info-circle"></i><span>Инфо</span></a>
            </nav>

            <!-- Floating Action Button -->
            <a href="#" class="mobile-fab d-md-none" data-view="create" id="mob-fab-create">
                <i class="bi bi-plus-lg"></i>
            </a>

            <!-- Mobile Top Header -->
            <header class="mobile-header d-md-none align-items-center">
                <div id="mobile-back-btn" class="me-auto" style="display: none; cursor: pointer;"><i class="bi bi-chevron-left fs-4 text-primary"></i></div>
                <div id="mobile-title" class="flex-grow-1 fs-5 fw-bold text-center">HOP</div>
                <div id="mobile-profile-trigger" class="ms-auto bg-light text-primary rounded-circle d-flex justify-content-center align-items-center shadow-sm" style="width: 35px; height: 35px; cursor: pointer;">
                    <i class="bi bi-person-fill"></i>
                </div>
            </header>

            <div class="row g-0">
                <!-- Desktop Sidebar -->
                <nav class="col-md-2 sidebar d-none d-md-block shadow">
                    <div class="sidebar-brand" id="sidebar-org-name">
                        <i class="bi bi-tools me-2"></i> HOP
                    </div>
                    <div class="d-flex flex-column justify-content-between" style="height: calc(100vh - 100px);">
                        <ul class="nav flex-column" id="main-nav">
                            <li class="nav-item"><a class="nav-link" href="#" data-view="dashboard"><i class="bi bi-grid-1x2"></i> Мои заявки</a></li>
                            <li class="nav-item" id="nav-tasks"><a class="nav-link" href="#" data-view="tasks"><i class="bi bi-list-check"></i> Мои задачи</a></li>
                            <li class="nav-item"><a class="nav-link" href="#" data-view="chat" id="nav-chat"><i class="bi bi-chat-dots"></i> Общий чат</a></li>
                            <li class="nav-item" id="nav-department"><a class="nav-link" href="#" data-view="department"><i class="bi bi-people"></i> Заявки отдела</a></li>
                            <li class="nav-item" id="nav-create"><a class="nav-link" href="#" data-view="create"><i class="bi bi-plus-circle"></i> Создать заявку</a></li>
                            <li class="nav-item" id="nav-admin"><a class="nav-link" href="#" data-view="admin"><i class="bi bi-shield-lock"></i> Админ</a></li>
                            <li class="nav-item" id="nav-reports"><a class="nav-link" href="#" data-view="reports"><i class="bi bi-bar-chart"></i> Отчеты</a></li>
                            <li class="nav-item"><a class="nav-link" href="#" data-view="help"><i class="bi bi-question-circle"></i> Справка</a></li>
                        </ul>
                        <div class="mt-auto px-3 pb-4">
                            <div id="user-info" class="small mb-3 text-white bg-white bg-opacity-10 p-3 rounded-4"></div>
                            <button id="logout-btn" class="btn btn-danger btn-sm w-100 rounded-pill mb-3 shadow-sm"><i class="bi bi-box-arrow-right me-1"></i> Выйти</button>
                            <div class="text-center">
                                <a href="https://wes.by" target="_blank" class="text-white-50 text-decoration-none" style="font-size: 0.65rem;">&copy; 2026 Коваженко С.Б.</a>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Mobile Profile (Offcanvas Bottom Sheet style) -->
                <div class="offcanvas offcanvas-bottom rounded-top-5" tabindex="-1" id="mobileProfile" style="height: 400px; border-top: none;">
                    <div class="offcanvas-body p-4">
                        <div class="text-center mb-4">
                            <div class="bg-primary text-white rounded-circle d-inline-flex justify-content-center align-items-center mb-2" style="width: 70px; height: 70px; font-size: 2rem;">
                                <i class="bi bi-person"></i>
                            </div>
                            <div id="mobile-profile-name" class="h5 fw-bold mb-0"></div>
                            <div id="mobile-profile-role" class="text-muted small"></div>
                        </div>
                        <div class="list-group list-group-flush mb-4">
                            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 border-0 rounded-3 mb-2" onclick="location.reload()">
                                <i class="bi bi-arrow-repeat fs-5 text-info"></i>
                                <span>Обновить данные</span>
                            </a>
                            <a href="#" class="list-group-item list-group-item-action d-flex align-items-center gap-3 py-3 border-0 rounded-3 mb-2" onclick="el.logoutBtn.click()">
                                <i class="bi bi-box-arrow-right fs-5 text-danger"></i>
                                <span>Выйти из аккаунта</span>
                            </a>
                        </div>
                        <button type="button" class="btn btn-light w-100 py-3 rounded-pill fw-bold" data-bs-dismiss="offcanvas">Закрыть</button>
                    </div>
                </div>

                <!-- Main Content Area -->
                <main class="col-md-10 ms-sm-auto">
                    <div id="announcement-banner" class="hidden alert alert-warning rounded-0 border-0 border-bottom mb-0 py-2 px-4 small text-center fw-bold shadow-sm"></div>
                    <div id="app-content" class="fade-in">
                        <!-- Views will be rendered here -->
                    </div>
                </main>
            </div>
        </div>
    </div>

    <!-- Modals -->
    <div class="modal fade" id="requestModal" tabindex="-1">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow-lg" style="border-radius: 1.5rem;">
                <div class="modal-header border-0 pb-0">
                    <h5 class="modal-title fw-bold">Детали заявки</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                </div>
                <div class="modal-body p-4" id="modal-content"></div>
            </div>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script src="js/app.js?v=<?php echo time(); ?>"></script>
</body>
</html>
