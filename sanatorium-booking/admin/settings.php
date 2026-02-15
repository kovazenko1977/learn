<?php
require_once "auth.php";
require_once "../core/autoload.php";

use Sanatorium\Core\Helpers\WebParser;
use Sanatorium\Core\Helpers\DemoDataLoader;

$pageTitle = 'Настройки';
$successMessage = '';
$errorMessage = '';
$importResults = null;

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if ($_POST['action'] === 'reset_all') {
        if ($_POST['confirm_password'] === '12345') {
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

    <!-- Сброс данных -->
    <div class="mica-card">
        <h2 style="color: #d83b01;">⚠️ Опасная зона</h2>
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
        <h2>🌐 Импорт данных (PRO)</h2>
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

    if (password === "12345") {
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
