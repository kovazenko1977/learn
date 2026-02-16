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
            $error = 'Неверный код доступа';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход в систему ХОП</title>
    <link rel="stylesheet" href="assets/style.css">
</head>
<body class="login-page">
    <div class="login-container">
        <h1>ХОП</h1>
        <p>Хозяйственно-Оперативные Поручения</p>
        <form method="POST">
            <div class="form-group">
                <input type="password" name="code" placeholder="Введите ваш код" required autofocus maxlength="6" pattern="\d{6}">
            </div>
            <?php if ($error): ?>
                <div class="error-message"><?php echo $error; ?></div>
            <?php endif; ?>
            <button type="submit" class="btn-primary">Войти</button>
        </form>
    </div>
</body>
</html>
