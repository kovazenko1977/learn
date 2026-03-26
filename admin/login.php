<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в панель управления</title>
    <style>
        body { font-family: -apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, Helvetica, Arial, sans-serif; background: #f0f2f5; display: flex; align-items: center; justify-content: center; height: 100vh; margin: 0; }
        .login-card { background: white; padding: 2rem; border-radius: 12px; box-shadow: 0 4px 6px rgba(0,0,0,0.1); width: 100%; max-width: 400px; text-align: center; }
        h1 { color: #1c1e21; margin-bottom: 1.5rem; }
        input { width: 100%; padding: 12px; margin-bottom: 1rem; border: 1px solid #ddd; border-radius: 6px; box-sizing: border-box; font-size: 1rem; }
        button { width: 100%; padding: 12px; background: #7360f2; border: none; border-radius: 6px; color: white; font-size: 1rem; cursor: pointer; transition: background 0.3s; }
        button:hover { background: #5a4ac3; }
        .error { color: #e41e3f; margin-bottom: 1rem; }
    </style>
</head>
<body>
    <div class="login-card">
        <h1>Бронирование Санатория</h1>
        <?php
        require_once '../includes/AuthManager.php';
        if (isset($_POST['passcode'])) {
            if (AuthManager::login($_POST['passcode'])) {
                header('Location: index.php');
                exit;
            } else {
                echo '<p class="error">Неверный код доступа</p>';
            }
        }
        ?>
        <form method="POST">
            <input type="password" name="passcode" placeholder="Введите 6-значный код" maxlength="6" required>
            <button type="submit">Войти</button>
        </form>
    </div>
</body>
</html>
