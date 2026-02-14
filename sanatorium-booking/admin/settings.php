<?php
require_once "auth.php";
require_once "../core/autoload.php";

$pageTitle = 'Настройки';
$successMessage = '';
$errorMessage = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'reset_all') {
    if ($_POST['confirm_password'] === '12345') {
        // Implementation will be in next step, but I'll add the UI feedback now
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
}

include 'includes/header.php';
?>

<div class="mica-card" style="max-width: 600px;">
    <h2>Опасная зона</h2>
    <p style="color: #666; margin-bottom: 20px;">
        Внимание: Операция «Сброс всех данных» безвозвратно удалит все бронирования, записи в календаре, данные гостей, списки номеров, процедур и другие настройки. Это действие нельзя отменить.
    </p>

    <?php if ($successMessage): ?>
        <div style="background: rgba(16, 124, 16, 0.1); color: #107c10; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(16, 124, 16, 0.2);">
            <?php echo $successMessage; ?>
        </div>
    <?php endif; ?>

    <?php if ($errorMessage): ?>
        <div style="background: rgba(216, 59, 1, 0.1); color: #d83b01; padding: 15px; border-radius: 8px; margin-bottom: 20px; border: 1px solid rgba(216, 59, 1, 0.2);">
            <?php echo $errorMessage; ?>
        </div>
    <?php endif; ?>

    <form id="reset-form" method="POST">
        <input type="hidden" name="action" value="reset_all">
        <input type="hidden" name="confirm_password" id="confirm_password">
        <button type="button" onclick="confirmReset()" class="btn btn-danger">Удалить все данные программы</button>
    </form>
</div>

<script>
function confirmReset() {
    const password = prompt("Для подтверждения удаления всех данных введите пароль:");
    if (password === null) return; // Cancelled

    if (password === "12345") {
        if (confirm("Вы абсолютно уверены? Все данные будут удалены навсегда!")) {
            document.getElementById('confirm_password').value = password;
            document.getElementById('reset-form').submit();
        }
    } else {
        alert("Неверный пароль.");
    }
}
</script>

<?php include 'includes/footer.php'; ?>
