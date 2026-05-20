<?php
require_once "auth.php";
require_once "../core/autoload.php";

use Sanatorium\Core\Helpers\WebParser;
use Sanatorium\Core\Helpers\DemoDataLoader;
use Sanatorium\Core\Helpers\BackupManager;
use Sanatorium\Core\Database\JsonStore;

$pageTitle = 'Настройки';
$successMessage = '';
$errorMessage = '';
$importResults = null;

$store = new JsonStore(__DIR__ . '/../data');
$settings = json_decode(@file_get_contents(__DIR__ . '/../data/settings.json'), true);

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'update_general') {
        $settings = json_decode(@file_get_contents(__DIR__ . '/../data/settings.json'), true) ?: [];
        $settings['org_name'] = $_POST['org_name'] ?? $settings['org_name'];
        $settings['auth_enabled'] = isset($_POST['auth_enabled']);

        if (isset($_POST['calendar_colors'])) {
            $settings['calendar_colors'] = $_POST['calendar_colors'];
        }

        file_put_contents(__DIR__ . '/../data/settings.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $successMessage = "Настройки сохранены!";
    } elseif ($_POST['action'] === 'export_backup') {
        $backup = new BackupManager(__DIR__ . '/../data');
        $zipPath = $backup->createBackup();
        if ($zipPath && file_exists($zipPath)) {
            header('Content-Type: application/zip');
            header('Content-Disposition: attachment; filename="sanatorium_backup_' . date('Y-m-d_H-i') . '.zip"');
            header('Content-Length: ' . filesize($zipPath));
            readfile($zipPath);
            unlink($zipPath);
            exit;
        } else {
            $errorMessage = "Не удалось создать резервную копию.";
        }
    } elseif ($_POST['action'] === 'restore_backup') {
        if (isset($_FILES['backup_file']) && $_FILES['backup_file']['error'] === UPLOAD_ERR_OK) {
            $backup = new BackupManager(__DIR__ . '/../data');
            $result = $backup->restoreBackup($_FILES['backup_file']['tmp_name']);
            if ($result['success']) {
                $successMessage = "Данные успешно восстановлены! Обновлено файлов: " . $result['count'];
            } else {
                $errorMessage = "Ошибка восстановления: " . $result['message'];
            }
        } else {
            $errorMessage = "Пожалуйста, выберите корректный файл архива.";
        }
    } elseif ($_POST['action'] === 'reset_selective') {
        if ($_POST['confirm_password'] === 'admin123') {
            $toDelete = $_POST['delete'] ?? [];
            $files = [];

            if (isset($toDelete['bookings'])) {
                $files[] = 'bookings.json';
                $files[] = 'room_calendar.json';
            }
            if (isset($toDelete['guests'])) {
                $files[] = 'guests.json';
                // Integrity: deleting guests must clear bookings
                if (!in_array('bookings.json', $files)) $files[] = 'bookings.json';
            }
            if (isset($toDelete['rooms'])) {
                $files[] = 'rooms.json';
                $files[] = 'room_classes.json';
                // Integrity: deleting rooms must clear bookings
                if (!in_array('bookings.json', $files)) $files[] = 'bookings.json';
                if (!in_array('room_calendar.json', $files)) $files[] = 'room_calendar.json';
            }
            if (isset($toDelete['dictionaries'])) {
                $files[] = 'procedures.json';
                $files[] = 'extra_services.json';
                $files[] = 'packages.json';
                $files[] = 'text_blocks.json';
            }
            if (isset($toDelete['system'])) {
                $files[] = 'audit_log.json';
                $files[] = 'expenses.json';
                $files[] = 'inventory.json';
                $files[] = 'tasks.json';
            }

            $successCount = 0;
            foreach (array_unique($files) as $file) {
                $path = __DIR__ . '/../data/' . $file;
                if (file_exists($path)) {
                    file_put_contents($path, json_encode([]));
                    $successCount++;
                }
            }
            $successMessage = "Выбранные данные успешно очищены ($successCount файлов).";
        } else {
            $errorMessage = "Неверный пароль подтверждения.";
        }
    } elseif ($_POST['action'] === 'clean_temp') {
        $files = glob(__DIR__ . '/../data/*.zip');
        foreach ($files as $file) {
            if (is_file($file)) unlink($file);
        }
        $logFile = __DIR__ . '/../server.log';
        if (file_exists($logFile)) file_put_contents($logFile, '');
        $successMessage = "Временные файлы и логи очищены.";
    } elseif ($_POST['action'] === 'load_demo') {
        $loader = new DemoDataLoader();
        if ($loader->load()) {
            $successMessage = "Демонстрационные данные успешно загружены! Теперь вы можете в полной мере оценить возможности системы.";
        } else {
            $errorMessage = "Произошла ошибка при загрузке демо-данных.";
        }
    } elseif ($_POST['action'] === 'import_website') {
        $url = $_POST['import_url'] ?? '';
        if (filter_var($url, FILTER_VALIDATE_URL)) {
            $parser = new WebParser();
            $importResults = $parser->parseAndImport($url);
            if ($importResults['success']) {
                $successMessage = "Импорт завершен! Добавлено объектов: " . $importResults['count'];
            } else {
                $errorMessage = "Ошибка импорта: " . $importResults['message'];
            }
        } else {
            $errorMessage = "Пожалуйста, введите корректный URL сайта.";
        }
    } elseif ($_POST['action'] === 'update_telegram') {
        $settings = json_decode(@file_get_contents(__DIR__ . '/../data/settings.json'), true) ?: [];
        $settings['telegram'] = [
            'bot_token' => $_POST['bot_token'] ?? '',
            'chat_id' => $_POST['chat_id'] ?? '',
            'enabled' => isset($_POST['tg_enabled'])
        ];
        file_put_contents(__DIR__ . '/../data/settings.json', json_encode($settings, JSON_PRETTY_PRINT | JSON_UNESCAPED_UNICODE));
        $successMessage = "Настройки Telegram сохранены!";
    }
}

include 'includes/header.php';
?>

<div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px;">
    <!-- Основные настройки -->
    <div class="mica-card" style="grid-column: span 2;">
        <h2>⚙️ Основные настройки</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update_general">
            <div class="form-group">
                <label>Название организации</label>
                <input type="text" name="org_name" value="<?php echo htmlspecialchars($settings['org_name'] ?? ''); ?>" style="width: 100%;">
            </div>
            <div class="form-group">
                <label>
                    <input type="checkbox" name="auth_enabled" <?php echo ($settings['auth_enabled'] ?? false) ? 'checked' : ''; ?>>
                    Включить защиту паролем (авторизацию)
                </label>
            </div>
            <button type="submit" class="btn btn-primary">Сохранить общие настройки</button>
        </form>
    </div>

    <!-- Управление пользователями -->
    <div class="mica-card" style="grid-column: span 2;">
        <h2>👥 Управление пользователями и правами</h2>
        <p style="color: #666; margin-bottom: 20px;">
            Настройте доступ сотрудников к различным разделам программы. Вы можете добавить неограниченное количество пользователей, назначить им роли и выдать персональные API-токены для мобильного приложения.
        </p>
        <div style="display: flex; gap: 15px;">
            <a href="users.php" class="btn btn-primary">
                <i class="lucide-users"></i> Открыть справочник пользователей
            </a>
            <button type="button" class="btn btn-outline" onclick="location.href='users.php?action=new'">
                <i class="lucide-user-plus"></i> Быстрое добавление
            </button>
        </div>
    </div>

    <!-- Резервное копирование -->
    <div class="mica-card">
        <h2>💾 Резервное копирование</h2>
        <p style="color: #666; margin-bottom: 20px;">
            Сохраните все данные системы (бронирования, гости, справочники) в один архив или восстановите их из ранее созданной копии.
        </p>

        <div style="display: flex; flex-direction: column; gap: 15px;">
            <form method="POST">
                <input type="hidden" name="action" value="export_backup">
                <button type="submit" class="btn btn-primary" style="width: 100%;">
                    <i class="lucide-download"></i> Скачать резервную копию (.zip)
                </button>
            </form>

            <div style="border-top: 1px solid rgba(0,0,0,0.05); pt: 15px; margin-top: 5px;">
                <p style="font-size: 0.85rem; color: #666; margin-bottom: 10px;">Восстановление из файла:</p>
                <form method="POST" enctype="multipart/form-data" onsubmit="return confirm('Внимание! Восстановление из резервной копии перезапишет текущие данные. Продолжить?');">
                    <input type="hidden" name="action" value="restore_backup">
                    <div style="display: flex; gap: 10px;">
                        <input type="file" name="backup_file" accept=".zip" required class="form-control" style="flex-grow: 1; padding: 5px;">
                        <button type="submit" class="btn btn-outline">Восстановить</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <!-- Сброс данных -->
    <div class="mica-card">
        <h2 style="color: #d83b01;">🛠 Обслуживание системы</h2>

        <div style="display: flex; flex-direction: column; gap: 10px; margin-bottom: 20px;">
            <form method="POST">
                <input type="hidden" name="action" value="clean_temp">
                <button type="submit" class="btn btn-outline" style="width: 100%;">Очистить временные файлы и логи</button>
            </form>

            <a href="system_health.php" class="btn btn-outline" style="text-align: center;">Проверить целостность данных</a>
        </div>

        <hr style="border: 0; border-top: 1px solid rgba(0,0,0,0.05); margin: 20px 0;">

        <h3 style="color: #d83b01; font-size: 1rem;">Опасная зона</h3>
        <p style="color: #666; margin-bottom: 20px;">
            Внимание: Операция «Сброс всех данных» безвозвратно удалит все бронирования, записи в календаре, данные гостей, списки номеров, процедур и другие настройки.
        </p>

        <?php if ($successMessage && !isset($importResults)): ?>
            <div class="alert alert-success">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <?php if ($errorMessage && !isset($importResults)): ?>
            <div class="alert alert-danger">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <form id="reset-form" method="POST">
            <input type="hidden" name="action" value="reset_selective">
            <input type="hidden" name="confirm_password" id="confirm_password">

            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 20px;">
                <label class="checkbox-container">
                    <input type="checkbox" name="delete[bookings]" checked>
                    <span class="checkmark"></span>
                    Бронирования и шахматка
                </label>
                <label class="checkbox-container">
                    <input type="checkbox" name="delete[guests]">
                    <span class="checkmark"></span>
                    Справочник гостей (очистит и бронирования)
                </label>
                <label class="checkbox-container">
                    <input type="checkbox" name="delete[rooms]">
                    <span class="checkmark"></span>
                    Номера и категории (очистит и бронирования)
                </label>
                <label class="checkbox-container">
                    <input type="checkbox" name="delete[dictionaries]">
                    <span class="checkmark"></span>
                    Справочники (услуги, процедуры, пакеты)
                </label>
                <label class="checkbox-container">
                    <input type="checkbox" name="delete[system]">
                    <span class="checkmark"></span>
                    Системные логи, задачи и финансы
                </label>
            </div>

            <button type="button" onclick="confirmReset()" class="btn btn-danger" style="width: 100%;">Выполнить очистку выбранных данных</button>
        </form>
    </div>

    <!-- Импорт данных -->
    <div class="mica-card">
        <h2>🌐 Импорт данных </h2>
        <p style="color: #666; margin-bottom: 20px;">
            Автоматическое наполнение справочников. Введите URL вашего сайта, и система попытается найти информацию о номерах, процедурах и услугах.
        </p>

        <?php if (isset($importResults) && $importResults['success']): ?>
            <div class="alert alert-success">
                <strong>Импорт выполнен!</strong><br>
                <?php foreach($importResults['details'] as $type => $count): ?>
                    - <?php echo $type; ?>: <?php echo $count; ?><br>
                <?php endforeach; ?>
            </div>
        <?php elseif (isset($importResults)): ?>
            <div class="alert alert-danger">
                <?php echo $errorMessage; ?>
            </div>
        <?php endif; ?>

        <button type="button" onclick="showImportModal()" class="btn btn-primary">
            <i class="lucide-globe"></i> Начать импорт с сайта
        </button>
    </div>

    <!-- Цвета шахматки -->
    <div class="mica-card" style="grid-column: span 2;">
        <h2>🎨 Цветовая схема шахматки</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update_general">
            <div class="grid-4">
                <div>
                    <label>Свободно</label>
                    <input type="color" name="calendar_colors[free]" value="<?php echo $settings['calendar_colors']['free'] ?? '#ffffff'; ?>">
                </div>
                <div>
                    <label>Резерв</label>
                    <input type="color" name="calendar_colors[reserved]" value="<?php echo $settings['calendar_colors']['reserved'] ?? '#fff3cd'; ?>">
                </div>
                <div>
                    <label>Частично (Муж)</label>
                    <input type="color" name="calendar_colors[partial_male]" value="<?php echo $settings['calendar_colors']['partial_male'] ?? '#e0f2fe'; ?>">
                </div>
                <div>
                    <label>Частично (Жен)</label>
                    <input type="color" name="calendar_colors[partial_female]" value="<?php echo $settings['calendar_colors']['partial_female'] ?? '#fce7f3'; ?>">
                </div>
                <div>
                    <label>Занято полностью</label>
                    <input type="color" name="calendar_colors[full]" value="<?php echo $settings['calendar_colors']['full'] ?? '#fee2e2'; ?>">
                </div>
            </div>
            <button type="submit" class="btn btn-primary" style="margin-top: 20px;">Сохранить цвета</button>
        </form>
    </div>

    <!-- Настройки Telegram -->
    <div class="mica-card" style="grid-column: span 2;">
        <h2>🤖 Уведомления в Telegram</h2>
        <form method="POST">
            <input type="hidden" name="action" value="update_telegram">

            <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 20px; margin-bottom: 20px;">
                <div class="form-group">
                    <label>Токен бота (Bot Token)</label>
                    <input type="password" name="bot_token" value="<?php echo htmlspecialchars($settings['telegram']['bot_token'] ?? ''); ?>" style="width: 100%;" placeholder="123456789:ABCDefgh...">
                </div>
                <div class="form-group">
                    <label>ID чата (Chat ID)</label>
                    <input type="text" name="chat_id" value="<?php echo htmlspecialchars($settings['telegram']['chat_id'] ?? ''); ?>" style="width: 100%;" placeholder="-100123456789">
                </div>
            </div>

            <div class="form-group">
                <label>
                    <input type="checkbox" name="tg_enabled" <?php echo ($settings['telegram']['enabled'] ?? false) ? 'checked' : ''; ?>>
                    Включить отправку уведомлений
                </label>
            </div>

            <div style="background: rgba(0,120,212,0.05); padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(0,120,212,0.1);">
                <h4 style="margin-top: 0; color: var(--primary-color);"> Как настроить?</h4>
                <ol style="font-size: 0.9rem; padding-left: 20px; color: #444;">
                    <li>Найдите в Telegram бота <b>@BotFather</b> и создайте нового бота командой <code>/newbot</code>.</li>
                    <li>Скопируйте полученный <b>HTTP API Token</b> в поле выше.</li>
                    <li>Добавьте бота в ваш чат или группу и напишите ему любое сообщение.</li>
                    <li>Чтобы узнать свой <b>Chat ID</b>, перешлите любое сообщение из этого чата боту <b>@userinfobot</b> или воспользуйтесь ботом <b>@getmyid_bot</b>.</li>
                    <li>Нажмите "Сохранить" и проверьте работу.</li>
                </ol>
            </div>

            <button type="submit" class="btn btn-primary">Сохранить настройки Telegram</button>
        </form>
    </div>

    <!-- Установка PWA / Десктоп -->
    <div class="mica-card" style="grid-column: span 2;">
        <h2>📱 Установка wesbooking</h2>
        <p style="color: #666; margin-bottom: 20px;">
            Вы можете установить wesbooking как полноценное приложение на ваш компьютер или мобильное устройство.
            Это обеспечит быстрый доступ без браузерной строки, работу в офлайн-режиме и нативные уведомления.
        </p>
        <div id="settings-install-container" style="padding: 20px; background: rgba(0,120,212,0.05); border-radius: 12px; border: 1px solid rgba(0,120,212,0.1); display: flex; align-items: center; justify-content: space-between;">
            <div>
                <strong style="display: block; font-size: 1.1rem; color: var(--primary-color);">Готово к установке</strong>
                <span style="font-size: 0.9rem; color: #666;">Нажмите кнопку справа, чтобы добавить wesbooking на рабочий стол</span>
            </div>
            <button id="settings-install-btn" class="btn btn-primary" style="padding: 12px 30px; font-weight: 700;">УСТАНОВИТЬ ПРИЛОЖЕНИЕ</button>
        </div>
        <div id="settings-installed-msg" style="display:none; padding: 20px; background: rgba(16, 124, 16, 0.05); border-radius: 12px; border: 1px solid rgba(16, 124, 16, 0.1); color: #166534;">
            <strong>✅ Приложение уже установлено</strong><br>
            <span style="font-size: 0.9rem;">wesbooking Pro работает в режиме нативного приложения.</span>
        </div>
    </div>

    <!-- Демо-данные -->
    <div class="mica-card" style="grid-column: span 2;">
        <h2>✨ Демонстрационный режим</h2>
        <p style="color: #666; margin-bottom: 20px;">
            Хотите быстро увидеть систему в действии? Нажмите кнопку ниже, чтобы наполнить базу данных примерами: 30 номеров различных классов, 25 лечебных процедур, 30 анкет клиентов, готовые путевки и активные бронирования в календаре.
        </p>

        <?php if ($successMessage && strpos($successMessage, 'Демонстрационные') !== false): ?>
            <div class="alert alert-success">
                <?php echo $successMessage; ?>
            </div>
        <?php endif; ?>

        <form method="POST" onsubmit="return confirm('Это действие удалит текущие данные и заменит их на демонстрационные. Продолжить?');">
            <input type="hidden" name="action" value="load_demo">
            <button type="submit" class="btn btn-secondary" style="background: #6264a7; color: white;">
                <i class="lucide-sparkles"></i> Загрузить демонстрационные данные
            </button>
        </form>
    </div>
</div>

<!-- Modal for Import -->
<div id="import-modal" class="modal-overlay" style="display:none;">
    <div class="mica-card modal-content" style="max-width: 500px;">
        <h3>Импорт данных с сайта</h3>
        <form method="POST">
            <input type="hidden" name="action" value="import_website">
            <div class="form-group">
                <label>URL сайта для парсинга:</label>
                <input type="url" name="import_url" class="form-control" placeholder="https://example.com" required>
            </div>
            <p style="font-size: 0.85rem; color: #666; margin-top: 10px;">
                Система просканирует страницу на наличие структурированных данных, прайс-листов и описаний услуг.
            </p>
            <div style="display: flex; gap: 10px; justify-content: flex-end; margin-top: 20px;">
                <button type="button" onclick="hideImportModal()" class="btn btn-secondary">Отмена</button>
                <button type="submit" class="btn btn-primary">Запустить парсинг</button>
            </div>
        </form>
    </div>
</div>

<script>
function confirmReset() {
    const password = prompt("Для подтверждения удаления всех данных введите пароль:");
    if (password === null) return;

    if (password === "admin123") {
        if (confirm("Вы абсолютно уверены? Все данные будут удалены навсегда!")) {
            document.getElementById('confirm_password').value = password;
            document.getElementById('reset-form').submit();
        }
    } else {
        alert("Неверный пароль.");
    }
}

function showImportModal() {
    document.getElementById('import-modal').style.display = 'flex';
}

function hideImportModal() {
    document.getElementById('import-modal').style.display = 'none';
}

// PWA Logic for Settings Page
window.addEventListener('load', () => {
    const installBtn = document.getElementById('settings-install-btn');
    const container = document.getElementById('settings-install-container');
    const installedMsg = document.getElementById('settings-installed-msg');

    if (window.matchMedia('(display-mode: standalone)').matches) {
        if(container) container.style.display = 'none';
        if(installedMsg) installedMsg.style.display = 'block';
    }

    window.addEventListener('beforeinstallprompt', (e) => {
        // The event is already being handled in header.php, but we can sync here
        if(container) container.style.display = 'flex';
    });

    installBtn?.addEventListener('click', async () => {
        // Use the global deferredPrompt from header.php
        if (typeof deferredPrompt !== 'undefined' && deferredPrompt) {
            deferredPrompt.prompt();
            const { outcome } = await deferredPrompt.userChoice;
            if (outcome === 'accepted') {
                if(container) container.style.display = 'none';
            }
        } else {
            alert('Для установки используйте меню браузера или кнопку в верхней части экрана (если она появилась).');
        }
    });
});
</script>

<style>
.alert {
    padding: 15px;
    border-radius: 8px;
    margin-bottom: 20px;
    border: 1px solid transparent;
}
.alert-success {
    background: rgba(16, 124, 16, 0.1);
    color: #107c10;
    border-color: rgba(16, 124, 16, 0.2);
}
.alert-danger {
    background: rgba(216, 59, 1, 0.1);
    color: #d83b01;
    border-color: rgba(216, 59, 1, 0.2);
}
.modal-overlay {
    position: fixed;
    top: 0; left: 0; right: 0; bottom: 0;
    background: rgba(0,0,0,0.4);
    backdrop-filter: blur(5px);
    display: flex;
    align-items: center;
    justify-content: center;
    z-index: 1000;
}
.modal-content {
    background: #ffffff !important;
    color: #323130 !important;
}
.modal-content h3 {
    color: #323130 !important;
}
.modal-content label {
    color: #323130 !important;
    font-weight: 600;
}

.checkbox-container {
    display: block;
    position: relative;
    padding-left: 35px;
    margin-bottom: 12px;
    cursor: pointer;
    font-size: 0.95rem;
    user-select: none;
    color: #323130;
}
.checkbox-container input {
    position: absolute;
    opacity: 0;
    cursor: pointer;
    height: 0; width: 0;
}
.checkmark {
    position: absolute;
    top: 0; left: 0;
    height: 22px; width: 22px;
    background-color: #eee;
    border-radius: 4px;
}
.checkbox-container:hover input ~ .checkmark {
    background-color: #ccc;
}
.checkbox-container input:checked ~ .checkmark {
    background-color: #d83b01;
}
.checkmark:after {
    content: "";
    position: absolute;
    display: none;
}
.checkbox-container input:checked ~ .checkmark:after {
    display: block;
}
.checkbox-container .checkmark:after {
    left: 8px; top: 4px;
    width: 6px; height: 11px;
    border: solid white;
    border-width: 0 2px 2px 0;
    transform: rotate(45deg);
}
</style>

<?php include 'includes/footer.php'; ?>
