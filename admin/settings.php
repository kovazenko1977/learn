<?php
require_once __DIR__ . '/../src/autoload.php';
use App\Helpers\Auth;
use App\Database\JsonStore;

Auth::requireAuth();

$success = '';
$error = '';

$settingsStore = new JsonStore(__DIR__ . '/../data/settings.json');
$settings = $settingsStore->getAll();
if (empty($settings)) {
    $settings = [
        'site_title' => 'NewsManager CMS',
        'site_description' => 'Управление новостями и медиа для WordPress и Elementor',
        'admin_email' => 'admin@site.local',
        'timezone' => 'Europe/Minsk',
        'date_format' => 'd.m.Y H:i',
        'accent_color' => '#4facfe',
        'default_view' => 'cards',
        'items_per_page' => 12,
        'enable_comments' => false,
        'enable_reactions' => true,
        'enable_share' => true,
        'enable_search' => true,
        'enable_views' => true,
        'enable_reading_time' => true,
        'enable_breadcrumbs' => true,
        'enable_lazy_load' => true,
        'enable_qr' => true,
        'enable_copy_link' => true,
        'enable_subscribe' => true,
        'enable_toc' => true,
        'enable_progress_bar' => true,
        'enable_dark_mode' => true,
        'allowed_upload_types' => 'jpg,png,webp,gif,svg,mp4,webm',
        'max_upload_size_mb' => 50,
        'webhook_url' => '',
        'telegram_bot_token' => '',
        'telegram_chat_id' => '',
        'custom_global_css' => '',
        'custom_global_js' => ''
    ];
    $settingsStore->set($settings);
}

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
    } elseif (isset($_POST['save_system_settings'])) {
        $settings['site_title'] = trim($_POST['site_title'] ?? 'NewsManager CMS');
        $settings['site_description'] = trim($_POST['site_description'] ?? '');
        $settings['admin_email'] = trim($_POST['admin_email'] ?? '');
        $settings['timezone'] = $_POST['timezone'] ?? 'Europe/Minsk';
        $settings['date_format'] = $_POST['date_format'] ?? 'd.m.Y H:i';
        $settings['accent_color'] = $_POST['accent_color'] ?? '#4facfe';
        $settings['default_view'] = $_POST['default_view'] ?? 'cards';
        $settings['items_per_page'] = max(1, (int)($_POST['items_per_page'] ?? 12));

        $settings['enable_reactions'] = isset($_POST['enable_reactions']);
        $settings['enable_share'] = isset($_POST['enable_share']);
        $settings['enable_search'] = isset($_POST['enable_search']);
        $settings['enable_views'] = isset($_POST['enable_views']);
        $settings['enable_reading_time'] = isset($_POST['enable_reading_time']);
        $settings['enable_breadcrumbs'] = isset($_POST['enable_breadcrumbs']);
        $settings['enable_lazy_load'] = isset($_POST['enable_lazy_load']);
        $settings['enable_qr'] = isset($_POST['enable_qr']);
        $settings['enable_copy_link'] = isset($_POST['enable_copy_link']);
        $settings['enable_subscribe'] = isset($_POST['enable_subscribe']);
        $settings['enable_toc'] = isset($_POST['enable_toc']);
        $settings['enable_progress_bar'] = isset($_POST['enable_progress_bar']);
        $settings['enable_dark_mode'] = isset($_POST['enable_dark_mode']);

        $settings['allowed_upload_types'] = trim($_POST['allowed_upload_types'] ?? 'jpg,png,webp,gif,svg,mp4,webm');
        $settings['max_upload_size_mb'] = max(1, (int)($_POST['max_upload_size_mb'] ?? 50));

        $settings['webhook_url'] = trim($_POST['webhook_url'] ?? '');
        $settings['telegram_bot_token'] = trim($_POST['telegram_bot_token'] ?? '');
        $settings['telegram_chat_id'] = trim($_POST['telegram_chat_id'] ?? '');
        $settings['custom_global_css'] = $_POST['custom_global_css'] ?? '';
        $settings['custom_global_js'] = $_POST['custom_global_js'] ?? '';

        $settingsStore->set($settings);
        Auth::log("Обновлены системные настройки администратора");
        $success = 'Все настройки системы успешно сохранены!';
    }
}
?>
<!DOCTYPE html>
<html lang="ru">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Настройки - NewsManager Pro</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.10.0/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/admin.css">
    <style>
        .settings-tab-pane {
            animation: fadeIn 0.3s ease;
        }
        @keyframes fadeIn {
            from { opacity: 0; transform: translateY(6px); }
            to { opacity: 1; transform: translateY(0); }
        }
    </style>
</head>
<body>
    <button class="btn btn-primary d-lg-none mobile-menu-btn" id="mobileMenuBtn"><i class="bi bi-list"></i></button>
    <div class="sidebar-backdrop" id="sidebarBackdrop"></div>
    <div class="sidebar" id="sidebar">
        <h4 class="mb-5 px-3"><b>News</b>Manager</h4>
        <nav class="nav flex-column">
            <a class="nav-link" href="index.php"><i class="bi bi-speedometer2"></i> Дашборд</a>
            <a class="nav-link" href="sections.php"><i class="bi bi-folder"></i> Разделы</a>
            <a class="nav-link" href="news.php"><i class="bi bi-newspaper"></i> Новости</a>
            <a class="nav-link" href="media.php"><i class="bi bi-images"></i> Медиа менеджер</a>
            <a class="nav-link" href="analytics.php"><i class="bi bi-bar-chart"></i> Аналитика</a>
            <a class="nav-link" href="subscribers.php"><i class="bi bi-envelope-at"></i> Подписчики</a>
            <a class="nav-link" href="users.php"><i class="bi bi-people"></i> Пользователи</a>
            <a class="nav-link" href="logs.php"><i class="bi bi-list-check"></i> Журнал</a>
            <a class="nav-link" href="health.php"><i class="bi bi-heart-pulse"></i> Состояние</a>
            <a class="nav-link" href="backup.php"><i class="bi bi-cloud-arrow-down"></i> Резервное копирование</a>
            <a class="nav-link active" href="settings.php"><i class="bi bi-gear"></i> Настройки</a>
            <a class="nav-link" href="about.php"><i class="bi bi-info-circle"></i> О программе (40 функций)</a>
            <hr>
            <a class="nav-link text-danger" href="../logout.php"><i class="bi bi-box-arrow-right"></i> Выход</a>
        </nav>
    </div>

    <div class="main-content">
        <header class="mb-4 d-flex justify-content-between align-items-center flex-wrap gap-3">
            <div>
                <h2 class="mb-1">Настройки системы</h2>
                <p class="text-muted mb-0 small">Максимальный набор параметров: безопасность, интеграции, виджеты, стили и загрузка медиа</p>
            </div>
            <div class="d-flex gap-2">
                <button class="btn btn-outline-secondary rounded-circle" id="themeToggle"><i class="bi bi-moon"></i></button>
            </div>
        </header>

        <?php if ($success): ?>
            <div class="alert alert-success alert-dismissible fade show rounded-4" role="alert">
                <i class="bi bi-check-circle-fill me-2"></i><?php echo $success; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <?php if ($error): ?>
            <div class="alert alert-danger alert-dismissible fade show rounded-4" role="alert">
                <i class="bi bi-exclamation-triangle-fill me-2"></i><?php echo $error; ?>
                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
            </div>
        <?php endif; ?>

        <!-- Nav tabs for settings categories -->
        <ul class="nav nav-pills mb-4 gap-2" id="settingsTab" role="tablist">
            <li class="nav-item" role="presentation">
                <button class="nav-link active rounded-pill px-4" id="general-tab" data-bs-toggle="pill" data-bs-target="#tab-general" type="button" role="tab"><i class="bi bi-sliders me-1"></i> Основные</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4" id="display-tab" data-bs-toggle="pill" data-bs-target="#tab-display" type="button" role="tab"><i class="bi bi-layout-text-window me-1"></i> Отображение и Виджеты</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4" id="media-tab" data-bs-toggle="pill" data-bs-target="#tab-media" type="button" role="tab"><i class="bi bi-camera-video me-1"></i> Медиа и Загрузки</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4" id="integration-tab" data-bs-toggle="pill" data-bs-target="#tab-integration" type="button" role="tab"><i class="bi bi-send me-1"></i> Webhooks & Telegram</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4" id="security-tab" data-bs-toggle="pill" data-bs-target="#tab-security" type="button" role="tab"><i class="bi bi-shield-lock me-1"></i> Безопасность (PIN)</button>
            </li>
            <li class="nav-item" role="presentation">
                <button class="nav-link rounded-pill px-4" id="custom-code-tab" data-bs-toggle="pill" data-bs-target="#tab-custom-code" type="button" role="tab"><i class="bi bi-code-slash me-1"></i> Свои CSS / JS</button>
            </li>
        </ul>

        <form method="POST">
            <div class="tab-content" id="settingsTabContent">
                <!-- Tab 1: General -->
                <div class="tab-pane fade show active settings-tab-pane" id="tab-general" role="tabpanel">
                    <div class="row g-4">
                        <div class="col-lg-8">
                            <div class="glass-card">
                                <h5 class="mb-3 text-primary"><i class="bi bi-gear-fill me-2"></i>Параметры сайта и системы</h5>
                                <div class="mb-3">
                                    <label class="form-label">Название портала / сайта</label>
                                    <input type="text" name="site_title" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['site_title'] ?? ''); ?>" required>
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Описание портала (мета-теги по умолчанию)</label>
                                    <textarea name="site_description" class="form-control rounded-3" rows="2"><?php echo htmlspecialchars($settings['site_description'] ?? ''); ?></textarea>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Email администратора</label>
                                        <input type="email" name="admin_email" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['admin_email'] ?? ''); ?>">
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Часовой пояс</label>
                                        <select name="timezone" class="form-select rounded-3">
                                            <?php
                                            $timezones = ['Europe/Minsk', 'Europe/Moscow', 'Europe/Kyiv', 'Asia/Almaty', 'UTC'];
                                            foreach ($timezones as $tz):
                                            ?>
                                                <option value="<?php echo $tz; ?>" <?php echo ($settings['timezone'] ?? '') === $tz ? 'selected' : ''; ?>><?php echo $tz; ?></option>
                                            <?php endforeach; ?>
                                        </select>
                                    </div>
                                </div>
                                <div class="row">
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Формат вывода дат</label>
                                        <select name="date_format" class="form-select rounded-3">
                                            <option value="d.m.Y H:i" <?php echo ($settings['date_format'] ?? '') === 'd.m.Y H:i' ? 'selected' : ''; ?>>09.11.2026 14:30 (d.m.Y H:i)</option>
                                            <option value="d.m.Y" <?php echo ($settings['date_format'] ?? '') === 'd.m.Y' ? 'selected' : ''; ?>>09.11.2026 (d.m.Y)</option>
                                            <option value="Y-m-d" <?php echo ($settings['date_format'] ?? '') === 'Y-m-d' ? 'selected' : ''; ?>>2026-11-09 (Y-m-d)</option>
                                        </select>
                                    </div>
                                    <div class="col-md-6 mb-3">
                                        <label class="form-label">Основной цвет акцента (HEX)</label>
                                        <div class="input-group">
                                            <input type="color" class="form-control form-control-color rounded-start-3" value="<?php echo htmlspecialchars($settings['accent_color'] ?? '#4facfe'); ?>" onchange="document.getElementById('accentText').value=this.value">
                                            <input type="text" id="accentText" name="accent_color" class="form-control rounded-end-3" value="<?php echo htmlspecialchars($settings['accent_color'] ?? '#4facfe'); ?>">
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="col-lg-4">
                            <div class="glass-card">
                                <h5>Информация о сервере</h5>
                                <ul class="list-unstyled mt-3 small">
                                    <li class="mb-2"><strong>PHP:</strong> <?php echo PHP_VERSION; ?></li>
                                    <li class="mb-2"><strong>Хранилище:</strong> JSON Flat Files (flock)</li>
                                    <li class="mb-2"><strong>Загрузки:</strong> /data/uploads/</li>
                                    <li class="mb-2"><strong>Память лимит:</strong> <?php echo ini_get('memory_limit'); ?></li>
                                    <li class="mb-2"><strong>Макс. пост:</strong> <?php echo ini_get('post_max_size'); ?></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 2: Display & Widgets -->
                <div class="tab-pane fade settings-tab-pane" id="tab-display" role="tabpanel">
                    <div class="glass-card">
                        <h5 class="mb-4 text-primary"><i class="bi bi-toggle-on me-2"></i>Глобальные переключатели отображения в шорткодах</h5>
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Вид по умолчанию</label>
                                <select name="default_view" class="form-select rounded-3">
                                    <option value="cards" <?php echo ($settings['default_view'] ?? '') === 'cards' ? 'selected' : ''; ?>>Сетка карточек (Cards)</option>
                                    <option value="table" <?php echo ($settings['default_view'] ?? '') === 'table' ? 'selected' : ''; ?>>Компактная таблица (Table)</option>
                                </select>
                            </div>
                            <div class="col-md-4 mb-3">
                                <label class="form-label">Элементов на странице по умолчанию</label>
                                <input type="number" name="items_per_page" class="form-control rounded-3" value="<?php echo (int)($settings['items_per_page'] ?? 12); ?>" min="1" max="100">
                            </div>
                        </div>
                        <hr class="my-4">
                        <div class="row g-3">
                            <div class="col-md-6 col-lg-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enable_reactions" id="opt_reactions" <?php echo !empty($settings['enable_reactions']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="opt_reactions">Включить лайки / реакции читателей</label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enable_share" id="opt_share" <?php echo !empty($settings['enable_share']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="opt_share">Кнопки шеринга (Telegram, WhatsApp, VK)</label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enable_search" id="opt_search" <?php echo !empty($settings['enable_search']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="opt_search">Интерактивный живой поиск по новостям</label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enable_views" id="opt_views" <?php echo !empty($settings['enable_views']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="opt_views">Счетчик уникальных просмотров</label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enable_reading_time" id="opt_readtime" <?php echo !empty($settings['enable_reading_time']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="opt_readtime">Расчет времени на чтение статьи</label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enable_breadcrumbs" id="opt_crumbs" <?php echo !empty($settings['enable_breadcrumbs']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="opt_crumbs">Хлебные крошки (навигационная цепочка)</label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enable_lazy_load" id="opt_lazy" <?php echo !empty($settings['enable_lazy_load']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="opt_lazy">Ленивая загрузка фото (Lazy-loading)</label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enable_qr" id="opt_qr" <?php echo !empty($settings['enable_qr']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="opt_qr">Генератор QR-кода для мобильных</label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enable_copy_link" id="opt_copylink" <?php echo !empty($settings['enable_copy_link']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="opt_copylink">Кнопка быстрого копирования ссылки</label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enable_subscribe" id="opt_sub" <?php echo !empty($settings['enable_subscribe']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="opt_sub">Виджет подписки на рассылку</label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enable_toc" id="opt_toc" <?php echo !empty($settings['enable_toc']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="opt_toc">Авто-оглавление статьи (Table of Contents)</label>
                                </div>
                            </div>
                            <div class="col-md-6 col-lg-4">
                                <div class="form-check form-switch">
                                    <input class="form-check-input" type="checkbox" name="enable_progress_bar" id="opt_progress" <?php echo !empty($settings['enable_progress_bar']) ? 'checked' : ''; ?>>
                                    <label class="form-check-label" for="opt_progress">Индикатор глубины прокрутки страницы</label>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 3: Media & Uploads -->
                <div class="tab-pane fade settings-tab-pane" id="tab-media" role="tabpanel">
                    <div class="glass-card">
                        <h5 class="mb-3 text-primary"><i class="bi bi-file-earmark-play-fill me-2"></i>Параметры фото и видеозагрузок</h5>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Разрешенные расширения файлов (через запятую)</label>
                                <input type="text" name="allowed_upload_types" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['allowed_upload_types'] ?? 'jpg,png,webp,gif,svg,mp4,webm'); ?>">
                                <small class="text-muted">Поддерживаются фото и видеоматериалы (MP4, WebM, Ogg, JPG, PNG, WEBP).</small>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Максимальный размер файла (МБ)</label>
                                <input type="number" name="max_upload_size_mb" class="form-control rounded-3" value="<?php echo (int)($settings['max_upload_size_mb'] ?? 50); ?>" min="1" max="500">
                                <small class="text-muted">Лимит сервера в php.ini: <?php echo ini_get('upload_max_filesize'); ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 4: Integrations -->
                <div class="tab-pane fade settings-tab-pane" id="tab-integration" role="tabpanel">
                    <div class="glass-card">
                        <h5 class="mb-3 text-primary"><i class="bi bi-broadcast me-2"></i>Интеграции и оповещения при публикации</h5>
                        <div class="mb-3">
                            <label class="form-label">Глобальный Webhook URL (HTTP POST)</label>
                            <input type="url" name="webhook_url" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['webhook_url'] ?? ''); ?>" placeholder="https://your-crm.com/api/news-webhook">
                            <small class="text-muted">При публикации новости система автоматически отправит JSON-пейлоад с заголовком, текстом и ссылками.</small>
                        </div>
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telegram Bot Token (опционально)</label>
                                <input type="text" name="telegram_bot_token" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['telegram_bot_token'] ?? ''); ?>" placeholder="123456789:ABCdef...">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label">Telegram Chat ID / Канал</label>
                                <input type="text" name="telegram_chat_id" class="form-control rounded-3" value="<?php echo htmlspecialchars($settings['telegram_chat_id'] ?? ''); ?>" placeholder="@my_news_channel или -100123456789">
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 5: Security & PIN -->
                <div class="tab-pane fade settings-tab-pane" id="tab-security" role="tabpanel">
                    <div class="row">
                        <div class="col-md-7">
                            <div class="glass-card">
                                <h5 class="mb-3 text-danger"><i class="bi bi-shield-lock-fill me-2"></i>Изменение цифрового пароля доступа</h5>
                                <p class="text-muted small">Введите текущий цифровой пароль и задайте новый (6 цифр).</p>
                                <div class="mb-3">
                                    <label class="form-label">Текущий пароль</label>
                                    <input type="password" name="current_pin" class="form-control rounded-3" maxlength="6">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Новый 6-значный пароль</label>
                                    <input type="password" name="new_pin" class="form-control rounded-3" maxlength="6">
                                </div>
                                <div class="mb-3">
                                    <label class="form-label">Подтвердите новый пароль</label>
                                    <input type="password" name="confirm_pin" class="form-control rounded-3" maxlength="6">
                                </div>
                                <button type="submit" name="update_pin" class="btn btn-danger rounded-pill px-4 mt-2"><i class="bi bi-key-fill me-1"></i>Обновить пароль PIN</button>
                            </div>
                        </div>
                        <div class="col-md-5">
                            <div class="glass-card">
                                <h6>Политика безопасности</h6>
                                <ul class="small text-muted ps-3 mt-3">
                                    <li class="mb-2">Защита от brute-force: 5 попыток с одного IP, после чего блокировка на 15 минут.</li>
                                    <li class="mb-2">Пароли хешируются с использованием стойкого алгоритма BCRYPT.</li>
                                    <li class="mb-2">Сессии админа защищены от межсайтовых атак и подделки.</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Tab 6: Custom CSS / JS -->
                <div class="tab-pane fade settings-tab-pane" id="tab-custom-code" role="tabpanel">
                    <div class="glass-card">
                        <h5 class="mb-3 text-primary"><i class="bi bi-code-square me-2"></i>Глобальный пользовательский код</h5>
                        <div class="mb-3">
                            <label class="form-label">Глобальный CSS код (для всех шорткодов)</label>
                            <textarea name="custom_global_css" class="form-control font-monospace rounded-3" rows="5" placeholder=".news-card { border-radius: 20px; }"><?php echo htmlspecialchars($settings['custom_global_css'] ?? ''); ?></textarea>
                        </div>
                        <div class="mb-3">
                            <label class="form-label">Глобальный JavaScript код</label>
                            <textarea name="custom_global_js" class="form-control font-monospace rounded-3" rows="4" placeholder="console.log('NewsManager loaded');"><?php echo htmlspecialchars($settings['custom_global_js'] ?? ''); ?></textarea>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Global Save Button -->
            <div class="glass-card mt-4 p-3 d-flex justify-content-between align-items-center">
                <span class="text-muted small"><i class="bi bi-info-circle me-1"></i>Все изменения применяются немедленно ко всем шорткодам и админке</span>
                <button type="submit" name="save_system_settings" class="btn btn-primary rounded-pill px-5 py-2 shadow-sm"><i class="bi bi-check2-circle me-1"></i> Сохранить настройки системы</button>
            </div>
        </form>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.3.0/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Dark theme toggle
        const themeToggle = document.getElementById('themeToggle');
        if (themeToggle) {
            const currentTheme = localStorage.getItem('theme') || 'light';
            document.documentElement.setAttribute('data-theme', currentTheme);
            themeToggle.innerHTML = currentTheme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
            themeToggle.onclick = () => {
                const theme = document.documentElement.getAttribute('data-theme') === 'dark' ? 'light' : 'dark';
                document.documentElement.setAttribute('data-theme', theme);
                localStorage.setItem('theme', theme);
                themeToggle.innerHTML = theme === 'dark' ? '<i class="bi bi-sun"></i>' : '<i class="bi bi-moon"></i>';
            };
        }

        // Mobile menu toggle
        const mobileBtn = document.getElementById('mobileMenuBtn');
        const sidebar = document.getElementById('sidebar');
        const backdrop = document.getElementById('sidebarBackdrop');
        if (mobileBtn && sidebar) {
            mobileBtn.onclick = () => {
                sidebar.classList.toggle('show');
                backdrop.classList.toggle('show');
            };
            backdrop.onclick = () => {
                sidebar.classList.remove('show');
                backdrop.classList.remove('show');
            };
        }
    </script>
</body>
</html>
