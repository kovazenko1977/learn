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
    } elseif ($_POST['action'] === 'reset_all') {
        if ($_POST['confirm_password'] === 'admin123') {
            $dataFiles = [
                'bookings.json', 'room_calendar.json', 'guests.json', 'plans.json',
                'rooms.json', 'room_classes.json', 'procedures.json', 'extra_services.json',
                'packages.json', 'text_blocks.json'
            ];

            $successCount = 0;
            foreach ($dataFiles as $file) {
                $path = __DIR__ . '/../data/' . $file;
                if (file_exists($path)) {
                    file_put_contents($path, json_encode([]));
                    $successCount++;
                }
            }
            $successMessage = "Все данные успешно удалены. Очищено файлов: $successCount.";
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
            <input type="hidden" name="action" value="reset_all">
            <input type="hidden" name="confirm_password" id="confirm_password">
            <button type="button" onclick="confirmReset()" class="btn btn-danger">Удалить все данные программы</button>
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
</style>

<?php include 'includes/footer.php'; ?>
