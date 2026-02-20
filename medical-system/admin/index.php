<?php
require_once __DIR__ . '/header.php';
requireLogin();

$user = getCurrentUser();
$role = $user['role'];

// Automatic redirect based on role
if ($role === 'doctor') { header('Location: doctor.php'); exit; }
if ($role === 'cashier') { header('Location: cashier.php'); exit; }
if ($role === 'nurse') { header('Location: nurse.php'); exit; }
?>

<div class="win-card mica-effect">
    <h1>Добро пожаловать, <?php echo htmlspecialchars($user['name']); ?>!</h1>
    <p>Выберите раздел для работы:</p>

    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 20px; margin-top: 30px;">
        <a href="doctor.php" class="win-card mica-effect" style="text-decoration: none; color: inherit; text-align: center;">
            <i class="lucide-clipboard-list" style="font-size: 40px; color: var(--win-accent);"></i>
            <h3>Врач (Назначения)</h3>
        </a>
        <a href="cashier.php" class="win-card mica-effect" style="text-decoration: none; color: inherit; text-align: center;">
            <i class="lucide-wallet" style="font-size: 40px; color: var(--success);"></i>
            <h3>Кассир (Оплата)</h3>
        </a>
        <a href="nurse.php" class="win-card mica-effect" style="text-decoration: none; color: inherit; text-align: center;">
            <i class="lucide-user-check" style="font-size: 40px; color: var(--win-accent);"></i>
            <h3>Медсестра (Прием)</h3>
        </a>
        <?php if ($role === 'admin'): ?>
        <a href="dictionary.php" class="win-card mica-effect" style="text-decoration: none; color: inherit; text-align: center;">
            <i class="lucide-settings" style="font-size: 40px; color: var(--text-sec);"></i>
            <h3>Справочники</h3>
        </a>
        <?php endif; ?>
    </div>
</div>

<?php require_once __DIR__ . '/footer.php'; ?>
