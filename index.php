<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>HOP | CRM Система управления поддержкой</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700&display=swap" rel="stylesheet">
    <link rel="manifest" href="manifest.json">
    <meta name="theme-color" content="#4f46e5">
    <meta name="apple-mobile-web-app-capable" content="yes">
    <meta name="apple-mobile-web-app-status-bar-style" content="black-translucent">
    <style>
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
        .badge {
            font-weight: 600;
            padding: 0.5em 0.8em;
            border-radius: 0.5rem;
        }

        /* Responsive Mobile Nav */
        .mobile-header {
            display: none;
            background: var(--sidebar-bg);
            color: #fff;
            padding: 0.75rem 1rem;
            position: sticky;
            top: 0;
            z-index: 1050;
        }

        @media (max-width: 767.98px) {
            nav.sidebar {
                display: none;
            }
            .offcanvas.sidebar {
                z-index: 1060 !important;
                background-color: #0f172a !important; /* Deeper, richer dark background */
            }
            .mobile-header {
                display: flex;
                justify-content: space-between;
                align-items: center;
            }
            #app-content {
                padding: 1rem;
            }

            /* Mobile Table to Card Transformation */
            .mobile-card-table thead {
                display: none;
            }
            .mobile-card-table tbody tr {
                display: block;
                margin-bottom: 1rem;
                padding: 1rem;
                background: #fff;
                border-radius: 1rem;
                box-shadow: 0 2px 4px rgba(0,0,0,0.05);
                border: 1px solid #edf2f7;
            }
            .mobile-card-table tbody td {
                display: flex;
                justify-content: space-between;
                align-items: center;
                padding: 0.5rem 0;
                border: none;
                text-align: right;
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
            .btn {
                width: 100%;
                margin-bottom: 0.5rem;
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
            <!-- Mobile Top Header -->
            <header class="mobile-header shadow-sm d-md-none">
                <span class="fw-bold">HOP</span>
                <button class="btn btn-link text-white p-0 border-0 outline-none" type="button" data-bs-toggle="offcanvas" data-bs-target="#mobileSidebar" aria-controls="mobileSidebar">
                    <i class="bi bi-list fs-1"></i>
                </button>
            </header>

            <div class="row g-0">
                <!-- Desktop Sidebar -->
                <nav class="col-md-2 sidebar d-none d-md-block shadow">
                    <div class="sidebar-brand">
                        <i class="bi bi-tools me-2"></i> HOP
                    </div>
                    <div class="d-flex flex-column justify-content-between" style="height: calc(100vh - 100px);">
                        <ul class="nav flex-column" id="main-nav">
                            <li class="nav-item"><a class="nav-link" href="#" data-view="dashboard"><i class="bi bi-grid-1x2"></i> Мои заявки</a></li>
                            <li class="nav-item" id="nav-department"><a class="nav-link" href="#" data-view="department"><i class="bi bi-people"></i> Заявки отдела</a></li>
                            <li class="nav-item" id="nav-create"><a class="nav-link" href="#" data-view="create"><i class="bi bi-plus-circle"></i> Создать заявку</a></li>
                            <li class="nav-item" id="nav-admin"><a class="nav-link" href="#" data-view="admin"><i class="bi bi-shield-lock"></i> Админ</a></li>
                            <li class="nav-item" id="nav-reports"><a class="nav-link" href="#" data-view="reports"><i class="bi bi-bar-chart"></i> Отчеты</a></li>
                            <li class="nav-item"><a class="nav-link" href="#" data-view="help"><i class="bi bi-question-circle"></i> Справка</a></li>
                        </ul>
                        <div class="mt-auto px-3 pb-4">
                            <div id="user-info" class="small mb-3 text-light-50 bg-white bg-opacity-10 p-3 rounded-4"></div>
                            <button id="logout-btn" class="btn btn-outline-light btn-sm w-100 rounded-pill mb-3"><i class="bi bi-box-arrow-right me-1"></i> Выйти</button>
                            <div class="text-center">
                                <a href="https://wes.by" target="_blank" class="text-white-50 text-decoration-none" style="font-size: 0.65rem;">&copy; 2026 Коваженко С.Б.</a>
                            </div>
                        </div>
                    </div>
                </nav>

                <!-- Mobile Sidebar (Offcanvas) -->
                <div class="offcanvas offcanvas-start sidebar p-0 border-0 shadow-lg" tabindex="-1" id="mobileSidebar" style="width: 300px;">
                    <div class="offcanvas-header text-white px-4 py-4 border-bottom border-white border-opacity-10">
                        <h4 class="offcanvas-title fw-bold"><i class="bi bi-tools me-2 text-info"></i> HOP CRM</h4>
                        <button type="button" class="btn-close btn-close-white shadow-none" data-bs-dismiss="offcanvas"></button>
                    </div>
                    <div class="offcanvas-body p-0 pt-3 d-flex flex-column">
                        <ul class="nav flex-column mb-auto" id="mobile-nav">
                            <!-- JS will clone navigation here -->
                        </ul>
                        <div class="p-4 border-top border-white border-opacity-10 mt-auto">
                             <div id="mobile-user-info" class="small mb-3 text-white-50 bg-white bg-opacity-10 p-3 rounded-4"></div>
                             <button onclick="location.reload()" class="btn btn-outline-info btn-sm w-100 rounded-pill"><i class="bi bi-arrow-repeat me-1"></i> Обновить</button>
                        </div>
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
