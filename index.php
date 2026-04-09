<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0, maximum-scale=1.0, user-scalable=no, viewport-fit=cover">
    <meta name="theme-color" content="#7360f2">
    <link rel="manifest" href="manifest.json">
    <link rel="apple-touch-icon" href="https://alco.by/wp-content/uploads/2021/04/cropped-logo-192x192.png">
    <title>Личный кабинет</title>
    <style>
        :root {
            --primary: #7360f2;
            --primary-dark: #5b4bc4;
            --bg: #f4f4f9;
            --card-bg: #ffffff;
            --text: #2d3436;
            --text-muted: #6e6e80;
            --border: #e6e6f2;
            --radius: 16px;
            --shadow: 0 4px 20px rgba(115, 96, 242, 0.08);
            --header-height: 70px;
            --transition: all 0.25s cubic-bezier(0.4, 0, 0.2, 1);
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: var(--bg); color: var(--text); line-height: 1.5; -webkit-font-smoothing: antialiased; }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .hidden { display: none !important; }

        @keyframes fadeIn { from { opacity: 0; transform: translateY(10px); } to { opacity: 1; transform: translateY(0); } }
        @keyframes slideInRight { from { opacity: 0; transform: translateX(20px); } to { opacity: 1; transform: translateX(0); } }
        @keyframes scaleIn { from { transform: scale(0.95); opacity: 0; } to { transform: scale(1); opacity: 1; } }

        .animate-fade { animation: fadeIn 0.4s ease-out forwards; }
        .animate-scale { animation: scaleIn 0.3s ease-out forwards; }

        /* Auth Layout */
        #auth-view { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; background: linear-gradient(135deg, #7360f2 0%, #5b4bc4 100%); }
        .auth-card { background: var(--card-bg); padding: 50px 40px; border-radius: 24px; box-shadow: 0 20px 40px rgba(0, 0, 0, 0.15); width: 100%; max-width: 420px; animation: scaleIn 0.5s ease-out; }
        .auth-card h1 { font-size: 26px; margin-bottom: 30px; text-align: center; color: var(--primary); font-weight: 800; }

        /* Dashboard Layout */
        header { background: rgba(255, 255, 255, 0.8); backdrop-filter: blur(10px); height: var(--header-height); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; box-shadow: 0 2px 10px rgba(0,0,0,0.02); }
        header .container { display: flex; align-items: center; justify-content: space-between; height: 100%; }
        .logo { font-weight: 800; font-size: 22px; color: var(--primary); text-decoration: none; letter-spacing: -0.5px; }
        .nav { display: flex; gap: 20px; z-index: 1000; }
        .nav-item { text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 14px; transition: color 0.2s; cursor: pointer; position: relative; }
        .nav-item:hover, .nav-item.active { color: var(--primary); }
        .badge-nav { position: absolute; top: -5px; right: -12px; background: #e74c3c; color: white; font-size: 9px; font-weight: 800; padding: 2px 5px; border-radius: 10px; min-width: 14px; text-align: center; border: 2px solid white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); animation: pulse 2s infinite; }
        @keyframes pulse { 0% { transform: scale(1); } 50% { transform: scale(1.1); box-shadow: 0 0 10px rgba(231, 76, 60, 0.5); } 100% { transform: scale(1); } }

        main { padding: 40px 0; }
        .view-header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .view-title { font-size: 28px; font-weight: 700; }

        /* Cards and Lists */
        .card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid rgba(226, 232, 240, 0.6); padding: 24px; margin-bottom: 20px; box-shadow: 0 4px 6px -1px rgba(0, 0, 0, 0.05); transition: var(--transition); }
        .card:hover { box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.1); transform: translateY(-4px); }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 12px; border-bottom: 2px solid var(--border); color: var(--text-muted); font-weight: 600; font-size: 13px; text-transform: uppercase; }
        .data-table td { padding: 16px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
        .data-table tr:hover { background: #fdfdfd; }

        /* Forms */
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s; }
        input:focus { border-color: var(--primary); box-shadow: 0 0 0 3px rgba(0, 51, 102, 0.1); }

        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 14px; transition: var(--transition); display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn:active { transform: scale(0.96); }
        .btn-primary { background: var(--primary); color: white; box-shadow: 0 2px 4px rgba(0,0,0,0.1); }
        .btn-primary:hover { opacity: 0.9; box-shadow: 0 4px 12px rgba(0, 51, 102, 0.3); transform: translateY(-1px); }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { background: var(--bg); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }

        /* Modals */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.4); backdrop-filter: blur(4px); display: flex; align-items: center; justify-content: center; z-index: 1000; opacity: 0; pointer-events: none; transition: var(--transition); }
        .modal-overlay.active { opacity: 1; pointer-events: auto; }
        .modal-content { background: var(--card-bg); padding: 30px; border-radius: var(--radius); width: 100%; max-width: 500px; box-shadow: var(--shadow); position: relative; transform: scale(0.9); transition: var(--transition); }
        .modal-overlay.active .modal-content { transform: scale(1); }
        .modal-close { position: absolute; top: 15px; right: 15px; cursor: pointer; font-size: 20px; color: var(--text-muted); }

        /* Badges */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-success { background: #e6fffa; color: #319795; }
        .badge-warning { background: #fffaf0; color: #dd6b20; }
        .badge-error { background: #fff5f5; color: #e53e3e; }
        .badge-primary { background: #eff2fe; color: var(--primary); }

        /* Glassmorphism & Modern UI */
        .glass { background: rgba(255, 255, 255, 0.7) !important; backdrop-filter: blur(12px); border: 1px solid rgba(255, 255, 255, 0.3) !important; box-shadow: 0 8px 32px 0 rgba(31, 38, 135, 0.07) !important; }
        .summary-bar { position: fixed; bottom: 0; left: 0; width: 100%; z-index: 500; border-top: 1px solid var(--border); transition: transform 0.3s ease; transform: translateY(0); }
        .summary-bar.hidden { transform: translateY(100%); display: block !important; }

        .price-row { transition: background 0.2s; }
        .price-row:hover { background: rgba(115, 96, 242, 0.02) !important; }
        .order-qty:focus { border-color: var(--primary) !important; box-shadow: 0 0 0 3px rgba(115, 96, 242, 0.1); }

        @media (max-width: 768px) {
            .summary-bar { bottom: var(--nav-height, 60px); }
            .summary-bar .container { flex-direction: column; gap: 10px; align-items: stretch !important; padding: 10px 15px !important; }
            .summary-bar button { width: 100%; }
        }

        /* Messenger Styles */
        .chat-layout { display: flex; flex-direction: column; height: 650px; background: #f4f4f9; border-radius: var(--radius); overflow: hidden; border: 1px solid var(--border); }
        .chat-history { flex: 1; overflow-y: auto; padding: 20px; display: flex; flex-direction: column; gap: 8px; scroll-behavior: smooth; }
        .chat-bubble { max-width: 75%; padding: 10px 14px; font-size: 15px; position: relative; line-height: 1.4; transition: var(--transition); }
        .chat-bubble.mine { align-self: flex-end; background: #eff2fe; color: var(--text); border-radius: 18px 18px 4px 18px; border: 1px solid #d1d9ff; }
        .chat-bubble.theirs { align-self: flex-start; background: #ffffff; color: var(--text); border-radius: 18px 18px 18px 4px; box-shadow: 0 2px 5px rgba(0,0,0,0.05); }
        .chat-info { font-size: 11px; margin-top: 4px; color: var(--text-muted); display: flex; justify-content: flex-end; align-items: center; gap: 4px; }
        .chat-bubble.mine .chat-info { color: #8e99c5; }

        .chat-list { border-right: 1px solid var(--border); overflow-y: auto; background: white; }
        .chat-list-item { padding: 15px; border-bottom: 1px solid var(--border); cursor: pointer; display: flex; align-items: center; gap: 12px; transition: var(--transition); }
        .chat-list-item:hover { background: #f8f8ff; }
        .chat-list-item.active { background: #eff2fe; border-left: 4px solid var(--primary); }
        .chat-avatar { width: 48px; height: 48px; border-radius: 50%; background: var(--primary); color: white; display: flex; align-items: center; justify-content: center; font-weight: 600; font-size: 18px; flex-shrink: 0; }
        .chat-item-content { flex: 1; min-width: 0; }
        .chat-item-header { display: flex; justify-content: space-between; margin-bottom: 4px; }
        .chat-item-name { font-weight: 600; font-size: 15px; color: var(--text); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-item-time { font-size: 12px; color: var(--text-muted); }
        .chat-item-preview { font-size: 13px; color: var(--text-muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis; }
        .chat-item-badge { background: var(--primary); color: white; font-size: 11px; padding: 2px 6px; border-radius: 10px; font-weight: 600; }

        .chat-attachments { margin-top: 8px; display: flex; flex-wrap: wrap; gap: 5px; }
        .chat-att-item { background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 4px; color: inherit; text-decoration: none; font-size: 12px; display: flex; align-items: center; gap: 5px; }
        .chat-bubble.theirs .chat-att-item { background: rgba(0,0,0,0.05); }
        .chat-input-area { padding: 15px; background: var(--card-bg); border: 1px solid var(--border); border-top: none; border-radius: 0 0 var(--radius) var(--radius); display: flex; gap: 10px; }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            :root { --header-height: 60px; }
            header { backdrop-filter: none; } /* Remove filter to fix fixed positioning of child .nav */
            header .container { padding: 0 15px; }
            .nav { position: fixed; bottom: 0; left: 0; width: 100%; background: #ffffff; border-top: 1px solid var(--border); padding: 6px 0; justify-content: space-around; gap: 0; padding-bottom: calc(6px + env(safe-area-inset-bottom)); box-shadow: 0 -5px 15px rgba(0,0,0,0.03); z-index: 1000; display: flex; }
            .nav-item { flex-direction: column; align-items: center; font-size: 11px; flex: 1; padding: 8px 0; color: #6e6e80; text-align: center; display: flex; transition: color 0.2s; }
            .nav-item.active { color: var(--primary); }
            .nav-item span { font-size: 20px; margin-bottom: 2px; }
            main { padding: 20px 0 100px 0; }
            .view-title { font-size: 20px; }
            .data-table thead { display: none; }
            .data-table tr { display: block; background: var(--card-bg); border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 15px; padding: 15px; box-shadow: 0 2px 4px rgba(0,0,0,0.02); }
            .data-table td { display: flex; justify-content: space-between; align-items: center; border: none; padding: 8px 0; font-size: 13px; }
            .data-table td:not(:last-child) { border-bottom: 1px solid #f8fafc; }
            .data-table td::before { content: attr(data-label); font-weight: 600; color: var(--text-muted); font-size: 12px; }

            .card { padding: 20px; }
            .btn { width: 100%; }
            .btn-sm { width: auto; }

            .chat-layout { height: calc(100dvh - 200px); grid-template-columns: 1fr !important; margin-bottom: 20px; }
            .chat-bubble { max-width: 90%; }
            .chat-list.mobile-hidden { display: none; }
            .chat-window.mobile-hidden { display: none; }
            .chat-input-area { padding-bottom: calc(15px + env(safe-area-inset-bottom)); }
        }
    </style>
</head>
<body>
    <div id="auth-view" class="hidden">
        <div class="auth-card" id="login-card">
            <h1>Личный кабинет</h1>
            <form id="login-form">
                <div class="form-group">
                    <label>Логин</label>
                    <input type="text" id="username" required placeholder="Введите логин">
                </div>
                <div class="form-group">
                    <label>Пароль</label>
                    <input type="password" id="password" required placeholder="Введите пароль">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 15px; height: 50px;">Войти</button>
            </form>
            <button class="btn btn-outline" style="width: 100%; margin-bottom: 20px;" onclick="App.showRegistration()">Регистрация нового клиента</button>
            <div style="text-align: center; border-top: 1px solid var(--border); padding-top: 20px;">
                <a href="https://alco.by/instal/" class="btn btn-outline" style="width: 100%; text-decoration: none; border: none; color: var(--text-muted);">На главную alco.by</a>
            </div>
        </div>

        <div class="auth-card hidden" id="register-card">
            <h1>Регистрация</h1>
            <form id="register-form">
                <div class="form-group">
                    <label>Логин (желаемый) *</label>
                    <input type="text" id="reg-username" required placeholder="Например: ivan_ivanov">
                </div>
                <div class="form-group">
                    <label>Пароль *</label>
                    <input type="password" id="reg-password" required>
                </div>
                <div class="form-group">
                    <label>Название компании *</label>
                    <input type="text" id="reg-company" required placeholder="ООО 'Пример'">
                </div>
                <div class="form-group">
                    <label>ИНН / УНП *</label>
                    <input type="text" id="reg-taxid" required>
                </div>
                <div class="form-group">
                    <label>Контактный Email *</label>
                    <input type="email" id="reg-email" required>
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 15px; height: 50px;">Отправить заявку</button>
            </form>
            <button class="btn btn-outline" style="width: 100%;" onclick="App.showLogin()">Назад к входу</button>
        </div>
    </div>

    <div id="app-view" class="hidden" style="opacity: 0; transition: opacity 0.5s ease-in-out;">
        <header>
            <div class="container">
                <a href="#" class="logo" data-view="dashboard">Личный кабинет</a>
                <nav class="nav">
                    <a class="nav-item active" data-view="dashboard"><span>🏠</span>Дашборд</a>
                    <a class="nav-item" data-view="documents"><span>📁</span>Файлы</a>
                    <a class="nav-item" data-view="messages"><span>💬</span>Чаты</a>
                    <a class="nav-item" data-view="pricelist"><span>🛒</span>Прайс</a>
                    <a class="nav-item" data-view="orders"><span>📦</span>Заказы</a>
                    <a class="nav-item admin-only hidden" data-view="users"><span>👥</span>Клиенты</a>
                    <a class="nav-item admin-only hidden" data-view="logs"><span>📋</span>Логи</a>
                    <a class="nav-item admin-only hidden" data-view="maintenance"><span>⚙️</span>Сервис</a>
                    <a class="nav-item" data-view="profile"><span>👤</span>Профиль</a>
                    <a class="nav-item" data-view="about"><span>ℹ️</span>Инфо</a>
                    <a class="nav-item" id="logout-btn"><span>🚪</span>Выход</a>
                </nav>
            </div>
        </header>

        <main class="container">
            <div id="view-container"></div>
        </main>
    </div>

    <!-- Modals -->
    <div id="install-prompt" class="card hidden animate-fade" style="position: fixed; bottom: 20px; left: 20px; right: 20px; z-index: 2000; display: flex; align-items: center; justify-content: space-between; gap: 15px; border: 2px solid var(--primary);">
        <div style="display: flex; align-items: center; gap: 10px;">
            <img src="https://alco.by/favicon.ico" width="32" height="32">
            <div>
                <strong style="display: block; font-size: 14px;">Установить приложение</strong>
                <small style="font-size: 11px; color: var(--text-muted);">Для быстрого доступа к порталу</small>
            </div>
        </div>
        <div style="display: flex; gap: 10px;">
            <button class="btn btn-outline btn-sm" onclick="this.parentElement.parentElement.classList.add('hidden')">Позже</button>
            <button class="btn btn-primary btn-sm" id="install-button">Установить</button>
        </div>
    </div>

    <div id="modal-container" class="modal-overlay hidden">
        <div class="modal-content">
            <div class="modal-close" onclick="App.closeModal()">&times;</div>
            <div id="modal-body"></div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
