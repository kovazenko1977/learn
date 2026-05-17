<?php
require_once __DIR__ . '/../../src/autoload.php';
use App\Helpers\Auth;

Auth::requireAuth();

$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_pin'])) {
        $currentPin = $_POST['current_pin'] ?? '';
        $newPin = $_POST['new_pin'] ?? '';
        $confirmPin = $_POST['confirm_pin'] ?? '';

        if (Auth::login($currentPin)) {
            if (strlen($newPin) === 6 && is_numeric($newPin)) {
                if ($newPin === $confirmPin) {
                    Auth::updatePin($newPin);
                    $success = 'Пароль успешно обновлен';
                } else {
                    $error = 'Новые пароли не совпадают';
                }
            } else {
                $error = 'Новый пароль должен состоять из 6 цифр';
            }
        } else {
            $error = 'Текущий пароль неверен';
        }
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки - NewsManager</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <style>
        :root {
            --glass-bg: rgba(255, 255, 255, 0.7);
            --glass-border: rgba(255, 255, 255, 0.3);
            --accent-color: #4facfe;
        }
        body {
            background: linear-gradient(135deg, #f5f7fa 0%, #c3cfe2 100%);
            min-height: 100vh;
            font-family: 'Inter', -apple-system, BlinkMacSystemFont, sans-serif;
        }
        .sidebar {
            width: 280px;
            background: var(--glass-bg);
            backdrop-filter: blur(15px);
            border-right: 1px solid var(--glass-border);
            height: 100vh;
            position: fixed;
            left: 0;
            top: 0;
            padding: 2rem 1rem;
            z-index: 1000;
        }
        .main-content {
            margin-left: 280px;
            padding: 2rem;
        }
        .glass-card {
            background: var(--glass-bg);
            backdrop-filter: blur(10px);
            border: 1px solid var(--glass-border);
            border-radius: 20px;
            padding: 1.5rem;
            box-shadow: 0 4px 15px rgba(0,0,0,0.05);
        }
        .nav-link {
            color: #555;
            padding: 0.8rem 1rem;
            border-radius: 12px;
            margin-bottom: 0.5rem;
            display: flex;
            align-items: center;
            transition: all 0.2s;
        }
        .nav-link:hover, .nav-link.active {
            background: rgba(79, 172, 254, 0.15);
            color: var(--accent-color);
        }
        .nav-link i {
            margin-right: 12px;
            font-size: 1.2rem;
        }
    </style>
</head>
<body>
    <div class="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link" href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link" href="backup.php"><i class="bi bi-cloud-arrow-down"></i> Резервное копирование</a>
            <a class="nav-link active" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-5">
            <h2>Настройки</h2>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
                <?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
                <?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
            </div>
        <?php endif; ?>

        <div class="row">
            <div class="col-md-6">
                <div class="glass-card">
                    <h5>Изменение пароля доступа</h5>
                    <p class="text-muted small">Введите текущий 6-значный пароль и новый.</p>
                    <form method="POST" class="mt-4">
                        <div class="mb-3">
                            <label class="form-label">Текущий пароль</label>
                            <input type="password" name="current_pin" class="form-control rounded-3" maxlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Новый пароль</label>
                            <input type="password" name="new_pin" class="form-control rounded-3" maxlength="6" required>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Подтвердите новый пароль</label>
                            <input type="password" name="confirm_pin" class="form-control rounded-3" maxlength="6" required>
                        </div>
                        <button type="submit" name="update_pin" class="btn btn-primary rounded-pill px-4 mt-2">Обновить пароль</button>
                    </form>
                </div>
            </div>

            <div class="col-md-6">
                <div class="glass-card">
                    <h5>Информация о системе</h5>
                    <ul class="list-unstyled mt-3">
                        <li class="mb-2"><strong>PHP Версия:</strong> <?php echo PHP_VERSION; ?></li>
                        <li class="mb-2"><strong>Хранилище:</strong> JSON Flat Files</li>
                        <li class="mb-2"><strong>Путь к данным:</strong> <code>/data/</code></li>
                    </ul>
                    <hr>
                    <p class="small text-muted">Для вставки раздела на сайт используйте шорткод из меню "Разделы".</p>
                </div>
            </div>
        </div>
    </div>
</body>
</html>
