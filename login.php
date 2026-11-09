<?php
require_once __DIR__ . '/src/autoload.php';
use App\Helpers\Auth;

if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

if (Auth::check()) {
    header('Location: admin/index.php');
    exit;
}

$error = '';

if (Auth::isLocked()) {
    $error = 'Слишком много неудачных попыток. Пожалуйста, подождите 15 минут.';
} elseif ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $pin = $_POST['pin'] ?? '';
    if (Auth::login($pin)) {
        header('Location: admin/index.php');
        exit;
    } else {
        $error = 'Неверный PIN-код';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Вход - NewsManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        body {
            background: linear-gradient(135deg, #4facfe 0%, #00f2fe 100%);
            height: 100vh;
            display: flex;
            align-items: center;
            justify-content: center;
            font-family: 'Inter', sans-serif;
        }
        .login-card {
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(15px);
            border: 1px solid rgba(255, 255, 255, 0.3);
            border-radius: 25px;
            padding: 3rem;
            width: 100%;
            max-width: 400px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
            text-align: center;
        }
        .pin-input {
            font-size: 2rem;
            letter-spacing: 0.5rem;
            text-align: center;
            border-radius: 15px;
            border: 2px solid #eee;
            margin-bottom: 1.5rem;
            transition: all 0.3s;
        }
        .pin-input:focus {
            border-color: #4facfe;
            box-shadow: 0 0 0 0.25rem rgba(79, 172, 254, 0.25);
        }
        .btn-login {
            background: #4facfe;
            border: none;
            padding: 0.8rem;
            font-weight: 600;
            border-radius: 15px;
        }
    </style>
</head>
<body>
    <div class="login-card">
        <h3 class="mb-4"><b>News</b>Manager</h3>
        <p class="text-muted mb-4">Введите 6-значный PIN-код</p>

        <?php if ($error): ?>
            <div class="alert alert-danger rounded-4 py-2 small"><?php echo $error; ?></div>
        <?php endif; ?>

        <form method="POST">
            <input type="password" name="pin" class="form-control pin-input" maxlength="6" pattern="\d{6}" autofocus required autocomplete="off">
            <button type="submit" class="btn btn-primary w-100 btn-login shadow-sm">Войти</button>
        </form>
    </div>
</body>
</html>
