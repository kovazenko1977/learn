<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Личный кабинет клиента</title>
    <style>
        :root {
            --primary: #003366;
            --primary-dark: #002244;
            --bg: #f0f2f5;
            --card-bg: #ffffff;
            --text: #2d3436;
            --text-muted: #636e72;
            --border: #e2e8f0;
            --radius: 12px;
            --shadow: 0 10px 15px -3px rgba(0, 0, 0, 0.1), 0 4px 6px -2px rgba(0, 0, 0, 0.05);
            --header-height: 70px;
        }

        * { box-sizing: border-box; margin: 0; padding: 0; }
        body { font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif; background: var(--bg); color: var(--text); line-height: 1.5; -webkit-font-smoothing: antialiased; }

        .container { max-width: 1200px; margin: 0 auto; padding: 0 20px; }
        .hidden { display: none !important; }

        /* Auth Layout */
        #auth-view { min-height: 100vh; display: flex; align-items: center; justify-content: center; padding: 20px; background: linear-gradient(135deg, #003366 0%, #001a33 100%); }
        .auth-card { background: var(--card-bg); padding: 50px 40px; border-radius: var(--radius); box-shadow: 0 20px 25px -5px rgba(0, 0, 0, 0.2); width: 100%; max-width: 420px; }
        .auth-card h1 { font-size: 26px; margin-bottom: 30px; text-align: center; color: var(--primary); font-weight: 800; }

        /* Dashboard Layout */
        header { background: var(--card-bg); height: var(--header-height); border-bottom: 1px solid var(--border); position: sticky; top: 0; z-index: 100; }
        header .container { display: flex; align-items: center; justify-content: space-between; height: 100%; }
        .logo { font-weight: 700; font-size: 20px; color: var(--primary); text-decoration: none; }
        .nav { display: flex; gap: 20px; }
        .nav-item { text-decoration: none; color: var(--text-muted); font-weight: 500; font-size: 14px; transition: color 0.2s; cursor: pointer; }
        .nav-item:hover, .nav-item.active { color: var(--primary); }

        main { padding: 40px 0; }
        .view-header { margin-bottom: 30px; display: flex; justify-content: space-between; align-items: center; }
        .view-title { font-size: 28px; font-weight: 700; }

        /* Cards and Lists */
        .card { background: var(--card-bg); border-radius: var(--radius); border: 1px solid var(--border); padding: 24px; margin-bottom: 20px; box-shadow: 0 1px 3px rgba(0,0,0,0.05); }
        .data-table { width: 100%; border-collapse: collapse; }
        .data-table th { text-align: left; padding: 12px; border-bottom: 2px solid var(--border); color: var(--text-muted); font-weight: 600; font-size: 13px; text-transform: uppercase; }
        .data-table td { padding: 16px 12px; border-bottom: 1px solid var(--border); font-size: 14px; }
        .data-table tr:hover { background: #fdfdfd; }

        /* Forms */
        .form-group { margin-bottom: 20px; }
        label { display: block; margin-bottom: 8px; font-weight: 500; font-size: 14px; }
        input, select, textarea { width: 100%; padding: 12px; border: 1px solid var(--border); border-radius: 8px; font-size: 14px; outline: none; transition: border-color 0.2s; }
        input:focus { border-color: var(--primary); }

        .btn { padding: 12px 24px; border-radius: 8px; font-weight: 600; cursor: pointer; border: none; font-size: 14px; transition: transform 0.1s, opacity 0.2s; display: inline-flex; align-items: center; justify-content: center; gap: 8px; }
        .btn:active { transform: scale(0.98); }
        .btn-primary { background: var(--primary); color: white; }
        .btn-primary:hover { opacity: 0.9; }
        .btn-outline { background: transparent; border: 1px solid var(--border); color: var(--text); }
        .btn-outline:hover { background: var(--bg); }
        .btn-sm { padding: 6px 12px; font-size: 12px; }

        /* Modals */
        .modal-overlay { position: fixed; top: 0; left: 0; width: 100%; height: 100%; background: rgba(0,0,0,0.5); display: flex; align-items: center; justify-content: center; z-index: 1000; }
        .modal-content { background: var(--card-bg); padding: 30px; border-radius: var(--radius); width: 100%; max-width: 500px; box-shadow: var(--shadow); position: relative; }
        .modal-close { position: absolute; top: 15px; right: 15px; cursor: pointer; font-size: 20px; color: var(--text-muted); }

        /* Badges */
        .badge { padding: 4px 8px; border-radius: 4px; font-size: 11px; font-weight: 700; text-transform: uppercase; }
        .badge-success { background: #e6fffa; color: #319795; }
        .badge-warning { background: #fffaf0; color: #dd6b20; }

        /* Messenger Styles */
        .chat-layout { display: flex; flex-direction: column; height: 600px; }
        .chat-history { flex: 1; overflow-y: auto; padding: 20px; background: #fdfdfd; border: 1px solid var(--border); border-radius: var(--radius) var(--radius) 0 0; display: flex; flex-direction: column; gap: 15px; }
        .chat-bubble { max-width: 80%; padding: 12px 16px; border-radius: 18px; font-size: 14px; position: relative; box-shadow: 0 1px 2px rgba(0,0,0,0.1); }
        .chat-bubble.mine { align-self: flex-end; background: var(--primary); color: white; border-bottom-right-radius: 4px; }
        .chat-bubble.theirs { align-self: flex-start; background: #f0f2f5; color: var(--text); border-bottom-left-radius: 4px; }
        .chat-info { font-size: 10px; margin-top: 5px; opacity: 0.8; display: flex; justify-content: space-between; gap: 10px; }
        .chat-attachments { margin-top: 8px; display: flex; flex-wrap: wrap; gap: 5px; }
        .chat-att-item { background: rgba(255,255,255,0.2); padding: 4px 8px; border-radius: 4px; color: inherit; text-decoration: none; font-size: 12px; display: flex; align-items: center; gap: 5px; }
        .chat-bubble.theirs .chat-att-item { background: rgba(0,0,0,0.05); }
        .chat-input-area { padding: 15px; background: var(--card-bg); border: 1px solid var(--border); border-top: none; border-radius: 0 0 var(--radius) var(--radius); display: flex; gap: 10px; }

        /* Mobile Adjustments */
        @media (max-width: 768px) {
            header .container { padding: 0 15px; }
            .nav { position: fixed; bottom: 0; left: 0; width: 100%; background: var(--card-bg); border-top: 1px solid var(--border); padding: 10px 0; justify-content: space-around; gap: 0; }
            .nav-item { flex-direction: column; align-items: center; font-size: 10px; }
            main { padding-bottom: 80px; }
            .view-title { font-size: 22px; }
            .data-table thead { display: none; }
            .data-table tr { display: block; border: 1px solid var(--border); border-radius: var(--radius); margin-bottom: 15px; padding: 10px; }
            .data-table td { display: flex; justify-content: space-between; border: none; padding: 5px; }
            .data-table td::before { content: attr(data-label); font-weight: 600; color: var(--text-muted); }
        }
    </style>
</head>
<body>
    <div id="auth-view" class="hidden">
        <div class="auth-card">
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
                <div id="2fa-group" class="form-group hidden">
                    <label>Код 2FA</label>
                    <input type="text" id="2fa-code" placeholder="Введите код">
                </div>
                <button type="submit" class="btn btn-primary" style="width: 100%; margin-bottom: 20px; height: 50px;">Войти</button>
            </form>
            <div style="text-align: center; border-top: 1px solid var(--border); padding-top: 20px;">
                <a href="https://alco.by/instal/" class="btn btn-outline" style="width: 100%; text-decoration: none;">На главную alco.by</a>
            </div>
        </div>
    </div>

    <div id="app-view" class="hidden">
        <header>
            <div class="container">
                <a href="#" class="logo" data-view="dashboard">Enterprise Portal</a>
                <nav class="nav">
                    <a class="nav-item active" data-view="dashboard">Дашборд</a>
                    <a class="nav-item" data-view="documents">Документы</a>
                    <a class="nav-item" data-view="messages">Сообщения</a>
                    <a class="nav-item admin-only hidden" data-view="users">Клиенты</a>
                    <a class="nav-item admin-only hidden" data-view="logs">Логи</a>
                    <a class="nav-item" data-view="about">О программе</a>
                    <a class="nav-item" id="logout-btn">Выход</a>
                </nav>
            </div>
        </header>

        <main class="container">
            <div id="view-container"></div>
        </main>
    </div>

    <!-- Modals -->
    <div id="modal-container" class="modal-overlay hidden">
        <div class="modal-content">
            <div class="modal-close" onclick="App.closeModal()">&times;</div>
            <div id="modal-body"></div>
        </div>
    </div>

    <script src="assets/js/app.js"></script>
</body>
</html>
