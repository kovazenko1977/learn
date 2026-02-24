<?php
require_once 'core/Autoloader.php';
use Hop\Core\JsonStore;
use Hop\Core\UserManager;
use Hop\Core\LogManager;

session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = $_POST['username'] ?? '';
    $password = $_POST['password'] ?? '';

    if ($username && $password) {
        $userStore = new JsonStore('data/users.json');
        $userManager = new UserManager($userStore);
        $user = $userManager->authenticate($username, $password);
        $logger = new LogManager();

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            $logger->log('auth_success', $user['id'], "Вход в систему: $username");
            header('Location: index.php');
            exit;
        } else {
            $logger->log('auth_failure', 0, "Неудачная попытка входа: $username", 'warning');
            $error = 'Неверный логин или пароль';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Авторизация | ХОП</title>
    <link rel="stylesheet" href="assets/style.css">
    <link rel="preconnect" href="https://fonts.googleapis.com">
    <link rel="preconnect" href="https://fonts.gstatic.com" crossorigin>
    <link href="https://fonts.googleapis.com/css2?family=Inter:wght@300;400;500;600;700;800&display=swap" rel="stylesheet">
    <script src="https://unpkg.com/lucide@latest"></script>
    <style>
        body.login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            background: radial-gradient(circle at top right, #eef2f7 0%, #d1d9e6 100%);
            font-family: 'Inter', sans-serif;
        }

        .login-container {
            width: 100%;
            max-width: 420px;
            padding: 20px;
            perspective: 1000px;
        }

        .login-card {
            background: rgba(255, 255, 255, 0.7);
            backdrop-filter: blur(20px) saturate(180%);
            -webkit-backdrop-filter: blur(20px) saturate(180%);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 24px;
            padding: 40px;
            box-shadow: 0 20px 40px rgba(0,0,0,0.08);
            animation: cardFadeIn 0.8s cubic-bezier(0.16, 1, 0.3, 1);
        }

        @keyframes cardFadeIn {
            from { opacity: 0; transform: translateY(30px) rotateX(-10deg); }
            to { opacity: 1; transform: translateY(0) rotateX(0); }
        }

        .logo-section {
            text-align: center;
            margin-bottom: 32px;
        }

        .logo-icon {
            width: 64px;
            height: 64px;
            background: var(--win-accent-gradient);
            border-radius: 16px;
            margin: 0 auto 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            color: white;
            font-weight: 800;
            font-size: 24px;
            box-shadow: 0 8px 16px rgba(0, 120, 212, 0.25);
            animation: logoPulse 2s infinite ease-in-out;
        }

        @keyframes logoPulse {
            0% { transform: scale(1); }
            50% { transform: scale(1.05); }
            100% { transform: scale(1); }
        }

        h1 {
            margin: 0;
            font-size: 24px;
            font-weight: 800;
            color: #1a1a1a;
            letter-spacing: -0.5px;
        }

        .subtitle {
            color: #666;
            font-size: 14px;
            margin-top: 8px;
            font-weight: 500;
        }

        .form-group {
            margin-bottom: 20px;
            position: relative;
        }

        .form-group label {
            display: block;
            font-size: 12px;
            font-weight: 700;
            color: #444;
            margin-bottom: 8px;
            margin-left: 4px;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }

        .input-wrapper {
            position: relative;
        }

        .input-wrapper i {
            position: absolute;
            left: 16px;
            top: 50%;
            transform: translateY(-50%);
            color: #999;
            width: 18px;
            height: 18px;
            transition: color 0.2s;
        }

        .login-input {
            width: 100%;
            background: rgba(255, 255, 255, 0.5);
            border: 1.5px solid rgba(0,0,0,0.05);
            border-radius: 14px;
            padding: 14px 16px 14px 48px;
            font-size: 15px;
            font-weight: 500;
            color: #1a1a1a;
            transition: all 0.2s;
            box-sizing: border-box;
        }

        .login-input:focus {
            background: white;
            border-color: var(--win-accent);
            box-shadow: 0 0 0 4px rgba(0, 120, 212, 0.1);
            outline: none;
        }

        .login-input:focus + i {
            color: var(--win-accent);
        }

        .btn-submit {
            width: 100%;
            background: var(--win-accent-gradient);
            color: white;
            border: none;
            border-radius: 14px;
            padding: 16px;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.3s;
            margin-top: 10px;
            box-shadow: 0 4px 12px rgba(0, 120, 212, 0.2);
        }

        .btn-submit:hover {
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 24px rgba(0, 120, 212, 0.3);
            filter: brightness(1.05);
        }

        .btn-submit:active {
            transform: translateY(0) scale(0.98);
        }

        .error-message {
            background: #fff0f0;
            border: 1px solid #ffdbdb;
            color: #d63031;
            padding: 12px;
            border-radius: 12px;
            font-size: 13px;
            font-weight: 600;
            margin-bottom: 24px;
            display: flex;
            align-items: center;
            gap: 10px;
            animation: shake 0.5s cubic-bezier(.36,.07,.19,.97) both;
        }

        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }

        .login-footer {
            text-align: center;
            margin-top: 32px;
        }

        .dev-credit {
            font-size: 11px;
            color: #999;
            opacity: 0.8;
            transition: opacity 0.2s;
        }

        .dev-credit:hover {
            opacity: 1;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-container">
        <div class="login-card">
            <div class="logo-section">
                <div class="logo-icon">ХОП</div>
                <h1>Вход в систему</h1>
                <p class="subtitle">Управление заявками больницы</p>
            </div>

            <?php if ($error): ?>
                <div class="error-message">
                    <i data-lucide="alert-circle" style="width:18px;"></i>
                    <?php echo $error; ?>
                </div>
            <?php endif; ?>

            <form method="POST">
                <div class="form-group">
                    <label>Логин</label>
                    <div class="input-wrapper">
                        <input type="text" name="username" class="login-input" placeholder="Введите ваш логин" required autofocus autocomplete="username">
                        <i data-lucide="user"></i>
                    </div>
                </div>

                <div class="form-group">
                    <label>Пароль</label>
                    <div class="input-wrapper">
                        <input type="password" name="password" class="login-input" placeholder="Введите ваш пароль" required autocomplete="current-password">
                        <i data-lucide="lock"></i>
                    </div>
                </div>

                <button type="submit" class="btn-submit">Войти в кабинет</button>
            </form>

            <div class="login-footer">
                <div class="dev-credit">Разработчик wes.by</div>
            </div>
        </div>
    </div>

    <script>
        lucide.createIcons();
    </script>
</body>
</html>
