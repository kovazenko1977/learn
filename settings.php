<?php
require_once 'core/Autoloader.php';
require_once 'includes/auth.php';
use Hop\Core\JsonStore;
use Hop\Core\BackupManager;
use Hop\Core\DemoDataLoader;
use Hop\Core\NotificationManager;

checkRole('admin');

$settingsStore = new JsonStore('data/settings.json');
$settings = $settingsStore->read();

$message = '';
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['tg_test_id'])) {
    checkCsrf();
    $notifStore = new JsonStore('data/notifications.json');
    $nm = new NotificationManager($settingsStore, $notifStore);
    $testResult = $nm->testConnection($_POST['tg_test_token'], $_POST['tg_test_id']);
    if ($testResult['success']) {
        $message = "✅ Тестовое сообщение успешно отправлено!";
    } else {
        $message = "❌ Ошибка Telegram: " . $testResult['error'];
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['hospital_name'])) {
    checkCsrf();
    $newSettings = [
        'hospital_name' => $_POST['hospital_name'],
        'sla' => [
            'low' => (int)$_POST['sla_low'],
            'medium' => (int)$_POST['sla_medium'],
            'high' => (int)$_POST['sla_high'],
            'critical' => (int)$_POST['sla_critical']
        ],
        'telegram_token' => $_POST['telegram_token'],
        'telegram_chat_id' => $_POST['telegram_chat_id'],
        'accent_color' => $_POST['accent_color'] ?? '#0078d4',
        'primary_font' => $_POST['primary_font'] ?? 'Inter',
        'polling_interval' => (int)($_POST['polling_interval'] ?? 10)
    ];

    if ($settingsStore->save($newSettings)) {
        $settings = $newSettings;
        $message = 'Настройки системы успешно обновлены';
    } else {
        $message = 'Ошибка при сохранении настроек';
    }
}

$backupManager = new BackupManager('data', 'uploads');

if (isset($_GET['action'])) {
    if ($_GET['action'] === 'backup') {
        try {
            $file = $backupManager->createBackup();
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="' . basename($file) . '"');
            readfile($file);
            unlink($file);
            exit;
        } catch (\Exception $e) {
            $message = "Ошибка бэкапа: " . $e->getMessage();
        }
    } elseif ($_GET['action'] === 'demo') {
        $requestStore = new JsonStore('data/requests.json');
        $serviceStore = new JsonStore('data/services.json');
        $userStore = new JsonStore('data/users.json');
        $tmplStore = new JsonStore('data/templates.json');
        $locStore = new JsonStore('data/locations.json');
        $loader = new DemoDataLoader();
        $loader->load($serviceStore, $userStore, $requestStore, $tmplStore, $locStore);
        header('Location: settings.php?msg=demo_ok');
        exit;
    }
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['maintenance'])) {
    checkCsrf();
    if ($_POST['maintenance'] === 'restore' && isset($_FILES['backup_file'])) {
        if ($backupManager->restoreBackup($_FILES['backup_file']['tmp_name'])) {
            $message = 'Данные успешно восстановлены из архива';
        } else {
            $message = 'Ошибка при восстановлении данных';
        }
    } elseif ($_POST['maintenance'] === 'reset') {
        if ($backupManager->resetData($_POST['reset_password'])) {
            $message = 'Система полностью очищена';
        } else {
            $message = 'Неверный административный пароль';
        }
    } elseif ($_POST['maintenance'] === 'purge') {
        $purgeDate = $_POST['purge_date'];
        if ($purgeDate) {
            $reqStore = new JsonStore('data/requests.json');
            $chatStore = new JsonStore('data/chat.json');
            $notifStore = new JsonStore('data/notifications.json');

            $nm = new \Hop\Core\NotificationManager($settingsStore, $notifStore);
            $rm = new \Hop\Core\RequestManager($reqStore, $nm);
            $cm = new \Hop\Core\ChatManager($chatStore);

            $purgedReqs = $rm->purgeBefore($purgeDate);
            $purgedChat = $cm->purgeBefore($purgeDate);
            $purgedNotifs = $nm->purgeBefore($purgeDate);

            $message = "✅ Очистка завершена: Удалено заявок: $purgedReqs, сообщений чата: $purgedChat, уведомлений: $purgedNotifs";
        }
    }
}

if (isset($_GET['msg']) && $_GET['msg'] === 'demo_ok') {
    $message = 'Демонстрационные данные успешно загружены (120 записей)';
}

include 'includes/header.php';
?>

<div class="container">
    <div class="page-header" style="animation: slideDown 0.5s ease-out;">
        <h1>Настройки ХОП</h1>
        <p style="color:var(--win-text-secondary);">Конфигурация параметров и обслуживание системы</p>
    </div>

    <?php if ($message): ?>
        <div class="alert alert-success" style="animation: slideDown 0.3s ease-out;"><?php echo $message; ?></div>
    <?php endif; ?>

    <div class="settings-tabs mica" style="animation: slideDown 0.4s ease-out; margin-bottom: 24px;">
        <button class="tab-btn active" onclick="showTab('org')"><i data-lucide="building-2"></i> Организация</button>
        <button class="tab-btn" onclick="showTab('tg')"><i data-lucide="bell-ring"></i> Уведомления</button>
        <button class="tab-btn" onclick="showTab('sla')"><i data-lucide="timer"></i> SLA & Система</button>
        <button class="tab-btn" onclick="showTab('data')"><i data-lucide="database"></i> Обслуживание</button>
    </div>

    <form method="POST" style="animation: slideUp 0.6s ease-out;">
        <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">

        <div id="tab-org" class="tab-content active">
            <div class="form-grid" style="align-items: stretch;">
                <section class="card mica">
                <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                    <i data-lucide="building-2" style="color:var(--win-accent);"></i> Организация
                </h2>
                <div class="form-group">
                    <label>Название учреждения</label>
                    <input type="text" name="hospital_name" value="<?php echo htmlspecialchars($settings['hospital_name']); ?>" required>
                </div>
            </section>

            <section class="card mica">
                <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                    <i data-lucide="settings" style="color:var(--win-accent);"></i> Системные
                </h2>
                <div class="form-group">
                    <label>Интервал проверки уведомлений (сек)</label>
                    <input type="number" name="polling_interval" value="<?php echo (int)($settings['polling_interval'] ?? 10); ?>" min="2" max="300" required>
                    <small style="color:var(--win-text-secondary); font-size:11px;">Как часто браузер будет проверять новые заявки (рекомендуется 10-30 сек).</small>
                </div>
            </section>

            <section class="card mica">
                <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                    <i data-lucide="palette" style="color:var(--win-accent);"></i> Внешний вид
                </h2>
                <div class="form-group">
                    <label>Акцентный цвет (HEX)</label>
                    <div style="display:flex; gap:10px;">
                        <input type="color" name="accent_color" value="<?php echo htmlspecialchars($settings['accent_color'] ?? '#0078d4'); ?>" style="width:50px; height:38px; padding:2px;">
                        <input type="text" value="<?php echo htmlspecialchars($settings['accent_color'] ?? '#0078d4'); ?>" readonly style="flex:1;">
                    </div>
                </div>
                <div class="form-group">
                    <label>Основной шрифт</label>
                    <select name="primary_font">
                        <option value="Inter" <?php echo ($settings['primary_font'] ?? 'Inter') === 'Inter' ? 'selected' : ''; ?>>Inter (Системный)</option>
                        <option value="'Segoe UI', sans-serif" <?php echo ($settings['primary_font'] ?? '') === "'Segoe UI', sans-serif" ? 'selected' : ''; ?>>Segoe UI</option>
                        <option value="'Roboto', sans-serif" <?php echo ($settings['primary_font'] ?? '') === "'Roboto', sans-serif" ? 'selected' : ''; ?>>Roboto</option>
                        <option value="'Open Sans', sans-serif" <?php echo ($settings['primary_font'] ?? '') === "'Open Sans', sans-serif" ? 'selected' : ''; ?>>Open Sans</option>
                        <option value="system-ui" <?php echo ($settings['primary_font'] ?? '') === 'system-ui' ? 'selected' : ''; ?>>System Default</option>
                    </select>
                </div>
                </section>
            </div>
        </div>

        <div id="tab-tg" class="tab-content">
            <section class="card mica">
            <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                <i data-lucide="bell-ring" style="color:var(--win-accent);"></i> Уведомления Telegram
            </h2>

            <div class="tg-config-layout">
                <div>
                    <div class="form-grid" style="grid-template-columns: 1fr;">
                        <div class="form-group">
                            <label>Bot Token</label>
                            <input type="text" id="tg_token" name="telegram_token" value="<?php echo htmlspecialchars($settings['telegram_token']); ?>" placeholder="000000000:AAHHH...">
                        </div>
                        <div class="form-group">
                            <label>Target Chat ID (Глобальный)</label>
                            <input type="text" id="tg_chat" name="telegram_chat_id" value="<?php echo htmlspecialchars($settings['telegram_chat_id']); ?>" placeholder="-100123456789">
                        </div>
                    </div>

                    <div style="margin-top:20px; padding:16px; background:rgba(0,120,212,0.05); border-radius:12px; border:1px solid rgba(0,120,212,0.1);">
                        <h3 style="margin-top:0; font-size:14px; margin-bottom:12px; display:flex; align-items:center; gap:6px;">
                            <i data-lucide="zap" style="width:16px; color:var(--win-accent);"></i> Быстрая проверка
                        </h3>
                        <div style="display:flex; gap:12px; align-items:flex-end;">
                            <div style="flex:1;">
                                <label style="font-size:11px; font-weight:700; color:var(--win-text-secondary); margin-bottom:4px; display:block;">ID ЧАТА / ПОЛЬЗОВАТЕЛЯ</label>
                                <input type="text" id="test_tg_id" placeholder="Напр. 123456789" style="height:38px; border-radius:8px;">
                            </div>
                            <button type="button" onclick="testTelegram()" class="btn-secondary" style="height:38px; white-space:nowrap; border-radius:8px; padding: 0 16px;">
                                <i data-lucide="send" style="width:14px;"></i> Тест
                            </button>
                        </div>
                    </div>
                </div>

                <div style="background: rgba(0,0,0,0.02); padding: 20px; border-radius: 12px; border: 1px solid var(--win-border);">
                    <h3 style="margin-top:0; font-size:14px; margin-bottom:12px;">Инструкция по настройке</h3>
                    <ul style="font-size:12px; padding-left:16px; margin:0; line-height:1.6; color:var(--win-text-secondary);">
                        <li style="margin-bottom:8px;">Создайте бота в <a href="https://t.me/BotFather" target="_blank" style="color:var(--win-accent); font-weight:600;">@BotFather</a> и получите <b>Token</b>.</li>
                        <li style="margin-bottom:8px;">Узнайте свой ID в <a href="https://t.me/userinfobot" target="_blank" style="color:var(--win-accent); font-weight:600;">@userinfobot</a> или ID группы.</li>
                        <li style="margin-bottom:8px;">Вставьте данные и нажмите <b>Тест</b>.</li>
                        <li>После успешного теста нажмите <b>Применить изменения</b>.</li>
                    </ul>
                    <div style="margin-top:16px; pt:16px; border-top:1px solid var(--win-border);">
                        <a href="help.php" class="btn-secondary" style="width:100%; font-size:11px; height:32px; padding:0; display:flex; align-items:center; justify-content:center; text-decoration:none;">
                            Подробная справка
                        </a>
                    </div>
                </div>
            </div>
            </section>
        </div>

        <div id="tab-sla" class="tab-content">
            <div class="form-grid">
                <section class="card mica">
                    <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                        <i data-lucide="settings" style="color:var(--win-accent);"></i> Системные
                    </h2>
                    <div class="form-group">
                        <label>Интервал проверки уведомлений (сек)</label>
                        <input type="number" name="polling_interval" value="<?php echo (int)($settings['polling_interval'] ?? 10); ?>" min="2" max="300" required>
                        <small style="color:var(--win-text-secondary); font-size:11px;">Как часто браузер будет проверять новые заявки (рекомендуется 10-30 сек).</small>
                    </div>
                </section>

                <section class="card mica" style="margin-top: 0;">
                    <h2 style="margin-top:0; font-size:18px; display:flex; align-items:center; gap:8px;">
                        <i data-lucide="timer" style="color:var(--win-accent);"></i> Нормативы SLA (в часах)
                    </h2>
                    <div class="form-grid" style="grid-template-columns: repeat(auto-fit, minmax(120px, 1fr));">
                        <div class="form-group">
                            <label style="color:var(--priority-low); font-weight:600;">Низкий</label>
                            <input type="number" name="sla_low" value="<?php echo $settings['sla']['low']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label style="color:var(--priority-medium); font-weight:600;">Средний</label>
                            <input type="number" name="sla_medium" value="<?php echo $settings['sla']['medium']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label style="color:var(--priority-high); font-weight:600;">Высокий</label>
                            <input type="number" name="sla_high" value="<?php echo $settings['sla']['high']; ?>" required>
                        </div>
                        <div class="form-group">
                            <label style="color:var(--priority-critical); font-weight:600;">Критический</label>
                            <input type="number" name="sla_critical" value="<?php echo $settings['sla']['critical']; ?>" required>
                        </div>
                    </div>
                </section>
            </div>
        </div>

        <form id="tg-test-form" method="POST" style="display:none;">
            <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
            <input type="hidden" name="tg_test_token" id="tg_test_token_input">
            <input type="hidden" name="tg_test_id" id="tg_test_id_input">
        </form>

        <script>
        function testTelegram() {
            const token = document.getElementById('tg_token').value;
            const chatId = document.getElementById('test_tg_id').value || document.getElementById('tg_chat').value;

            if (!token || !chatId) {
                alert('Укажите Token и ID чата');
                return;
            }

            document.getElementById('tg_test_token_input').value = token;
            document.getElementById('tg_test_id_input').value = chatId;
            document.getElementById('tg-test-form').submit();
        }
        </script>

        <style>
            .tg-config-layout {
                display: grid;
                grid-template-columns: 1fr 300px;
                gap: 24px;
            }
            @media (max-width: 850px) {
                .tg-config-layout {
                    grid-template-columns: 1fr;
                }
            }
        </style>

        <div style="margin-top: 24px; text-align: right;" id="save-btn-container">
            <button type="submit" class="btn-primary" style="padding: 12px 32px; font-size: 16px;">
                <i data-lucide="save"></i> Применить изменения
            </button>
        </div>
    </form>

    <div id="tab-data" class="tab-content">
        <div class="form-grid">
            <section class="card mica">
                <h3 style="margin-top:0;">Резервное копирование</h3>
                <p style="font-size:13px; color:var(--win-text-secondary); margin-bottom:20px;">
                    Создайте полную копию всех данных системы (пользователи, заявки, настройки и файлы) в формате ZIP.
                </p>
                <div style="display: flex; flex-direction: column; gap: 12px;">
                    <a href="settings.php?action=backup" class="btn-primary" style="display:flex; align-items:center; justify-content:center; gap:8px; text-decoration:none; background: var(--win-accent);">
                        <i data-lucide="download"></i> Скачать архив данных
                    </a>
                </div>

                <div style="margin: 24px 0; height: 1px; background: var(--win-border);"></div>

                <h3>Восстановление</h3>
                <form method="POST" enctype="multipart/form-data">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="maintenance" value="restore">
                    <div class="form-group">
                        <label>Выберите ZIP-архив</label>
                        <input type="file" name="backup_file" accept=".zip" required>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%; background:var(--status-checking);">
                        <i data-lucide="upload"></i> Загрузить и восстановить
                    </button>
                </form>
            </section>

            <section class="card mica">
                <h3 style="margin-top:0;">Архивация и очистка</h3>
                <p style="font-size:13px; color:var(--win-text-secondary); margin-bottom:20px;">
                    Удаление старых данных до указанной даты включительно для оптимизации скорости работы.
                </p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="maintenance" value="purge">
                    <div class="form-group">
                        <label>Удалить всё ДО (включительно):</label>
                        <input type="date" name="purge_date" value="<?php echo date('Y-m-d', strtotime('-1 month')); ?>" required>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%; background:var(--win-accent); margin-bottom: 24px;">
                        <i data-lucide="scissors"></i> Выполнить очистку
                    </button>
                </form>

                <div style="margin: 24px 0; height: 1px; background: var(--win-border);"></div>

                <h3 style="margin-top:0;">Тестовая среда</h3>
                <p style="font-size:13px; color:var(--win-text-secondary); margin-bottom:20px;">
                    Наполните систему сгенерированными данными для обучения персонала.
                </p>
                <a href="settings.php?action=demo" class="btn-primary" style="display:flex; align-items:center; justify-content:center; gap:8px; text-decoration:none; background:var(--status-working); margin-bottom: 24px;">
                    <i data-lucide="flask-conical"></i> Генерировать демо-данные
                </a>

                <div style="margin: 24px 0; height: 1px; background: var(--win-border);"></div>

                <h3 style="color:var(--priority-critical);">Сброс системы</h3>
                <p style="font-size:13px; color:var(--win-text-secondary); margin-bottom:20px;">
                    Удаление всех транзакционных данных. Параметры конфигурации и административные аккаунты сохранятся.
                </p>
                <form method="POST">
                    <input type="hidden" name="csrf_token" value="<?php echo generateCsrfToken(); ?>">
                    <input type="hidden" name="maintenance" value="reset">
                    <div class="form-group">
                        <label>Пароль подтверждения</label>
                        <input type="password" name="reset_password" placeholder="Введите '12345'" required>
                    </div>
                    <button type="submit" class="btn-primary" style="width:100%; background:var(--priority-critical);">
                        <i data-lucide="trash-2"></i> Очистить базу данных
                    </button>
                </form>
            </section>
        </div>
    </div>
</div>

<style>
.settings-tabs {
    display: flex;
    gap: 8px;
    padding: 6px;
    border-radius: 12px;
    overflow-x: auto;
    scrollbar-width: none;
}
.tab-btn {
    background: transparent;
    border: none;
    padding: 10px 20px;
    border-radius: 8px;
    font-weight: 600;
    color: var(--win-text-secondary);
    cursor: pointer;
    white-space: nowrap;
    display: flex;
    align-items: center;
    gap: 8px;
    transition: all 0.2s;
}
.tab-btn i { width: 16px; height: 16px; }
.tab-btn:hover { background: rgba(0,0,0,0.04); }
.tab-btn.active {
    background: var(--win-accent);
    color: white;
}
.tab-content { display: none; animation: fadeIn 0.3s ease-out; }
.tab-content.active { display: block; }

@keyframes fadeIn {
    from { opacity: 0; transform: translateY(10px); }
    to { opacity: 1; transform: translateY(0); }
}
</style>

<script>
function showTab(tabId) {
    document.querySelectorAll('.tab-content').forEach(c => c.classList.remove('active'));
    document.querySelectorAll('.tab-btn').forEach(b => b.classList.remove('active'));

    document.getElementById('tab-' + tabId).classList.add('active');
    event.currentTarget.classList.add('active');

    // Hide save button on data tab as it has its own forms
    const saveBtn = document.getElementById('save-btn-container');
    if (tabId === 'data') {
        saveBtn.style.display = 'none';
    } else {
        saveBtn.style.display = 'block';
    }
}
</script>

<?php include 'includes/footer.php'; ?>
