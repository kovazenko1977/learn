<?php
require_once 'core/Autoloader.php';
use Hop\Core\JsonStore;
use Hop\Core\UserManager;

session_start();

$error = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $code = $_POST['code'] ?? '';
    if ($code) {
        $userStore = new JsonStore('data/users.json');
        $userManager = new UserManager($userStore);
        $user = $userManager->getByCode($code);

        if ($user) {
            $_SESSION['user_id'] = $user['id'];
            $_SESSION['user_role'] = $user['role'];
            $_SESSION['user_name'] = $user['name'];
            header('Location: index.php');
            exit;
        } else {
            $error = 'Неверный персональный код';
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
    <style>
        body.login-page {
            min-height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0;
            background: linear-gradient(135deg, #f0f4f8 0%, #d7e3ec 100%);
        }
        .login-card {
            width: 100%;
            max-width: 400px;
            padding: 48px 32px;
            animation: cardEntrance 0.8s cubic-bezier(0.16, 1, 0.3, 1);
            text-align: center;
        }
        @keyframes cardEntrance {
            from { opacity: 0; transform: scale(0.9) translateY(20px); }
            to { opacity: 1; transform: scale(1) translateY(0); }
        }
        .logo-box {
            width: 72px;
            height: 72px;
            background: var(--win-accent);
            border-radius: 18px;
            margin: 0 auto 24px;
            display: flex;
            align-items: center;
            justify-content: center;
            box-shadow: 0 8px 16px rgba(0, 120, 212, 0.3);
            color: white;
            font-size: 32px;
            font-weight: 800;
            letter-spacing: -1px;
        }
        h1 { font-weight: 800; margin-bottom: 8px; color: #1a1a1a; font-size: 28px; }
        .subtitle { color: #666; margin-bottom: 40px; font-size: 14px; font-weight: 500; }

        .code-input {
            background: rgba(255,255,255,0.8);
            border: 2px solid var(--win-border);
            border-radius: 12px;
            padding: 16px;
            font-size: 32px;
            letter-spacing: 4px;
            text-align: center;
            width: 100%;
            box-sizing: border-box;
            transition: all 0.2s;
            margin-bottom: 24px;
            font-family: 'Inter', sans-serif;
            font-weight: 700;
            color: var(--win-text);
        }
        .code-input:focus {
            background: #fff;
            border-color: var(--win-accent);
            outline: none;
            box-shadow: 0 0 0 4px rgba(0, 120, 212, 0.1);
        }
        .error-box {
            background: #fee;
            color: #c00;
            padding: 12px;
            border-radius: 8px;
            margin-bottom: 24px;
            font-size: 13px;
            font-weight: 600;
            animation: shake 0.4s cubic-bezier(.36,.07,.19,.97) both;
        }
        @keyframes shake {
            10%, 90% { transform: translate3d(-1px, 0, 0); }
            20%, 80% { transform: translate3d(2px, 0, 0); }
            30%, 50%, 70% { transform: translate3d(-4px, 0, 0); }
            40%, 60% { transform: translate3d(4px, 0, 0); }
        }
        .btn-login {
            background: var(--win-accent);
            color: white;
            border: none;
            border-radius: 12px;
            padding: 16px;
            width: 100%;
            font-size: 16px;
            font-weight: 700;
            cursor: pointer;
            transition: all 0.2s;
        }
        .btn-login:hover {
            filter: brightness(1.1);
            transform: translateY(-2px) scale(1.02);
            box-shadow: 0 8px 24px rgba(0, 120, 212, 0.4);
        }
        .btn-login:active { transform: translateY(0); }

        .footer-note {
            margin-top: 32px;
            font-size: 12px;
            color: #888;
        }
    </style>
</head>
<body class="login-page">
    <div class="login-card card mica">
        <div class="logo-box">ХОП</div>
        <h1>Вход в систему</h1>
        <p class="subtitle">Информационная система техподдержки больницы</p>

        <form method="POST">
            <?php if ($error): ?>
                <div class="error-box"><?php echo $error; ?></div>
            <?php endif; ?>

            <div style="position: relative;">
                <input type="text" name="code" id="code-input" class="code-input" placeholder="000000" required autofocus maxlength="6" pattern="\d{6}" inputmode="numeric">
                <div style="font-size: 11px; color: var(--win-text-secondary); margin-bottom: 20px;">Введите ваш персональный 6-значный код</div>
            </div>

            <button type="submit" class="btn-login">Войти в кабинет</button>
        </form>

        <div class="footer-note">
            <div>При возникновении проблем с доступом обратитесь к администратору</div>
            <div style="margin-top: 12px; font-size: 10px; opacity: 0.7;">Разработчик wes.by</div>
        </div>
    </div>
</body>
</html>
