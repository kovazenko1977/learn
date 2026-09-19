<!DOCTYPE html>
<html lang="ru" data-theme="light">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>CRM Service Fix — Заявки на обслуживание и ремонт</title>
    <link rel="stylesheet" href="assets/css/style.css">
    <!-- FontAwesome for clean icons -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
    <div id="app" class="app-layout">
        <!-- Sidebar Navigation -->
        <aside id="sidebar" class="sidebar">
            <div class="sidebar-header">
                <i class="fa-solid fa-screwdriver-wrench logo-icon"></i>
                <div class="logo-text">
                    <span class="app-title">CRM Service</span>
                    <span class="app-sub">Ремонт & Обслуживание</span>
                </div>
            </div>

            <nav class="sidebar-nav">
                <a href="#dashboard" class="nav-link active" data-page="dashboard">
                    <i class="fa-solid fa-chart-pie"></i>
                    <span data-i18n="nav_dashboard">Дашборд</span>
                </a>
                <a href="#tickets" class="nav-link" data-page="tickets">
                    <i class="fa-solid fa-list-check"></i>
                    <span data-i18n="nav_tickets">Заявки</span>
                    <span class="badge badge-primary" id="sidebarTicketCount">0</span>
                </a>
                <a href="#kanban" class="nav-link" data-page="kanban">
                    <i class="fa-solid fa-table-columns"></i>
                    <span data-i18n="nav_kanban">Канбан Борд</span>
                </a>
                <a href="#analytics" class="nav-link" data-page="analytics">
                    <i class="fa-solid fa-chart-line"></i>
                    <span data-i18n="nav_analytics">Аналитика и Отчёты</span>
                </a>
                <a href="#form-builder" class="nav-link admin-only" data-page="form-builder">
                    <i class="fa-solid fa-cubes"></i>
                    <span data-i18n="nav_form_builder">Конструктор Форм</span>
                </a>
                <a href="#users" class="nav-link admin-manager-only" data-page="users">
                    <i class="fa-solid fa-users-gears"></i>
                    <span data-i18n="nav_users">Сотрудники</span>
                </a>
                <a href="#settings" class="nav-link admin-only" data-page="settings">
                    <i class="fa-solid fa-sliders"></i>
                    <span data-i18n="nav_settings">Настройки Настройки</span>
                </a>
            </nav>

            <div class="sidebar-footer">
                <div class="user-chip" id="sidebarUserChip">
                    <div class="avatar" id="userAvatar">A</div>
                    <div class="user-info">
                        <span class="user-name" id="userName">Администратор</span>
                        <span class="user-role badge-role" id="userRole">Admin</span>
                    </div>
                </div>
            </div>
        </aside>

        <!-- Main Wrapper -->
        <main class="main-content">
            <!-- Header Topbar -->
            <header class="topbar">
                <button id="sidebarToggleBtn" class="btn-icon" title="Переключить меню">
                    <i class="fa-solid fa-bars"></i>
                </button>

                <div class="topbar-search">
                    <i class="fa-solid fa-magnifying-glass search-icon"></i>
                    <input type="text" id="globalSearchInput" placeholder="Поиск заявки (номер, название...)" data-i18n-ph="ph_search">
                </div>

                <div class="topbar-actions">
                    <button class="btn btn-primary" id="quickNewTicketBtn">
                        <i class="fa-solid fa-plus"></i> <span data-i18n="btn_create_ticket">Новая заявка</span>
                    </button>

                    <!-- Language Selector -->
                    <select id="langSelect" class="form-select select-sm">
                        <option value="ru">RU 🇷🇺</option>
                        <option value="en">EN 🇬🇧</option>
                    </select>

                    <!-- Theme Toggle -->
                    <button id="themeToggleBtn" class="btn-icon" title="Переключить тему">
                        <i class="fa-solid fa-moon"></i>
                    </button>

                    <!-- User Profile Dropdown -->
                    <div class="dropdown">
                        <button class="btn-icon" id="profileMenuBtn">
                            <i class="fa-solid fa-user-gear"></i>
                        </button>
                        <div class="dropdown-menu" id="profileDropdown">
                            <a href="#profile" class="dropdown-item" data-page="profile">
                                <i class="fa-solid fa-id-card"></i> <span data-i18n="menu_profile">Мой Профиль</span>
                            </a>
                            <a href="#notifications" class="dropdown-item" data-page="notifications">
                                <i class="fa-solid fa-bell"></i> <span data-i18n="menu_notif">Уведомления</span>
                            </a>
                            <hr class="dropdown-divider">
                            <button class="dropdown-item text-danger" id="logoutBtn">
                                <i class="fa-solid fa-right-from-bracket"></i> <span data-i18n="menu_logout">Выход</span>
                            </button>
                        </div>
                    </div>
                </div>
            </header>

            <!-- Dynamic Page Views Container -->
            <div id="pageContainer" class="page-container">
                <!-- Views loaded dynamically by app.js -->
            </div>
        </main>
    </div>

    <!-- Modals Container -->
    <div id="modalOverlay" class="modal-overlay hidden">
        <div id="modalContent" class="modal-box">
            <!-- Dynamic Modal Content -->
        </div>
    </div>

    <!-- Toast Notification Container -->
    <div id="toastContainer" class="toast-container"></div>

    <script src="assets/js/app.js"></script>
</body>
</html>
